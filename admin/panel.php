<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

// Trae cada libro junto con sus áreas temáticas ya unidas en un solo texto
$stmt = $conn->query("
    SELECT l.*, GROUP_CONCAT(a.nombre ORDER BY a.id SEPARATOR ', ') AS areas_nombres
    FROM libros l
    LEFT JOIN libro_areas_tematicas lat ON lat.libro_id = l.id
    LEFT JOIN areas_tematicas a ON a.id = lat.area_id
    GROUP BY l.id
    ORDER BY l.orden ASC
");
$libros = $stmt->fetchAll();
$totalLibros = count($libros);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Administrativo</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background: #eef2f7; min-height: 100vh; }

    /* ── NAVBAR ── */
    .navbar {
      background: #0b2f47;
      padding: 14px 32px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .navbar-brand { display: flex; align-items: center; gap: 12px; }
    .navbar-title { color: #fff; font-size: 17px; font-weight: 800; }

    .navbar-user { display: flex; align-items: center; gap: 12px; }
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
    .container { max-width: 1300px; margin: 40px auto; padding: 0 24px; }

    .success { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; font-size: 14px; }

    .header-row {
      display: flex; align-items: flex-end; justify-content: space-between;
      flex-wrap: wrap; gap: 20px;
      margin-bottom: 28px;
    }
    .page-label {
      display: flex; align-items: center; gap: 10px;
      font-size: 12px; font-weight: 800; letter-spacing: 1.5px;
      color: #6a8fa0; text-transform: uppercase; margin-bottom: 8px;
    }
    .page-label .dash { width: 22px; height: 3px; background: #29b6e8; border-radius: 2px; display: inline-block; }
    h1 { font-size: 30px; font-weight: 900; color: #0d3d4f; display: flex; align-items: center; gap: 12px; }
    .count-badge {
      background: #dff3fa; color: #1a8fc0;
      font-size: 14px; font-weight: 800;
      width: 28px; height: 28px; border-radius: 50%;
      display: inline-flex; align-items: center; justify-content: center;
    }

    .header-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .search-box {
      display: flex; align-items: center; gap: 8px;
      background: #fff; border: 1.5px solid #d8e6ec; border-radius: 12px;
      padding: 11px 16px; min-width: 260px;
    }
    .search-box svg { flex-shrink: 0; color: #8fa9b6; }
    .search-box input {
      border: none; outline: none; font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 14px; color: #0d3d4f; width: 100%; background: transparent;
    }
    .search-box input::placeholder { color: #a8bfc9; }

    .btn-add {
      background: #29b6e8; color: #0b2f47; border: none; border-radius: 12px;
      padding: 13px 22px; font-size: 14px; font-weight: 800;
      font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer;
      text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
      white-space: nowrap;
      transition: background 0.2s ease;
    }
    .btn-add:hover { background: #1a9bd1; }

    /* ── TABLA ── */
    .tabla-card { background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 4px 24px rgba(11,58,98,0.06); }
    .tabla-card.oculto { display: none; }
    table { width: 100%; border-collapse: collapse; }
    th {
      background: #f5f9fb; color: #6a8fa0;
      padding: 14px 20px; text-align: left;
      font-size: 11.5px; font-weight: 800; letter-spacing: 0.8px; text-transform: uppercase;
      white-space: nowrap;
      border-bottom: 1px solid #e9f0f4;
    }
    td { padding: 16px 20px; border-bottom: 1px solid #eef4f7; font-size: 14px; color: #0d3d4f; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #fafcfd; }

    .drag-handle { cursor: grab; color: #b8ccd6; text-align: center; width: 36px; }
    .drag-handle:active { cursor: grabbing; }
    tr.sortable-ghost { opacity: 0.4; background: #eaf4f8; }
    tr.sortable-chosen td { background: #f5fbfd; }

    .portada-thumb { width: 46px; height: 64px; object-fit: cover; border-radius: 8px; }

    .col-titulo { font-weight: 800; }
    .col-autor { color: #4a7a8a; }
    .col-anio { font-weight: 800; }

    .pill-categoria {
      display: inline-block;
      background: #dff3fa; color: #1a5f7a;
      font-size: 12.5px; font-weight: 700;
      padding: 6px 14px; border-radius: 8px;
      white-space: nowrap;
    }

    .col-areas { max-width: 220px; font-size: 13px; color: #4a7a8a; }
    .col-areas .bullet { color: #29b6e8; margin-right: 4px; }

    .acciones-cell { display: flex; align-items: center; gap: 8px; }
    .btn-edit {
      display: inline-flex; align-items: center; gap: 6px;
      background: #fff; color: #0d3d4f;
      border: 1.5px solid #d8e6ec; border-radius: 8px;
      padding: 8px 14px; font-size: 12.5px; font-weight: 700;
      font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer;
      text-decoration: none; white-space: nowrap;
      transition: all 0.2s ease;
    }
    .btn-edit:hover { border-color: #29b6e8; color: #1a9bd1; }
    .btn-del {
      display: inline-flex; align-items: center; justify-content: center;
      width: 34px; height: 34px;
      background: #fdeceb; color: #c0392b;
      border: none; border-radius: 8px;
      cursor: pointer; text-decoration: none; flex-shrink: 0;
      transition: background 0.2s ease;
    }
    .btn-del:hover { background: #f9d4d1; }

    .tabla-footer {
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 8px;
      padding: 16px 20px;
      border-top: 1px solid #eef4f7;
      font-size: 13px; color: #6a8fa0;
    }

    .empty { text-align: center; padding: 60px; color: #4a7a8a; font-size: 15px; }

    .orden-msg {
      position: fixed;
      bottom: 24px; right: 24px;
      background: #0d3d4f; color: #fff;
      padding: 10px 20px; border-radius: 10px;
      font-size: 13px; font-weight: 700;
      opacity: 0; transform: translateY(10px);
      transition: opacity 0.25s ease, transform 0.25s ease;
      pointer-events: none;
      max-width: 90vw;
      word-break: break-word;
      z-index: 999;
    }
    .orden-msg.mostrando { opacity: 1; transform: translateY(0); }

    @media (max-width: 720px) {
      .navbar { padding: 12px 18px; }
      .container { padding: 0 16px; }
      h1 { font-size: 24px; }
      .header-row { align-items: flex-start; }
      .search-box { min-width: 0; flex: 1; }
      .header-actions { width: 100%; }
      .tabla-card { overflow-x: auto; }
    }
  </style>
</head>
<body>

<div class="navbar">
  <div class="navbar-brand">
    <span class="navbar-title">Panel Administrativo</span>
  </div>
  <div class="navbar-user">
    <a href="logout.php" class="btn-logout">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Cerrar sesión
    </a>
  </div>
</div>

<div class="container">

  <?php if (isset($_GET['msg'])): ?>
    <div class="success">
      <?php
        if ($_GET['msg'] === 'agregado') echo '✅ Libro agregado exitosamente.';
        if ($_GET['msg'] === 'editado') echo '✅ Libro actualizado exitosamente.';
        if ($_GET['msg'] === 'eliminado') echo '✅ Libro eliminado exitosamente.';
      ?>
    </div>
  <?php endif; ?>

  <div class="header-row">
    <div>
      <div class="page-label"><span class="dash"></span>Catálogo</div>
      <h1>Libros publicados <span class="count-badge"><?= $totalLibros ?></span></h1>
    </div>
    <div class="header-actions">
      <div class="search-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="buscarInput" placeholder="Buscar título o autor" oninput="filtrarLibros()">
      </div>
      <a href="agregar.php" class="btn-add">+ Agregar libro</a>
    </div>
  </div>

  <div class="tabla-card <?= $totalLibros === 0 ? '' : '' ?>" id="tablaCard">
    <?php if ($totalLibros === 0): ?>
      <div class="empty">No hay libros publicados aún. ¡Agrega el primero!</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th style="width:36px;"></th>
            <th>Portada</th>
            <th>Título</th>
            <th>Autor</th>
            <th>Año</th>
            <th>Categoría</th>
            <th>Área temática</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tablaLibros">
          <?php foreach ($libros as $libro): ?>
          <tr data-id="<?= $libro['id'] ?>" data-buscar="<?= htmlspecialchars(mb_strtolower($libro['titulo'] . ' ' . $libro['autor'])) ?>">
            <td class="drag-handle" title="Arrastra para reordenar">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="8" cy="6" r="1.6"/><circle cx="8" cy="12" r="1.6"/><circle cx="8" cy="18" r="1.6"/><circle cx="16" cy="6" r="1.6"/><circle cx="16" cy="12" r="1.6"/><circle cx="16" cy="18" r="1.6"/></svg>
            </td>
            <td><img class="portada-thumb" src="../images/libros/<?= htmlspecialchars($libro['portada']) ?>" alt="portada"></td>
            <td class="col-titulo"><?= htmlspecialchars($libro['titulo']) ?></td>
            <td class="col-autor"><?= htmlspecialchars($libro['autor']) ?></td>
            <td class="col-anio"><?= htmlspecialchars($libro['anio']) ?></td>
            <td><span class="pill-categoria"><?= htmlspecialchars($libro['categoria']) ?></span></td>
            <td class="col-areas"><?php if (!empty($libro['areas_nombres'])): ?><span class="bullet">●</span><?= htmlspecialchars($libro['areas_nombres']) ?><?php else: ?>—<?php endif; ?></td>
            <td>
              <div class="acciones-cell">
                <a href="editar.php?id=<?= $libro['id'] ?>" class="btn-edit">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                  Editar
                </a>
                <a href="eliminar.php?id=<?= $libro['id'] ?>" class="btn-del" title="Eliminar" onclick="return confirm('¿Seguro que quieres eliminar este libro?')">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="tabla-footer">
        <span>Mostrando <span id="contadorVisible"><?= $totalLibros ?></span> de <?= $totalLibros ?> libros</span>
        <span>Arrastra para reordenar el catálogo</span>
      </div>
    <?php endif; ?>
  </div>

  <div id="ordenMsg" class="orden-msg"></div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var tabla = document.getElementById('tablaLibros');
  if (!tabla) return;

  new Sortable(tabla, {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function () {
      var ids = Array.from(tabla.querySelectorAll('tr')).map(function (fila) {
        return fila.getAttribute('data-id');
      });

      var msg = document.getElementById('ordenMsg');
      msg.textContent = 'Guardando orden...';
      msg.className = 'orden-msg mostrando';

      fetch('guardar_orden', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'ids=' + encodeURIComponent(JSON.stringify(ids))
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.ok) {
            msg.textContent = '✅ Orden guardado';
          } else {
            msg.textContent = '❌ ' + (data.error || 'Error al guardar el orden');
          }
          setTimeout(function () { msg.className = 'orden-msg'; }, 4000);
        })
        .catch(function (err) {
          msg.textContent = '❌ Error de conexión: ' + err.message;
          setTimeout(function () { msg.className = 'orden-msg'; }, 4000);
        });
    }
  });
});

function filtrarLibros() {
  var texto = document.getElementById('buscarInput').value.trim().toLowerCase();
  var filas = document.querySelectorAll('#tablaLibros tr');
  var visibles = 0;

  filas.forEach(function (fila) {
    var coincide = fila.getAttribute('data-buscar').indexOf(texto) !== -1;
    fila.style.display = coincide ? '' : 'none';
    if (coincide) visibles++;
  });

  var contador = document.getElementById('contadorVisible');
  if (contador) contador.textContent = visibles;
}
</script>

</body>
</html>