<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

// Catálogo de áreas temáticas para pintar los checkboxes
$areas_disponibles = $conn->query("SELECT id, nombre FROM areas_tematicas ORDER BY id ASC")->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo    = trim($_POST['titulo'] ?? '');
    $autor     = trim($_POST['autor'] ?? '');
    $anio      = (int)($_POST['anio'] ?? 0);
    $sinopsis  = trim($_POST['sinopsis'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $areas_seleccionadas = $_POST['areas_tematicas'] ?? []; // array de IDs

    if (empty($areas_seleccionadas)) {
        $error = 'Selecciona al menos un área temática.';
    }

    // Subir imagen
    $portada = '';
    if (empty($error)) {
        if (!empty($_FILES['portada']['name'])) {
            $ext = strtolower(pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION));
            $permitidos = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $permitidos)) {
                $error = 'Solo se permiten imágenes JPG, PNG o WEBP.';
            } else {
                $nombre = uniqid('libro_') . '.' . $ext;
                $destino = '../images/libros/' . $nombre;
                if (!is_dir('../images/libros/')) mkdir('../images/libros/', 0755, true);
                if (move_uploaded_file($_FILES['portada']['tmp_name'], $destino)) {
                    $portada = $nombre;
                } else {
                    $error = 'Error al subir la imagen.';
                }
            }
        } else {
            $error = 'La portada es obligatoria.';
        }
    }

    if (empty($error)) {
        try {
            $conn->beginTransaction();

            // area_tematica ya no se llena aquí; la relación vive en libro_areas_tematicas
            $siguienteOrden = (int)$conn->query("SELECT COALESCE(MAX(orden), 0) + 1 FROM libros")->fetchColumn();

            $stmt = $conn->prepare("INSERT INTO libros (titulo, autor, anio, sinopsis, categoria, portada, orden) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titulo, $autor, $anio, $sinopsis, $categoria, $portada, $siguienteOrden]);
            $libro_id = $conn->lastInsertId();

            $stmtArea = $conn->prepare("INSERT INTO libro_areas_tematicas (libro_id, area_id) VALUES (?, ?)");
            foreach ($areas_seleccionadas as $area_id) {
                $stmtArea->execute([$libro_id, (int)$area_id]);
            }

            $conn->commit();
            header('Location: panel.php?msg=agregado');
            exit;
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = 'Error al guardar el libro: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agregar libro — Panel Administrativo</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background: #eef2f7; min-height: 100vh; }

    /* ── NAVBAR ── */
    .navbar { background: #0b2f47; padding: 14px 32px; display: flex; align-items: center; justify-content: space-between; }
    .navbar-title { color: #fff; font-size: 17px; font-weight: 800; }
    .btn-logout {
      display: inline-flex; align-items: center; gap: 8px;
      color: #cfe4ee; font-size: 13px; font-weight: 700;
      text-decoration: none;
      border: 1.5px solid rgba(255,255,255,0.18);
      border-radius: 10px;
      padding: 8px 16px;
      transition: all 0.2s ease;
    }
    .btn-logout:hover { border-color: #29b6e8; color: #fff; }

    /* ── CONTENIDO ── */
    .container { max-width: 680px; margin: 40px auto; padding: 0 24px; }
    .page-label {
      display: flex; align-items: center; gap: 10px;
      font-size: 12px; font-weight: 800; letter-spacing: 1.5px;
      color: #6a8fa0; text-transform: uppercase; margin-bottom: 8px;
    }
    .page-label .dash { width: 22px; height: 3px; background: #29b6e8; border-radius: 2px; display: inline-block; }
    h1 { font-size: 28px; font-weight: 900; color: #0d3d4f; margin-bottom: 28px; }

    .card { background: #fff; border-radius: 18px; padding: 36px; box-shadow: 0 4px 24px rgba(11,58,98,0.06); }

    .form-group { margin-bottom: 20px; }
    label { font-size: 13px; font-weight: 700; color: #0d3d4f; display: block; margin-bottom: 6px; }

    input, select, textarea {
      width: 100%;
      padding: 12px 16px;
      border: 1.5px solid #d8e6ec;
      border-radius: 10px;
      font-size: 14px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: #0d3d4f;
      outline: none;
      background: #fff;
    }
    textarea { resize: vertical; }
    input:focus, select:focus, textarea:focus { border-color: #29b6e8; }

    .hint-inline { font-size: 11px; font-weight: 400; color: #6a8fa0; }
    .hint { font-size: 11px; color: #6a8fa0; margin-top: 4px; }
    .contador-caracteres { text-align: right; font-size: 11px; color: #6a8fa0; margin-top: 4px; }
    .contador-caracteres.limite { color: #c0392b; font-weight: 700; }

    .btn {
      background: #29b6e8; color: #0b2f47; border: none; border-radius: 10px;
      padding: 14px 28px; font-size: 15px; font-weight: 800;
      font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer;
      transition: background 0.2s ease;
    }
    .btn:hover { background: #1a9bd1; }

    .btn-cancel {
      background: transparent; color: #0d3d4f;
      border: 1.5px solid #d8e6ec; border-radius: 10px;
      padding: 14px 28px; font-size: 15px; font-weight: 800;
      font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer;
      text-decoration: none; display: inline-block; margin-left: 10px;
      transition: all 0.2s ease;
    }
    .btn-cancel:hover { border-color: #29b6e8; color: #1a9bd1; }

    .error {
      background: #fdeceb; color: #c0392b;
      padding: 12px 18px; border-radius: 10px;
      font-size: 13px; margin-bottom: 20px; font-weight: 700;
    }

    /* Checkboxes de área temática */
    .checkbox-group {
      max-height: 260px; overflow-y: auto;
      border: 1.5px solid #d8e6ec; border-radius: 10px;
      padding: 8px 4px; background: #fff;
    }
    .checkbox-item {
      display: flex; align-items: center; gap: 8px;
      padding: 7px 12px; font-size: 14px; font-weight: 400;
      color: #0d3d4f; cursor: pointer; border-radius: 6px;
    }
    .checkbox-item:hover { background: #eef7fb; }
    .checkbox-item input[type="checkbox"] {
      width: 16px; height: 16px; accent-color: #29b6e8;
      cursor: pointer; flex-shrink: 0;
    }
  </style>
</head>
<body>

<div class="navbar">
  <span class="navbar-title">Panel Administrativo</span>
  <a href="panel.php" class="btn-logout">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Volver al panel
  </a>
</div>

<div class="container">
  <div class="page-label"><span class="dash"></span>Catálogo</div>
  <h1>+ Agregar libro nuevo</h1>

  <?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label>Título *</label>
        <input type="text" name="titulo" placeholder="Título del libro" value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Autor *</label>
        <input type="text" name="autor" placeholder="Nombre del autor" value="<?= htmlspecialchars($_POST['autor'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Año *</label>
        <input type="number" name="anio" placeholder="2024" min="1900" max="2099" value="<?= htmlspecialchars($_POST['anio'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Sinopsis <span class="hint-inline">(opcional, máx. 800 caracteres)</span></label>
        <textarea name="sinopsis" id="sinopsisInput" maxlength="800" rows="5" placeholder="Breve resumen del libro que se mostrará en su ficha..."><?= htmlspecialchars($_POST['sinopsis'] ?? '') ?></textarea>
        <div class="contador-caracteres"><span id="sinopsisContador">0</span>/800</div>
      </div>
      <div class="form-group">
        <label>Categoría *</label>
        <?php $catSel = $_POST['categoria'] ?? ''; ?>
        <select name="categoria" required>
          <option value="">Selecciona una categoría</option>
          <option value="Literatura" <?= $catSel==='Literatura'?'selected':'' ?>>Literatura</option>
          <option value="Literatura Infantil y Juvenil" <?= $catSel==='Literatura Infantil y Juvenil'?'selected':'' ?>>Literatura Infantil y Juvenil</option>
          <option value="Ciencias Sociales y Humanidades" <?= $catSel==='Ciencias Sociales y Humanidades'?'selected':'' ?>>Ciencias Sociales y Humanidades</option>
          <option value="Ciencias, Salud y Tecnología" <?= $catSel==='Ciencias, Salud y Tecnología'?'selected':'' ?>>Ciencias, Salud y Tecnología</option>
          <option value="Economía, Empresa y Desarrollo Organizacional" <?= $catSel==='Economía, Empresa y Desarrollo Organizacional'?'selected':'' ?>>Economía, Empresa y Desarrollo Organizacional</option>
          <option value="Arte, Diseño y Fotografía" <?= $catSel==='Arte, Diseño y Fotografía'?'selected':'' ?>>Arte, Diseño y Fotografía</option>
          <option value="Educación y Formación" <?= $catSel==='Educación y Formación'?'selected':'' ?>>Educación y Formación</option>
        </select>
      </div>
      <div class="form-group">
        <label>Área temática * (puedes seleccionar varias)</label>
        <div class="checkbox-group">
          <?php
          $seleccionadasPrevias = $_POST['areas_tematicas'] ?? [];
          foreach ($areas_disponibles as $area):
              $checked = in_array($area['id'], $seleccionadasPrevias) ? 'checked' : '';
          ?>
            <label class="checkbox-item">
              <input type="checkbox" name="areas_tematicas[]" value="<?= $area['id'] ?>" <?= $checked ?>>
              <?= htmlspecialchars($area['nombre']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="form-group">
        <label>Portada (imagen) *</label>
        <input type="file" name="portada" accept="image/*" required>
        <div class="hint">Formatos: JPG, PNG, WEBP. Tamaño recomendado: 400x600px</div>
      </div>
      <div style="margin-top:8px;">
        <button type="submit" class="btn">Publicar libro</button>
        <a href="panel.php" class="btn-cancel">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
  var sinopsisInput = document.getElementById('sinopsisInput');
  var sinopsisContador = document.getElementById('sinopsisContador');
  function actualizarContador() {
    var len = sinopsisInput.value.length;
    sinopsisContador.textContent = len;
    sinopsisContador.parentElement.classList.toggle('limite', len >= 800);
  }
  sinopsisInput.addEventListener('input', actualizarContador);
  actualizarContador();
</script>

</body>
</html>