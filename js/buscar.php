<?php
session_start(); // siempre la primera línea antes de cualquier HTML
require_once 'database.php';

// Protección modificada para permitir acceso directo solo a index.php
$archivo_actual = basename(__FILE__);
if ($archivo_actual == basename($_SERVER["SCRIPT_FILENAME"]) && $archivo_actual != 'buscar.php') {
    die("Acceso denegado.");
}
// Página actual (por defecto la 1)
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;

$BuscarArticulo;
$BuscarTipo;
$BuscarMarca;

if (isset($_POST['Search1'])) {
    $_SESSION['D3BuscarArticulo'] = "";
    $_SESSION['D4BuscarTipo'] = "";
    $_SESSION['D5BuscarMarca'] = "";
}

// comprobar si es recarga o avanzar la pagina, sino se toma como busqueda nueva
if ($_SESSION['D3BuscarArticulo'] > 0 || $_SESSION['D4BuscarTipo'] > 0 || $_SESSION['D5BuscarMarca'] > 0) {
    $BuscarArticulo = $_SESSION['D3BuscarArticulo'];
    $BuscarTipo = $_SESSION['D4BuscarTipo'];
    $BuscarMarca = $_SESSION['D5BuscarMarca'];
}else{
    // Obtener datos del POST del formulario
    $BuscarArticulo = isset($_POST['articulo']) ? trim($_POST['articulo']) : '';
    $BuscarTipo    = "@";//isset($_POST['tipoo']) ? trim($_POST['tipoo']) : '';
    $BuscarMarca    = isset($_POST['marca']) ? trim($_POST['marca']) : '';    
}

$BuscarIdArticulo = 0;
$BuscarIdTipo = 0;
$BuscarIdMarca = 0;

$EMPR = 2;
$existenciasP = false;

$HayArticulos = getDivisionesByEmpresa($EMPR);
$HayTipos = getDepartamentosByEmpresa($EMPR);
$HayMarcas = getCategoriasByEmpresa($EMPR);
// Calcular total de páginas
$totalPaginas = 0;
// Cantidad de productos por página (puedes cambiarlo a 10, 12, etc.)
$productosPorPagina = 8;
// Para obtener el total de productos
$totalProductos = 0;
// Calcular desde qué registro empezar
$offset = ($pagina - 1) * $productosPorPagina;

// Inicializar array de resultados
$resultados = [
    'tres_filtros' => [],
    'articulo_marca' => [],
    'solo_articulo' => [],
    'solo_marca' => []
];

