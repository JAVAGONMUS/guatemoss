<?php
require_once 'js/database.php';

// Protección modificada para permitir acceso directo solo a index.php
$archivo_actual = basename(__FILE__);
if ($archivo_actual == basename($_SERVER["SCRIPT_FILENAME"]) && $archivo_actual != 'index.php') {
    die("Acceso denegado.");
}

$EMPR = 2;
$existenciasP = false;

$productos = getAllProductos($EMPR);                        
// Cantidad de productos por página (puedes cambiarlo a 10, 12, etc.)
$productosPorPagina = 12;
// Página actual (por defecto la 1)
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;

// Calcular desde qué registro empezar
$offset = ($pagina - 1) * $productosPorPagina;

// Consultar el total de productos
$totalProductos = SaberMaximoCatalogo($EMPR);

// Calcular total de páginas
$totalPaginas = ceil($totalProductos / $productosPorPagina);

// Traer solo los productos de la página actual
$query = MostrarSoloPagina($offset, $productosPorPagina,$EMPR);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/guatemosslogo.ico">
    <meta name="keywords" content="Guate Moss, GUATE MOSS, guate moss, GUATEMOSS, guatemoss, Guatemoss, gUATEMOSS, gUATE mOSS, GuateMoss">
	<meta name="description" content="GUATE MOSS S.A. SE PONE A SUS ORDENES CON PRODUCTOS EXCLUSIVOS, VENDEMOS POR MAYOR Y POR UNIDAD.">
	<meta name="author" content="GUATE MOSS S.A.">
	<meta name="copyright" content="GUATE MOSS S.A.">
	<meta name="robots" content="index">
    <title>GUATE MOSS</title>
    <script src="js/codexone.js"></script>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="fontawesome/css/all.min.css">
    <link rel="stylesheet" href="css/templatemo-style.css">
<!--
    
TemplateMo 556 Catalog-Z

https://templatemo.com/tm-556-catalog-z

