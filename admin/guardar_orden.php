<?php
ob_start();

session_start();
require_once 'config.php';

ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No autorizado.']);
    exit;
}

// ── MODO DEPURACIÓN TEMPORAL ──
// Si la URL trae ?debug=1, devolvemos TODO lo que llegó al servidor,
// sin importar si viene por POST tradicional o por el cuerpo crudo.
if (isset($_GET['debug'])) {
    echo json_encode([
        'ok' => false,
        'metodo' => $_SERVER['REQUEST_METHOD'] ?? null,
        'content_type_servidor' => $_SERVER['CONTENT_TYPE'] ?? null,
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? null,
        'post_completo' => $_POST,
        'get_completo' => $_GET,
        'php_input_crudo' => file_get_contents('php://input'),
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $crudo = $_POST['ids'] ?? '';
    $ids = json_decode($crudo, true);

    if (!is_array($ids) || empty($ids)) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => 'Datos inválidos recibidos del navegador.',
            'debug_crudo' => $crudo,
            'debug_json_error' => json_last_error_msg(),
            'debug_post_keys' => array_keys($_POST),
            'debug_content_type' => $_SERVER['CONTENT_TYPE'] ?? 'ninguno'
        ]);
        exit;
    }

    $conn->beginTransaction();
    $stmt = $conn->prepare("UPDATE libros SET orden = ? WHERE id = ?");
    foreach ($ids as $posicion => $id) {
        $stmt->execute([$posicion + 1, (int)$id]);
    }
    $conn->commit();

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}