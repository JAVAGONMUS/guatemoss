<?php
require_once "database.php"; // 📌 Importa tus funciones de conexión

// 📌 Validar datos recibidos
if (empty($_POST['ids']) || empty($_POST['id_empr'])) {
    die("No se recibieron parámetros válidos");
}

$idEmpresa = intval($_POST['id_empr']);

$FullPic = $_POST['FullPic'] ?? '';
$idsArray = [];

// Debug: ver qué está llegando
if (empty($FullPic)) {
    die("⚠️ No se recibió ningún ID en FullPic");
}

// Si llega ya como array (caso raro)
if (is_array($FullPic)) {
    $idsArray = array_map('intval', $FullPic);
} else {
    // Si llega como string separado por comas
    $idsArray = array_filter(array_map('intval', explode(',', $FullPic)));
}

// Debug opcional (solo en pruebas, no en producción)
var_dump($FullPic);
var_dump($idsArray);




if (count($ids) === 0) {
    die("No se recibieron IDs de fotos");
}

// 📌 Crear placeholders dinámicos (?, ?, ?)
$placeholders = implode(",", array_fill(0, count($ids), "?"));

// 📌 Construir consulta segura
$sql = "
    SELECT ID_FOT, NOMBRE, FOTO, TIPO_MIME
    FROM FOTOS
    WHERE ID_FOT IN ($placeholders)
      AND ID_EMPR = ?
      AND TIPO_MIME LIKE 'image/%'
";

// 📌 Preparar parámetros (IDs + empresa)
$params = array_merge($ids, [$idEmpresa]);

// 📌 Ejecutar consulta con tu helper
$fotos = executeQuery($sql, $params);

if (empty($fotos)) {
    die("No se encontraron fotos para descargar.");
}

// 📦 Crear archivo ZIP temporal
$zipFile = tempnam(sys_get_temp_dir(), 'fotos') . ".zip";
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
    die("No se pudo crear el archivo ZIP");
}

// ➕ Agregar imágenes con su nombre real
foreach ($fotos as $row) {
    $fileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $row['NOMBRE']); 
    $zip->addFromString($fileName, $row['FOTO']); // FOTO es el BLOB
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