-->
</head>
<body>
    <!-- Page Loader -->
    <div id="loader-wrapper">
        <div id="loader"></div>

        <div class="loader-section section-left"></div>
        <div class="loader-section section-right"></div>

    </div>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-film mr-2"></i>
                CATALOGO GUATE MOSS S.A.
            </a>
            <ul class="navbar-nav ml-auto mb-2 mb-lg-0">
                <button id="btnWhatsApp">💬 Escríbenos</button>                
            </ul>
            
        </div>
    </nav>

    <div class="tm-hero d-flex justify-content-center align-items-center" id="tm-video-container"   >
        <video autoplay muted loop id="tm-video">
            <source src="video/hero.mp4" type="video/mp4">
        </video>  
        <i id="tm-video-control-button" class="fas fa-pause"></i>

        <form action="js/buscar.php" method="get" class="d-flex position-absolute tm-search-form">
            <div class="form-group">
                <input class="form-control" type="text" id="articulo" name="articulo" placeholder="PRODUCTOS"> 
            </div>  
            <div class="form-group">                 
                <input class="form-control" type="text" id="marca" name="marca" placeholder="MARCAS">
            </div>  
            <button class="form-group" type="submit">
                <i class="fas fa-search"></i>
            </button>
        </form>
        
    </div>



    
        
    





    <div class="container-fluid tm-container-content tm-mt-60">
        <div class="row mb-4">
            <h2 class="col-6 tm-text-primary">
                PRODUCTOS
            </h2>
            <div class="col-6 d-flex justify-content-end align-items-center">
                <form action="" class="tm-text-primary">
                    Pagina <input type="text" value="<?php echo $pagina ?>" size="1" class="tm-input-paging tm-text-primary" readonly> de <?php echo $totalPaginas; ?>
                </form>
            </div>
        </div>




        <div class="row tm-mb-90 tm-gallery">
            <?php foreach ($query as $producto): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 mb-5">           
                    <figure class="effect-ming tm-video-item">
                        <?php 
                            $imagenes = getImagesByIds([$producto['ID_FOT']]);
                            if (!empty($imagenes)) {
                                $imagen = $imagenes[0];
                                $src = 'data:' . $imagen['TIPO_MIME'] . ';base64,' . base64_encode($imagen['FOTO']);
                                    
                                if (strpos($imagen['TIPO_MIME'], 'video/') === 0) {
                                    
                                } else {
                                    echo '<img src="' . $src . '" alt="Image" class="img-fluid">';
                                }
                            }
                        ?>         
                        <figcaption class="d-flex align-items-center justify-content-center">
                            <h2>INFORMACION</h2>
                            <a href="photo-detail.php?id=<?php echo $producto['ID_CATT']; ?>">View more</a>
                        </figcaption>                    
                    </figure>
                    <div class="d-flex justify-content-between tm-text-gray">
                        <span><?php echo htmlspecialchars($producto['DEPARTAMENTO']); ?>,<?php echo htmlspecialchars($producto['CATEGORIA']); ?></span>
                        <span class="tm-text-gray-light">Q<?php echo number_format($producto['PRECIO_OFERTA'], 2); ?> * UNIDAD</span>
                    </div>            
                </div>   
            <?php endforeach; ?>
        </div> <!-- row -->


        <!-- Paginación -->
        <div class="row tm-mb-90">
            <div class="col-12 d-flex justify-content-between align-items-center tm-paging-col">
                <!-- Botón Anterior -->
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?php echo $pagina - 1; ?>" class="btn btn-primary tm-btn-prev mb-2 eneable">⟨ ANTERIOR</a>                   
                <?php else: ?>
                    <a class="btn btn-primary tm-btn-prev mb-2 disabled">⟨ ANTERIOR</a>
                <?php endif; ?>

                <!-- Números de página -->
                <div class="tm-paging d-flex">
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <a href="?pagina=<?php echo $i; ?>" class="<?php echo ($i == $pagina) ? 'active tm-paging-link' : 'tm-paging-link'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>                       
                </div>
                

                <!-- Botón Siguiente -->
                <?php if ($pagina < $totalPaginas): ?>
                    <a href="?pagina=<?php echo $pagina + 1; ?>" class="btn btn-primary tm-btn-next eneable ">SIGUIENTE ⟩</a>                   
                <?php else: ?>
                    <a class="btn btn-primary tm-btn-next disabled ">SIGUIENTE ⟩</a>   
                <?php endif; ?>
            </div>  
        </div>

    </div> <!-- container-fluid, tm-container-content -->








    <footer class="tm-bg-gray pt-5 pb-3 tm-text-gray tm-footer">
        <div class="container-fluid tm-container-small">
            <div class="row">
                <div class="col-lg-6 col-md-12 col-12 px-5 mb-5">
                    <h3 class="tm-text-primary mb-4 tm-footer-title"></h3>
                    <p> <a rel="sponsored" href="https://v5.getbootstrap.com/"></a> GUATE MOSS S.A. TE TRAE LO MEJOR EN ACCESORIOS, TENEMOS UNA VARIEDAD DE DISEÑOS, VENTA POR MAYOR Y MENOR. ENTREGAS EN TODO EL PAIS Y PAGO CONTRA ENTREGA.</p>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12 px-5 mb-5">
                    <h3 class="tm-text-primary mb-4 tm-footer-title"></h3>
                    <ul class="tm-footer-links pl-0">
                        <li><a href="#"></a></li>
                        <li><a href="#"></a></li>
                        <li><a href="#"></a></li>
                        <li><a href="#"></a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12 px-5 mb-5">
                    <ul class="tm-social-links d-flex justify-content-end pl-0 mb-5">
                        <li class="mb-2"><a href="https://www.facebook.com/profile.php?id=100093685280633" target="_blank"><i class="fab fa-facebook"></i></a></li>
                        
                        <li class="mb-2"><a href="https://www.tiktok.com/@guatemos" target="_blank"><i class="fab fa-instagram"></i></a></li>
                        
                    </ul>
                    <a href="#" class="tm-text-gray text-right d-block mb-2"></a>
                    <a href="#" class="tm-text-gray text-right d-block"></a>
                </div>
            </div>
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
    
    <script src="js/plugins.js"></script>
    <script>
        $(window).on("load", function() {
            $('body').addClass('loaded');
        });
    </script>
</body>
</html>
