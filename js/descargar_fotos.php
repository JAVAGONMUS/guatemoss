<?php
require_once 'database.php';

// 🔹 Validar datos recibidos
if (empty($_POST['ids']) || empty($_POST['id_empr'])) {
    die("No se recibieron parámetros válidos");
}

$idEmpresa = intval($_POST['id_empr']);
$FullPic = $_POST['ids'];

// 🔹 Convertir la cadena de IDs en array y limpiar
$ids = array_filter(array_map('intval', explode(",", $FullPic)));

if (count($ids) === 0) {
    die("No se recibieron IDs válidos de fotos");
}

// 🔹 Crear placeholders dinámicos para PDO
$placeholders = implode(",", array_fill(0, count($ids), "?"));

// 🔹 Preparar parámetros para PDO (IDs + ID_EMPR)
$params = array_merge($ids, [$idEmpresa]);

// 🔹 Consulta segura: solo imágenes
$sql = "
    SELECT ID_FOT, NOMBRE, FOTO, TIPO_MIME
    FROM FOTOS
    WHERE ID_FOT IN ($placeholders)
      AND ID_EMPR = ?
      AND TIPO_MIME LIKE 'image/%'
";

// 🔹 Ejecutar consulta
$fotos = executeQuery($sql, $params);

if (empty($fotos)) {
    die("No se encontraron fotos para descargar.");
}

// 🔹 Crear archivo ZIP temporal
$zipFile = tempnam(sys_get_temp_dir(), 'fotos') . ".zip";
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
    die("No se pudo crear el archivo ZIP");
}

// 🔹 Agregar imágenes con nombre limpio
foreach ($fotos as $row) {
    $fileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $row['NOMBRE']); 
    $zip->addFromString($fileName, $row['FOTO']); // FOTO es el BLOB
}

$zip->close();

// 🔹 Forzar descarga del ZIP
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=fotos_catalogo.zip");
header("Content-Length: " . filesize($zipFile));
readfile($zipFile);

// 🔹 Eliminar archivo temporal
unlink($zipFile);
exit;
?>
