<?php
require_once "database.php"; // 📌 Importa funciones de conexión

// 📌 Validar datos recibidos
if (empty($_POST['ids']) || empty($_POST['id_empr'])) {
    die("No se recibieron parámetros válidos");
}

$idEmpresa = intval($_POST['id_empr']);
$ids = $_POST['ids'] ?? '';
$idsArray = [];

// Si llega ya como array
if (is_array($ids)) {
    $idsArray = array_map('intval', $ids);
} else {
    $idsArray = array_filter(array_map('intval', explode(',', $ids)));
}

if (empty($idsArray)) {
    die("⚠️ No se recibieron IDs válidos para descargar.");
}

// 📌 Crear placeholders dinámicos (?, ?, ?)
$placeholders = implode(",", array_fill(0, count($idsArray), "?"));

// 📌 Consulta segura (corrigiendo ID_FOTO)
$sql = "
    SELECT ID_FOT, NOMBRE, FOTO, TIPO_MIME
    FROM FOTOS
    WHERE ID_FOT IN ($placeholders)
      AND ID_EMPR = ?
      AND TIPO_MIME LIKE 'image/%'
";

$params = array_merge($idsArray, [$idEmpresa]);

$fotos = executeQuery($sql, $params);

if (empty($fotos) || !is_array($fotos)) {
    die("No se encontraron fotos para descargar.");
}

// 📦 Crear archivo ZIP temporal
$zipFile = tempnam(sys_get_temp_dir(), 'fotos') . ".zip";
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
    die("No se pudo crear el archivo ZIP");
}

// ➕ Agregar imágenes
foreach ($fotos as $row) {
    $fileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $row['NOMBRE']);
    $zip->addFromString($fileName, $row['FOTO']); // FOTO es BLOB
}

$zip->close();

// 📥 Forzar descarga del ZIP
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=fotos_catalogo.zip");
header("Content-Length: " . filesize($zipFile));
readfile($zipFile);

// 🗑️ Eliminar temporal
unlink($zipFile);
exit;
?>
