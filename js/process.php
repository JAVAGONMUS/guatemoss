<?php
// process.php

// Incluir conexión a la base de datos
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar los datos del formulario
    
    // Manejo de archivos
    $uploadedFiles = $_FILES['productImages'];
    $filePaths = [];
    
    // Obtener la imagen principal
    $mainImageIndex = $_POST['mainImage'] ?? 0;
    
    // Crear directorio de uploads si no existe
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Procesar cada archivo
    for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
        $fileName = $uploadedFiles['name'][$i];
        $fileTmpName = $uploadedFiles['tmp_name'][$i];
        $fileError = $uploadedFiles['error'][$i];
        
        // Si no hay error y el archivo existe
        if ($fileError === UPLOAD_ERR_OK) {
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid() . '.' . $fileExtension;
            $fileDestination = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                $filePaths[] = [
                    'path' => $fileDestination,
                    'is_main' => ($i == $mainImageIndex)
                ];
            } else {
                // Manejar error de subida para este archivo
                error_log("Error al subir el archivo: $fileName");
            }
        } else {
            // Manejar error de archivo
            error_log("Error en el archivo: $fileName - Código de error: $fileError");
        }
    }
    
    // Recoger URLs de YouTube
    $youtubeUrls = $_POST['youtubeUrls'] ?? [];
    // Filtrar URLs vacías
    $youtubeUrls = array_filter($youtubeUrls);
    
    // Recoger otros datos del formulario
    $description = $_POST['description'];
    $standardPrice = $_POST['standardPrice'];
    $offerPrice = $_POST['offerPrice'] ?? null;
    $wholesalePrice = $_POST['wholesalePrice'] ?? null;
    $retailUnits = $_POST['retailUnits'];
    $wholesaleUnits = $_POST['wholesaleUnits'];
    $model = $_POST['model'];
    $color = $_POST['color'];
    $status = $_POST['status'];
    $division = $_POST['division'];
    $department = $_POST['department'];
    $category = $_POST['category'];
    
    // Generar el UPC automáticamente
    $division_str = str_pad($division, 2, '0', STR_PAD_LEFT);
    $department_str = str_pad($department, 2, '0', STR_PAD_LEFT);
    $category_str = str_pad($category, 4, '0', STR_PAD_LEFT);
    
    // Consultar el último UPC para esta combinación
    $base_upc = $division_str . $department_str . $category_str;
    $sql = "SELECT MAX(SUBSTRING(UPC, 9, 5)) as last_sequence FROM mercaderia WHERE UPC LIKE '$base_upc%'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    
    $last_sequence = $row['last_sequence'] ?? 0;
    $sequence = intval($last_sequence) + 1;
    $sequence_str = str_pad($sequence, 5, '0', STR_PAD_LEFT);
    
    $upc_code = $base_upc . $sequence_str;
    
    // Verificar que el UPC generado no exista (por si acaso)
    $check_sql = "SELECT COUNT(*) as count FROM mercaderia WHERE UPC = '$upc_code'";
    $check_result = mysqli_query($conn, $check_sql);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if ($check_row['count'] > 0) {
        // Si por alguna razón ya existe, incrementar la secuencia
        $sequence++;
        $sequence_str = str_pad($sequence, 5, '0', STR_PAD_LEFT);
        $upc_code = $base_upc . $sequence_str;
    }
    
    // Insertar el producto en la tabla MERCADERIA
    $insert_sql = "INSERT INTO mercaderia (UPC, DESCRIPCION, PRECIO_ESTANDAR, PRECIO_OFERTA, PRECIO_MAYOREO, 
                   UNIDADES_MINORISTA, UNIDADES_MAYORISTA, MODELO, COLOR, ESTADO, ID_DIV, ID_DEP, ID_CAT) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, 'ssdddiisssiii', 
        $upc_code, $description, $standardPrice, $offerPrice, $wholesalePrice,
        $retailUnits, $wholesaleUnits, $model, $color, $status, 
        $division, $department, $category
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $product_id = mysqli_insert_id($conn);
        
        // Insertar las imágenes en la tabla de imágenes (si existe)
        foreach ($filePaths as $index => $file) {
            $image_sql = "INSERT INTO producto_imagenes (PRODUCTO_ID, RUTA_IMAGEN, ES_PRINCIPAL) 
                          VALUES (?, ?, ?)";
            $is_main = $file['is_main'] ? 1 : 0;
            $stmt_img = mysqli_prepare($conn, $image_sql);
            mysqli_stmt_bind_param($stmt_img, 'isi', $product_id, $file['path'], $is_main);
            mysqli_stmt_execute($stmt_img);
        }
        
        // Insertar las URLs de YouTube (si existen)
        foreach ($youtubeUrls as $url) {
            if (!empty($url)) {
                $youtube_sql = "INSERT INTO producto_videos (PRODUCTO_ID, URL_VIDEO) VALUES (?, ?)";
                $stmt_yt = mysqli_prepare($conn, $youtube_sql);
                mysqli_stmt_bind_param($stmt_yt, 'is', $product_id, $url);
                mysqli_stmt_execute($stmt_yt);
            }
        }
        
        // Éxito
        header('Location: success.php?upc=' . $upc_code);
        exit;
    } else {
        // Error en la inserción
        $error = mysqli_error($conn);
        header('Location: error.php?message=' . urlencode($error));
        exit;
    }
    
} else {
    // Si no es POST, redirigir al formulario
    header('Location: form_productos.php');
    exit;
}
?>