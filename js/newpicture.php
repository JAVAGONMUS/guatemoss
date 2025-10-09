<?php
session_start();

require_once 'database.php';

// Protección modificada para permitir acceso directo solo a newpicture.php
$archivo_actual = basename(__FILE__);
if ($archivo_actual == basename($_SERVER["SCRIPT_FILENAME"]) && $archivo_actual != 'newpicture.php') {
    die("Acceso denegado.");
}





$mensaje_error = "";

// Configuración
$password_correcto = "guatemoss"; // Cambia por tu contraseña
$max_intentos = 3; // Límite de intentos
$bloqueo_tiempo = 120; // 2 minutos de bloqueo en segundos
// Verificar si está bloqueado por muchos intentos
if (isset($_SESSION['intentos_fallidos']) && 
    $_SESSION['intentos_fallidos'] >= $max_intentos &&
    time() - $_SESSION['ultimo_intento'] < $bloqueo_tiempo) {
    
    $tiempo_restante = $bloqueo_tiempo - (time() - $_SESSION['ultimo_intento']);
    die("Demasiados intentos fallidos. Espere " . ceil($tiempo_restante/60) . " minutos.");
}

// Verificar si ya está autenticado
if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
    mostrarPanelAdmin();
    exit();
}

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    
    if ($_POST['password'] === $password_correcto) {
        // Contraseña correcta
        $_SESSION['autenticado'] = true;
        $_SESSION['intentos_fallidos'] = 0;
        $_SESSION['ultimo_intento'] = time();
        mostrarPanelAdmin();
        exit();
    } else {
        // Contraseña incorrecta
        $_SESSION['intentos_fallidos'] = isset($_SESSION['intentos_fallidos']) ? 
            $_SESSION['intentos_fallidos'] + 1 : 1;
        $_SESSION['ultimo_intento'] = time();
        $mensaje_error = "Contraseña incorrecta. Intento " . $_SESSION['intentos_fallidos'] . " de $max_intentos";
    }
}






// Mostrar formulario de login
mostrarFormularioLogin($mensaje_error ?? '');

// Función para mostrar el formulario de login
function mostrarFormularioLogin($error = '') {       
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Administrador</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .login-container {
                background: white;
                padding: 2rem;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                width: 300px;
            }
            .login-container h2 {
                text-align: center;
                color: #333;
                margin-bottom: 1.5rem;
            }
            .form-group {
                margin-bottom: 1rem;
            }
            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                color: #555;
            }
            .form-group input {
                width: 100%;
                padding: 0.5rem;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-sizing: border-box;
            }
            .btn {
                width: 100%;
                padding: 0.75rem;
                background-color: #007bff;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 1rem;
            }
            .btn:hover {
                background-color: #0056b3;
            }
            .error {
                color: #dc3545;
                text-align: center;
                margin-bottom: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2>Acceso Administrador</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Acceder</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}
















