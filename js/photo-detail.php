<?php
require_once 'database.php';

// Protección modificada para permitir acceso directo solo a index.php
$archivo_actual = basename(__FILE__);
if ($archivo_actual == basename($_SERVER["SCRIPT_FILENAME"]) && $archivo_actual != 'photo-detail.php') {
    die("Acceso denegado.");
}
$EMPR = 2;

// Se  recibe el ID del producto actual buscado
//$idCatt = isset($_GET['id']) ? (int)$_GET['id'] : 1;
//if ($idCatt < 1) $idCatt = 0;

// Validación de seguridad
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("HTTP/1.1 400 Solicitud incorrecta");
    die("ID de producto no válido");
}

$id = intval($_GET['id']);
$producto = getProductoById($id);
if (!$producto) {
    header("HTTP/1.1 404 No encontrado");
    die("Producto no encontrado");
}
//  AQUI SE GUARDAN LAS FOTOS POR SI DESEAN DESCARGAR
$FullPic = "";

// Obtener multimedia relacionado
$ids_fotos = explode(',', $producto['ID_FOTT']);
// Función para determinar el tipo de contenido
function obtenerTipoContenido($imagen) {
    if ($imagen['URL_VIDEO'] !== '-') {
        return 'youtube';
    } elseif (strpos($imagen['TIPO_MIME'], 'image/') === 0) {
        return 'imagen';
    } elseif (strpos($imagen['TIPO_MIME'], 'video/') === 0) {
        return 'video';
    }
    return 'desconocido';
}