$excluirIds = [];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
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
            <label for="articulo">Productos -</label>
            <div class="form-group">                
                <input class="form-control" autocomplete="off" oninput="this.value = this.value.toUpperCase()" type="text" id="articulo" name="articulo" placeholder="cartera...">
            </div>    
            <label for="marca">Marcas -</label>
            <div class="form-group">                
                <input class="form-control" autocomplete="off" oninput="this.value = this.value.toUpperCase()" type="text" id="marca" name="marca" placeholder="coach...">
            </div>     
            <button name="Search1" class="form-group" type="submit">
                <i class="fas fa-search"></i>
            </button>       
        </form>        
    </div>
    <?php
        //echo $totalPaginas," / ",$pagina," / ",$offset," / ",$BuscarArticulo," / ",$BuscarTipo," / ",$BuscarMarca; 
        //echo $BuscarArticulo," / ",$BuscarTipo," / ",$BuscarMarca," : ";
        $totalProductos = 0;
        // Mostrar divisiones
        foreach ($HayArticulos as $d) {
            if ($d['NOMBRE'] === $BuscarArticulo) {   
                $BuscarIdArticulo = $d['ID_DIV'];                           
            }    
        }
        // Mostrar departamentos
        foreach ($HayTipos as $t) {
            if ($t['NOMBRE'] === $BuscarTipo) {   
                $BuscarIdTipo = $t['ID_DEP'];                           
            }    
        }
        // Mostrar categorías
        foreach ($HayMarcas as $c) {
            if ($c['NOMBRE'] === $BuscarMarca) {
                $BuscarIdMarca = $c['ID_CAT'];                
            } 
        }     
        //echo "RESULTADOS: ",$BuscarIdArticulo," - ",$BuscarIdTipo," - ",$BuscarIdMarca;

        
        // 1. Búsqueda con los tres filtros
        if ($BuscarIdArticulo > 0 && $BuscarIdTipo > 0 && $BuscarIdMarca > 0) {
            $filtros = [
                'id_cat' => $BuscarIdMarca,
                'id_dep' => $BuscarIdTipo,
                'id_div' => $BuscarIdArticulo
            ];
            
            $resultados['tres_filtros'] = buscarProductosFiltrados($filtros, $excluirIds);
            
            // Actualizar IDs a excluir
            if (!empty($resultados['tres_filtros'])) {
                $idsEncontrados = array_column($resultados['tres_filtros'], 'ID_CATT');
                $idsEncontrados = array_map('intval', $idsEncontrados); // Convertir a enteros
                $excluirIds = array_merge($excluirIds, $idsEncontrados);
            }
        }

        // 2. Búsqueda con artículo y marca (sin tipo)
        if ($BuscarIdArticulo > 0 && $BuscarIdMarca > 0) {
            $filtros = [
                'id_cat' => $BuscarIdMarca,
                'id_dep' => null,
                'id_div' => $BuscarIdArticulo
            ];
            
            $resultados['articulo_marca'] = buscarProductosFiltrados($filtros, $excluirIds);
            
            // Actualizar IDs a excluir
            if (!empty($resultados['articulo_marca'])) {
                $idsEncontrados = array_column($resultados['articulo_marca'], 'ID_CATT');
                $idsEncontrados = array_map('intval', $idsEncontrados); // Convertir a enteros
                $excluirIds = array_merge($excluirIds, $idsEncontrados);
            }
        }

        // 3. Búsqueda solo con artículo
        if ($BuscarIdArticulo > 0) {
            $filtros = [
                'id_cat' => null,
                'id_dep' => null,
                'id_div' => $BuscarIdArticulo
            ];
            
            $resultados['solo_articulo'] = buscarProductosFiltrados($filtros, $excluirIds);
            
            // Actualizar IDs a excluir
            if (!empty($resultados['solo_articulo'])) {
                $idsEncontrados = array_column($resultados['solo_articulo'], 'ID_CATT');
                $idsEncontrados = array_map('intval', $idsEncontrados); // Convertir a enteros
                $excluirIds = array_merge($excluirIds, $idsEncontrados);
            }
        }

        // 4. Búsqueda solo con marca
        if ($BuscarIdMarca > 0) {
            $filtros = [
                'id_cat' => $BuscarIdMarca,
                'id_dep' => null,
                'id_div' => null
            ];
            
            $resultados['solo_marca'] = buscarProductosFiltrados($filtros, $excluirIds);
        }       
        // Mostrar resultados
        //echo "<h2>Resultados de Búsqueda</h2>";
        // Resumen total
        $totalProductos =   count($resultados['tres_filtros']) + 
                            count($resultados['articulo_marca']) + 
                            count($resultados['solo_articulo']) + 
                            count($resultados['solo_marca']);
                        
        //echo "<h3>Total de productos encontrados: $totalProductos</h3>";
        // Calcular total de páginas
        $totalPaginas = ceil($totalProductos / $productosPorPagina);
        //debugBusqueda($resultados);
    ?>
    <div class="container-fluid tm-container-content tm-mt-60" >
        <?php if ($BuscarIdArticulo === 0 && $BuscarIdMarca === 0): ?>            
            <div class="row mb-4">
                <div class="col-6 d-flex justify-content-end align-items-center">
                    <form action="" class="tm-text-primary">
                        Pagina <input type="text" value="<?php echo 0 ?>" size="1" class="tm-input-paging tm-text-primary" readonly> de <?php echo 0 ?>
                    </form>
                </div>
            </div>  
            <p class="no-resultados">1.0 No se encontraron productos con los datos solicitados.</p>
        <?php else: ?>
            <div class="row mb-4">
                <h2 class="col-6 tm-text-primary">
                    ARTICULOS DISPONIBLES
                </h2>
                <div class="col-6 d-flex justify-content-end align-items-center">
                    <form action="" class="tm-text-primary">
                        Pagina <input type="text" value="<?php echo $pagina ?>" size="1" class="tm-input-paging tm-text-primary" readonly> de <?php echo $totalPaginas; ?>
                    </form>
                </div>
                <?php if ($BuscarMarca || $BuscarArticulo): ?>
                    <p class="filtros">
                        <?php if ($BuscarArticulo) echo " Producto= $BuscarArticulo";?>
                        <?php if ($BuscarArticulo && $BuscarMarca) echo " | ";?>
                        <?php if ($BuscarMarca) echo " Marca= $BuscarMarca";?>
                    </p>
                <?php endif;?>            
            </div>
            <div class="row tm-mb-90 tm-gallery">
                <?php  
                    $limite = $offset+$productosPorPagina;
                    $inicio = $offset;
                    $contador = 0;
                    // 1. Resultados con tres filtros
                    //echo "<h3>Productos con los tres filtros (" . count($resultados['tres_filtros']) . "):</h3>";
                    //echo "inicio",$inicio," limite",$limite;
                    foreach ($resultados['tres_filtros'] as $producto) {                        
                        // Verificar si se alcanzó el límite máximo
                        if ($contador >= $inicio + $limite) {
                            //echo "ME SALGO AL NUMERO ",$contador;
                            break;
                        }
                        // Saltar los primeros elementos hasta llegar al punto de inicio
                        if ($contador >= $inicio && $contador < $limite) {?> 
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
                                    <span><?php echo htmlspecialchars($producto['DIVISION']); ?>,<?php echo htmlspecialchars($producto['CATEGORIA']); ?></span>
                                    <span class="tm-text-gray-light">Q<?php echo number_format($producto['PRECIO_OFERTA'], 2); $existenciasP = true;?> * UNIDAD</span>
                                </div>            
                            </div>  
                        <?php
                        } 
                        $contador=$contador+1;
                    }
                    // 2. Resultados con artículo y marca
                    //echo "<h3>Productos con artículo y marca (" . count($resultados['articulo_marca']) . "):</h3>";
                    //echo "inicio",$inicio," limite",$limite;
                    foreach ($resultados['articulo_marca'] as $producto) {                        
                        // Verificar si se alcanzó el límite máximo
                        if ($contador >= $inicio + $limite) {
                            //echo "ME SALGO AL NUMERO ",$contador;
                            break;
                        }
                        // Saltar los primeros elementos hasta llegar al punto de inicio
                        if ($contador >= $inicio && $contador < $limite) {?> 
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
                                    <span><?php echo htmlspecialchars($producto['DIVISION']); ?>,<?php echo htmlspecialchars($producto['CATEGORIA']); ?></span>
                                    <span class="tm-text-gray-light">Q<?php echo number_format($producto['PRECIO_OFERTA'], 2); $existenciasP = true;?> * UNIDAD</span>
                                </div>            
                            </div>  
                        <?php
                        } 
                        $contador=$contador+1;
                    }
                    // 3. Resultados solo con artículo
                    //echo "<h3>Productos solo con artículo (" . count($resultados['solo_articulo']) . "):</h3>";
                    //echo "inicio",$inicio," limite",$limite;
                    foreach ($resultados['solo_articulo'] as $producto) {                        
                        // Verificar si se alcanzó el límite máximo
                        if ($contador >= $inicio + $limite) {
                            //echo "ME SALGO AL NUMERO ",$contador;
                            break;
                        }
                        // Saltar los primeros elementos hasta llegar al punto de inicio
                        if ($contador >= $inicio && $contador < $limite) {?> 
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
                                    <span><?php echo htmlspecialchars($producto['DIVISION']); ?>,<?php echo htmlspecialchars($producto['CATEGORIA']); ?></span>
                                    <span class="tm-text-gray-light">Q<?php echo number_format($producto['PRECIO_OFERTA'], 2); $existenciasP = true;?> * UNIDAD</span>
                                </div>            
                            </div>  
                        <?php
                        } 
                        $contador=$contador+1;
                    }
                    // 4. Resultados solo con marca
                    //echo "<h3>Productos solo con marca (" . count($resultados['solo_marca']) . "):</h3>";
                    //echo "inicio",$inicio," limite",$limite;
                    foreach ($resultados['solo_marca'] as $producto) {                        
                        // Verificar si se alcanzó el límite máximo
                        if ($contador >= $inicio + $limite) {
                            //echo "ME SALGO AL NUMERO ",$contador;
                            break;
                        }
                        // Saltar los primeros elementos hasta llegar al punto de inicio
                        if ($contador >= $inicio && $contador < $limite) {?> 
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
                                    <span><?php echo htmlspecialchars($producto['DIVISION']); ?>,<?php echo htmlspecialchars($producto['CATEGORIA']); ?></span>
                                    <span class="tm-text-gray-light">Q<?php echo number_format($producto['PRECIO_OFERTA'], 2); $existenciasP = true;?> * UNIDAD</span>
                                </div>            
                            </div>  
                        <?php
                        } 
                        $contador=$contador+1;
                    }
                ?>
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

            <?php if (!$existenciasP) {?>
                <div class="row mb-4">
                    <div class="col-6 d-flex justify-content-end align-items-center">
                        <form action="" class="tm-text-primary">
                            Pagina <input type="text" value="<?php echo 0 ?>" size="1" class="tm-input-paging tm-text-primary" readonly> de <?php echo 0 ?>
                        </form>
                    </div>
                </div> 
                <p class="no-resultados">2.0 No se encontraron productos con los datos solicitados.</p>
            <?php } ?>
        <?php endif; ?>
    </div> <!-- container-fluid, tm-container-content -->

    <!-- Paginación del SISTEMA -->
    <?php
        $_SESSION['D3BuscarArticulo'] = $BuscarArticulo;
        $_SESSION['D4BuscarTipo'] = $BuscarTipo;
        $_SESSION['D5BuscarMarca'] = $BuscarMarca;
    ?>

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