// Función para mostrar el panel de administración
function mostrarPanelAdmin() {
    
    // Configuración para archivos grandes
    ini_set('upload_max_filesize', '50M');
    ini_set('post_max_size', '50M');
    ini_set('max_execution_time', '300');
    ini_set('memory_limit', '256M');

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


    $EMPR = "2"; 
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

    $HayArticulos = getDivisionesByEmpresa($EMPR);
    $HayTipos = getDepartamentosByEmpresa($EMPR);
    $HayMarcas = getCategoriasByEmpresa($EMPR);

    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="img/guatemosslogo.ico">
        <title>INVENTARIO</title>
        <link rel="stylesheet" href="../css/styls.css">        
    </head> 
    <body>
        <div class="logout">
            <a href="logout.php">Cerrar Sesión</a>
        </div>      

        <div class="container">
            
            <h1>GUATE MOSS S.A.</h1> 
            <h1>AGREGAR CONTENIDO AL CATALOGO</h1><br><br>

            <form id="productForm" enctype="multipart/form-data" method="POST" action="newpicture.php" >
                <h2>ARCHIVO (imagen o video del producto):</h2>
                <!-- Selector de archivos múltiple con vista previa -->
                <div class="form-group">
                    <label for="fileUpload">FOTOGRAFIAS DEL PRODUCTO</label>
                    <div id="fileInputsContainer">
                        <div class="file-input-container">
                            <input type="file" id="imagen" name="imagen" accept="image/*" class="file-input">
                            <small>formatos aceptados: JPG, PNG, GIF, WEBM (Máx. 20MB)</small>
                            <div class="preview-container">
                                <div class="image-preview"></div>
                                <label class="main-image-label">
                                    <input type="radio" name="mainImage" value="0" class="main-image-radio"> CARATULA/PORTADA
                                </label>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="addFileBtn" class="add-youtube-btn">mas fotos..</button>
                    <div id="fileError" class="error-message"></div>
                </div><br>

                <!-- URLs de YouTube -->
                <div class="form-group">            
                    <label for="youtube_url">ENLACE DE YOUTUBE</label>
                    <div id="youtubeUrlsContainer">
                        <div class="youtube-url-container">
                            <input type="url" id="youtube_url" name="youtube_url" class="youtube-url-input" placeholder="https://www.youtube.com/watch?v=...">
                        </div>
                    </div>
                    <button type="button" id="addYoutubeUrlBtn" class="add-youtube-btn">mas videos..</button>
                    <div id="youtubeUrlError" class="error-message"></div>
                </div><br><br><br>

                <h2>DATOS (informacion del producto):</h2>


                
                

                <!-- Selectores de categorías -->
                <div class="flex-container">
                    <div class="form-group flex-item">
                        <label for="division">PRODUCTOS</label>
                        <select id="division" name="division" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($HayArticulos as $articulo):?>
                                <option value="<?php echo $articulo['ID_DIV']; ?>">
                                    <?php echo htmlspecialchars($articulo['NOMBRE']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="divisionError" class="error-message"></div>
                    </div>
                    
                    <div class="form-group flex-item">
                        <label for="department">DISEÑOS</label>
                        <select id="department" name="department" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($HayTipos as $tipo): ?>
                                <option value="<?php echo $tipo['ID_DEP']; ?>">
                                    <?php echo htmlspecialchars($tipo['NOMBRE']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="departmentError" class="error-message"></div>
                    </div>
                    
                    <div class="form-group flex-item">
                        <label for="category">MARCAS</label>
                        <select id="category" name="category" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($HayMarcas as $marca): ?>
                                <option value="<?php echo $marca['ID_CAT']; ?>">
                                    <?php echo htmlspecialchars($marca['NOMBRE']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="categoryError" class="error-message"></div>
                    </div>
                </div>

                <!-- El campo UPC ya no se muestra al usuario -->
                <input type="hidden" id="upcCode" name="upcCode">













                
                

                
                <!-- Campos de datos del producto -->
                <div class="form-group">
                    <label for="description">DESCRIPCION DEL PRODUCTO</label>
                    <input type="text" id="description" name="description" required>
                    <div id="descriptionError" class="error-message"></div>
                </div>

                <div class="flex-container">
                        <div class="form-group flex-item">
                            <label for="model">MODELO DEL PRODUCTO</label>
                            <input type="text" id="model" name="model" required>
                            <div id="modelError" class="error-message"></div>
                        </div>
                        
                        <div class="form-group flex-item">
                            <label for="color">COLOR DEL PRODUCTO</label>
                            <input type="text" id="color" name="color" required>
                            <div id="colorError" class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="status">ESTADO DEL PRODUCTO (1-10)</label>
                            <input type="number" id="status" name="status" min="1" max="10" required class="status-input">
                            <div id="statusError" class="error-message"></div>
                        </div>
                </div>

                <div class="flex-container">
                    <div class="form-group flex-item">
                        <label for="retailUnits">UNIDADES DE VENTA MINORISTA</label>
                        <input type="number" id="retailUnits" name="retailUnits" min="1" required>
                        <div id="retailUnitsError" class="error-message"></div>
                    </div>
                    
                    <div class="form-group flex-item">
                        <label for="wholesaleUnits">UNIDADES DE VENTA MAYORISTA</label>
                        <input type="number" id="wholesaleUnits" name="wholesaleUnits" min="1" required>
                        <div id="wholesaleUnitsError" class="error-message"></div>
                    </div>
                </div>

                <div class="flex-container">
                    <div class="form-group flex-item">
                        <label for="standardPrice">PRECIO ESTANDAR * UNIDAD</label>
                        <input type="number" id="standardPrice" name="standardPrice" step="0.01" min="0" required>
                        <div id="standardPriceError" class="error-message"></div>
                    </div>
                    
                    <div class="form-group flex-item">
                        <label for="offerPrice">PRECIO OFERTA * UNIDAD</label>
                        <input type="number" id="offerPrice" name="offerPrice" step="0.01" min="0">
                        <div id="offerPriceError" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="wholesalePrice">PRECIO PARA MAYOREO</label>
                        <input type="number" id="wholesalePrice" name="wholesalePrice" step="0.01" min="0">
                        <div id="wholesalePriceError" class="error-message"></div>
                    </div>
                </div>

                <button type="submit" class="add-file-btn">CREAR PRODUCTO</button>
            </form><br><br>

            <div class="row">
                <div class="col-lg-8 col-md-7 col-12 px-5 mb-3"> &copy; <?php echo date('Y'); ?> 
                    Catálogo de GUATE MOSS S.A. Todos los derechos reservados.
                </div>
                <div class="col-lg-4 col-md-5 col-12 px-5 text-right">
                    <a href="https://templatemo.com" class="tm-text-gray" rel="sponsored" target="_parent"></a>
                </div>
            </div>

        </div><br>

        <div class="container">            

            <?php if ($mensaje): ?>
                <div class="mensaje exito"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>            
            <?php if ($error): ?>
                <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

        </div>

        <script src="codexone.js"></script>
        <script src="codexthree.js"></script>
    </body>
    </html>
    <?php
}


?>
