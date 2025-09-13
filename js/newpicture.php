<?php
require_once 'database.php';

// Protección modificada para permitir acceso directo solo a index.php
$archivo_actual = basename(__FILE__);
if ($archivo_actual == basename($_SERVER["SCRIPT_FILENAME"]) && $archivo_actual != 'newpicture.php') {
    die("Acceso denegado.");
}

$EMPR = "2";

// Configuración para archivos grandes
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('max_execution_time', '300');
ini_set('memory_limit', '256M');

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $youtube_url = $_POST['youtube_url'] ?? null;
    
    // Validar si es enlace de YouTube o subida de archivo
    if (!empty($youtube_url)) {
        // Validar enlace de YouTube
        if (esEnlaceYouTubeValido($youtube_url)) {
            if (urlEstaActiva($youtube_url)) {
                if (esVideoYouTube($youtube_url)) {
                    $url_embed = $youtube_url;
                    $youtube_url = 'YOUTUBE.COM';
                    try {
                        $sql = "INSERT INTO FOTOS (ID_EMPR, NOMBRE, FOTO, TIPO_MIME, URL_VIDEO, FECHA_ALTA, HORA_ALTA, USER_NEW_DATA) 
                                VALUES (?, ?, NULL, 'video/webm', ?, CURDATE(), CURTIME(), '1')";
                        executeQuery($sql, [$EMPR, $youtube_url, $url_embed]);
                        $mensaje = "✅ Enlace de YouTube guardado correctamente!";
                    } catch (Exception $e) {
                        $error = "❌ Error al guardar en BD: " . $e->getMessage();
                    }              
                } else {
                    $error = "❌ El enlace no es un video de YouTube válido";
                }
            } else {
                $error = "❌ El enlace no es un video de YouTube válido";
            }            
        } else {
            $error = "❌ El enlace no es un video de YouTube válido";
        }
    } elseif (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        // Proceso para subir archivo multimedia
        $nombre = $_FILES['imagen']['name'];
        $tipo_mime = $_FILES['imagen']['type'];
        $temp_path = $_FILES['imagen']['tmp_name'];
        
        // Validar tipo de archivo
        $tipos_permitidos = [
            'image/jpeg', 'image/png', 'image/gif',
            'video/mp4', 'video/webm', 'video/quicktime'
        ];
        
        if (in_array($tipo_mime, $tipos_permitidos)) {
            try {
                $contenido = file_get_contents($temp_path);
                $sql = "INSERT INTO FOTOS (ID_EMPR, NOMBRE, FOTO, TIPO_MIME, URL_VIDEO, FECHA_ALTA, HORA_ALTA, USER_NEW_DATA) 
                        VALUES (?, ?, ?, ?, '-', CURDATE(), CURTIME(), '1')";
                executeQuery($sql, [$EMPR, $nombre, $contenido, $tipo_mime]);
                $mensaje = "✅ Archivo subido correctamente! ID: " . getLastInsertId();
            } catch (Exception $e) {
                $error = "❌ Error al subir archivo: " . $e->getMessage();
            }
        } else {
            $error = "❌ Tipo de archivo no permitido. Formatos aceptados: JPEG, PNG, GIF, MP4, WEBM";
        }
    } else {
        $error_code = $_FILES['imagen']['error'] ?? 'N/A';
        $error = "❌ Error al subir archivo (Código: $error_code). " . getUploadError($error_code);
    }
}

// Funciones auxiliares
function esEnlaceYouTubeValido($url) {
    return filter_var($url, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $url);
}
// Función para verificar si la URL está activa
function urlEstaActiva($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpCode >= 200 && $httpCode < 400);
}
function esVideoYouTube($url) {
    // Patrones para URLs de YouTube
    $patrones = [
        '/^https?:\/\/(?:www\.|m\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
        '/^https?:\/\/(?:www\.|m\.)?youtu\.be\/([a-zA-Z0-9_-]{11})/',
        '/^https?:\/\/(?:www\.|m\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
        '/^https?:\/\/(?:www\.|m\.)?youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
        '/^https?:\/\/(?:www\.|m\.)?youtube\.com\/live\/([a-zA-Z0-9_-]{11})/'
    ];
    
    foreach ($patrones as $patron) {
        if (preg_match($patron, $url, $matches)) {
            // Verificar que el ID del video tenga exactamente 11 caracteres
            return strlen($matches[1]) === 11;
        }
    }
    
    return false;
}

function getUploadError($code) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño permitido',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño del formulario',
        UPLOAD_ERR_PARTIAL => 'El archivo solo se subió parcialmente',
        UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir en disco',
        UPLOAD_ERR_EXTENSION => 'Subida detenida por extensión'
    ];
    return $errors[$code] ?? 'Error desconocido';
}

function getLastInsertId() {
    $conn = getDBConnection();
    return $conn->lastInsertId();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/guatemosslogo.ico">
    <title>Subir Miscelanea</title>
    <script src="js/codexone.js"></script>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="fontawesome/css/all.min.css">
    <link rel="stylesheet" href="css/templatemo-style.css">
</head>
<body>
    <header>
        <h1>Agregar Nuevo Contenido</h1>
    </header>
    
    
    
    <main class="form-container">
        <?php if ($mensaje): ?>
            <div class="mensaje exito"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form action="newpicture.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="imagen">Subir archivo (imagen o video):</label>
                <input type="file" id="imagen" name="imagen" accept="image/*,video/*">
                <small>Formatos aceptados: JPG, PNG, GIF, MP4, WEBM (Máx. 25MB)</small>
            </div><br><br>
            
            <div class="separador">O</div><br><br>
            
            <div class="form-group">
                <label for="youtube_url">Enlace de YouTube:</label>
                <input type="text" id="youtube_url" name="youtube_url" 
                       placeholder="Ej: https://youtu.be/dQw4w9WgXcQ">
                <small>Ejemplos válidos: youtu.be/ID o youtube.com/watch?v=ID</small>
            </div><br><br><br>
            
            <button type="submit" class="btn-submit">Guardar Contenido</button><br><br>
        </form>
    </main>
    
    <footer class="tm-bg-gray pt-5 pb-3 tm-text-gray tm-footer">
        <div class="container-fluid tm-container-small">
            
            <div class="row">
                <div class="col-lg-8 col-md-7 col-12 px-5 mb-3"> &copy; <?php echo date('Y'); ?> 
                     Catálogo de GUATE MOSS S.A. Todos los derechos reservados.
                </div>
                <div class="col-lg-4 col-md-5 col-12 px-5 text-right">
                    <a href="https://templatemo.com" class="tm-text-gray" rel="sponsored" target="_parent"></a>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="codexone.js"></script>
</body>
</html>