function obtenerIdYoutube($url) {
    // Extrae el ID del video desde distintas variantes de URL
    $patron = '%(?:youtube\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
    preg_match($patron, $url, $coincidencias);
    return $coincidencias[1] ?? false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../img/guatemosslogo.ico">
    <meta name="keywords" content="Guate Moss, GUATE MOSS, guate moss, GUATEMOSS, guatemoss, Guatemoss, gUATEMOSS, gUATE mOSS, GuateMoss">
	<meta name="description" content="GUATE MOSS S.A. SE PONE A SUS ORDENES CON PRODUCTOS EXCLUSIVOS, VENDEMOS POR MAYOR Y POR UNIDAD.">
	<meta name="author" content="GUATE MOSS S.A.">
	<meta name="copyright" content="GUATE MOSS S.A.">
	<meta name="robots" content="index">
    <title>GUATE MOSS</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/templatemo-style.css">
</head>
<body style="background-color:  #080705;">
       
    <div class="tm-hero d-flex justify-content-center align-items-center" id="tm-video-container"   >
        <video autoplay muted loop id="tm-video">
            <source src="../video/hero.mp4" type="video/mp4">
        </video>  
        <i id="tm-video-control-button" class="fas fa-pause"></i>        
    </div> 
    <nav>
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-film mr-2"></i>
                CATALOGO GUATE MOSS S.A.
            </a>        
            <div class="social-buttons-container">
                <a href="https://www.facebook.com/profile.php?id=100093685280633" target="_blank" class="social-link facebook-link auto-hide-text" aria-label="Facebook">
                    <svg class="social-icon" viewBox="0 0 24 24" fill="white">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <span class="link-text">Facebook</span>
                </a>
                <a href="https://www.tiktok.com/@guatemos" target="_blank" class="social-link tiktok-link auto-hide-text" aria-label="TikTok">
                    <svg class="social-icon" viewBox="0 0 24 24" fill="white">
                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.10-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                    </svg>
                    <span class="link-text">TikTok</span>
                </a>
                <a id="btnWhatsApp" class="social-link whatsapp-link auto-hide-text" aria-label="WhatsApp">
                    <svg class="social-icon" viewBox="0 0 24 24" fill="white">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.864 3.488"/>
                    </svg>
                    <span class="link-text">WhatsApp</span>
                </a>
            </div>   
        </div>
    </nav>
    <div class="tm-hero d-flex justify-content-center align-items-center" >
        <form action="buscar.php" method="POST" class="d-flex position-absolute search-form" id="searchForm">
            <label for="articulo">Productos</label>
            <div class="form-group">                
                <input class="form-control" autocomplete="off" oninput="this.value = this.value.toUpperCase()" type="text" id="articulo" name="articulo" placeholder="cartera...">
            </div>
            <label for="marca">Marcas</label>
            <div class="form-group">                
                <input class="form-control" autocomplete="off" oninput="this.value = this.value.toUpperCase()" type="text" id="marca" name="marca" placeholder="coach...">
            </div>                        
            <button class="form-group" type="submit">
                <i class="fas fa-search"></i>
            </button>       
        </form>        
    </div>







    

    <div class="container-fluid tm-container-content tm-mt-60">
        <div class="row mb-4">
            <h2 class="col-12 tm-text-primary"><?php echo htmlspecialchars($producto['DIVISION']); ?> - <?php echo htmlspecialchars($producto['DEPARTAMENTO']); ?> - <?php echo htmlspecialchars($producto['CATEGORIA']);?></h2>
        </div>
        <div class="row tm-mb-90">            
            <div class="col-xl-8 col-lg-7 col-md-6 col-sm-12">
                <?php 
                    $imagenes = getImagesByIds([$producto['ID_FOT']]);
                    if (!empty($imagenes)) {
                        $imagen = $imagenes[0];
                        $src = 'data:' . $imagen['TIPO_MIME'] . ';base64,' . base64_encode($imagen['FOTO']);                            
                        if (strpos($imagen['TIPO_MIME'], 'video/') === 0) {
                            $videoID = obtenerIdYoutube($imagen['URL_VIDEO']);
                            if ($videoID) {
                                echo '<div class="contenedor-multimedia">';
                                echo '<iframe width="300" height="200" src="https://www.youtube.com/embed/' . htmlspecialchars($videoID) . '" frameborder="0" allowfullscreen></iframe>';
                                echo '</div>';
                            }

                        } else {
                            echo '<img src="' . $src . '" alt="Image" class="img-fluid">';
                            $FullPic = $producto['ID_FOTT'];
                        }
                    }else {
                        ?><div class="mensaje info">Este producto no tiene imágenes/videos asociados</div><?php                        
                    }
                ?> 
            </div>
            <div class="col-xl-4 col-lg-5 col-md-6 col-sm-12">
                <div class="tm-bg-gray tm-video-details" style="background-color:  #e4c4ffff;">   

                    <div>
                        <?php if (!empty($producto['DESCRIPCION'])): ?>
                            <div class="info-item full-width descripcion">
                                <h3 class="tm-text-gray-dark mb-3"><?php echo nl2br(htmlspecialchars($producto['DESCRIPCION'])); ?></h3>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <h3 class="tm-text-gray-dark mb-3">DETALLES:</h3>
                        <div class="info-item">
                            <span class="info-label">🏷️ Modelo:</span>
                            <span class="info-value"><?php echo htmlspecialchars($producto['MODELO']); ?></span>
                        </div>                        
                        <div class="info-item">
                            <span class="info-label">🎨 Color:</span>
                            <span class="info-value"><?php echo htmlspecialchars($producto['COLOR']); ?></span>
                        </div>                
                        <div class="info-item">
                            <span class="info-label">📋 Código:</span>
                            <span class="info-value"><?php echo htmlspecialchars($producto['UPC']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">📦 Estado:</span>
                            <span class="info-value"><?php echo htmlspecialchars($producto['ESTADO']); ?> de 10</span>
                        </div> 
                        <div class="info-item destacado">
                            <span class="info-label">💰 Precio/unidad:</span>
                            <span class="info-value precio">Q<?php echo number_format($producto['PRECIO_OFERTA'], 2); ?></span>
                        </div>
                        <div class="info-item destacado">
                            <span class="info-label">💰 Precio/mayor:</span>
                            <span class="info-value precio">Q<?php echo number_format($producto['PRECIO_MIN'], 2); ?></span>
                        </div>
                        <div class="info-item full-width">
                            <span class="info-label">🔄 --- </span>
                            <span class="info-value <?php echo $producto['VENDIDO'] ? 'vendido' : 'disponible'; ?>">
                                <?php echo $producto['VENDIDO'] ? '❌ AGOTADO' : '✅ DISPONIBLE'; ?>
                            </span>
                        </div>   
                    </div>

                    <div>
                        <br>
                    </div>



                        
                    <form action="descargar_fotos.php" method="post" class="text-center mb-5">
                        <input type="hidden" name="ids" value="<?php echo $FullPic; ?>">
                        <input type="hidden" name="id_empr" value="<?php echo $EMPR; ?>">
                        <button type="submit" class="btn btn-primary tm-btn-big">📥 Descargar fotos</button>
                    </form>

                    
                    






                </div>
            </div>
        </div>
        <div class="row mb-4">
            <h2 class="col-12 tm-text-primary">MAS PRODUCTO PARA TI 
            </h2>
        </div>







        <div class="row mb-3 tm-gallery">
            
            <?php $imagenes = getImagesByIds($ids_fotos);
            if (empty($imagenes)): ?>
                <div class="mensaje info">Este producto no tiene imágenes/videos asociados</div>
            <?php else: ?>
                <?php foreach ($imagenes as $imagen): ?>
                    <?php $tipo = obtenerTipoContenido($imagen);?>                    
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 mb-5">
                        <figure class="effect-ming tm-video-item">
                            <?php if ($tipo === 'youtube'): 

                                if (!empty($imagen['URL_VIDEO'])) {
                                    $videoID = obtenerIdYoutube($imagen['URL_VIDEO']);
                                    if ($videoID) {
                                        echo '<div class="contenedor-multimedia">';
                                        echo '<iframe width="300" height="200" src="https://www.youtube.com/embed/' . htmlspecialchars($videoID) . '" frameborder="0" allowfullscreen></iframe>';
                                        echo '</div>';
                                    }
                                }
                                
                            ?>    
                            <?php elseif ($tipo === 'imagen'): ?>
                                <!-- Imagen normal -->
                                <img src="data:<?php echo $imagen['TIPO_MIME']; ?>;base64,<?php echo base64_encode($imagen['FOTO']); ?>" 
                                        alt="<?php echo htmlspecialchars($imagen['NOMBRE']); ?>"
                                        class="img-preview"
                                        loading="lazy">
                                        
                            <?php elseif ($tipo === 'video'): ?>
                                <!-- Video subido directamente -->
                                <video controls class="video-thumbnail">
                                    <source src="data:<?php echo $imagen['TIPO_MIME']; ?>;base64,<?php echo base64_encode($imagen['FOTO']); ?>" 
                                            type="<?php echo $imagen['TIPO_MIME']; ?>">
                                    Tu navegador no soporta este formato de video.
                                </video>
                                
                            <?php else: ?>
                                <div class="mensaje error">Formato no soportado</div>
                            <?php endif; ?>
                               
                        </figure>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        
        </div> <!-- row -->

    </div> <!-- container-fluid, tm-container-content -->





    <footer class="tm-bg-gray pt-5 pb-3 tm-text-gray tm-footer">
        <div class="container-fluid tm-container-small">
            <div class="row">               
                <h3 class="tm-text-primary mb-4 tm-footer-title">GUATE MOSS S.A. TE TRAE LO MEJOR EN ACCESORIOS, TENEMOS UNA VARIEDAD DE DISEÑOS, VENTA POR MAYOR Y MENOR. ENTREGAS EN TODO EL PAIS Y PAGO CONTRA ENTREGA.</h3>
                <p> <a rel="sponsored" href="https://v5.getbootstrap.com/"></a>&copy; <?php echo date('Y'); ?> 
                     Catálogo de GUATE MOSS S.A. Todos los derechos reservados.</p>
                               
            </div>
        </div>
    </footer>
    
    <script src="plugins.js"></script>
    <script>
        $(window).on("load", function() {
            $('body').addClass('loaded');
        });
    </script>
    <script src="codexone.js"></script>
    <script src="codextwo.js"></script>
</body>
</html>
