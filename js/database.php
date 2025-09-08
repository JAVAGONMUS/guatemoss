<?php
// database.php - Archivo único de conexión a la base de datos

// Configuración de la base de datos para PHPMYADMIN
define('DB_HOST', 'localhost');
define('DB_USER', 'admin');
define('DB_PASS', '');
define('DB_NAME', 'sysmussa');

// Función para obtener conexión a la base de datos
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $conn->exec("SET NAMES 'utf8'");
        } catch(PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    
    return $conn;
}

// Función para ejecutar consultas seguras
function executeQuery($sql, $params = []) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Error en la preparación de la consulta: " . implode(" ", $conn->errorInfo()));
        }
        
        $result = $stmt->execute($params);
        
        if ($result === false) {
            throw new Exception("Error en la ejecución de la consulta: " . implode(" ", $stmt->errorInfo()));
        }
        
        // Para SELECT, INSERT, UPDATE, DELETE
        if (stripos($sql, 'SELECT') === 0) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif (stripos($sql, 'INSERT') === 0) {
            return $conn->lastInsertId();
        } else {
            return $stmt->rowCount();
        }
    } catch (Exception $e) {
        die("Error en la consulta: " . $e->getMessage());
    }
}

// Función para obtener imágenes por IDs
function getImagesByIds($ids) {
    if (empty($ids)) return [];
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT * FROM FOTOS WHERE ID_FOT IN ($placeholders)";
    
    return executeQuery($sql, $ids);
}

// Función para obtener productos con filtros
function getProductosFiltrados($marca = null, $talla = null) {
    $sql = "SELECT c.*, m.UPC, m.DESCRIPCION, m.PRECIO, 
                   d.NOMBRE as DIVISION, dep.NOMBRE as DEPARTAMENTO, cat.NOMBRE as CATEGORIA
            FROM CATALOGO c
            JOIN MERCADERIA m ON c.ID_PROD = m.ID_PROD
            JOIN DIVISION d ON m.ID_DIV = d.ID_DIV
            JOIN DEPARTAMENTO dep ON m.ID_DEP = dep.ID_DEP
            JOIN CATEGORIA cat ON m.ID_CAT = cat.ID_CAT
            WHERE c.VENDIDO = 0";
    
    $params = [];
    
    if (!empty($marca)) {
        $sql .= " AND cat.NOMBRE LIKE ?";
        $params[] = "%$marca%";
    }
    
    if (!empty($talla)) {
        $sql .= " AND (c.TALLA_USS = ? OR c.TALLA_EUR = ? OR c.TALLA_CM = ?)";
        $params[] = $talla;
        $params[] = $talla;
        $params[] = $talla;
    }
    
    $sql .= " ORDER BY cat.NOMBRE, c.TALLA_USS";
    
    return executeQuery($sql, $params);
}

// Función para obtener todos los productos
function getAllProductos() {
    $sql = "SELECT c.*, m.UPC, m.DESCRIPCION, m.PRECIO, 
                   d.NOMBRE as DIVISION, dep.NOMBRE as DEPARTAMENTO, cat.NOMBRE as CATEGORIA
            FROM CATALOGO c
            JOIN MERCADERIA m ON c.ID_PROD = m.ID_PROD
            JOIN DIVISION d ON m.ID_DIV = d.ID_DIV
            JOIN DEPARTAMENTO dep ON m.ID_DEP = dep.ID_DEP
            JOIN CATEGORIA cat ON m.ID_CAT = cat.ID_CAT
            ORDER BY cat.NOMBRE, c.TALLA_USS";
    
    return executeQuery($sql);
}

// Función para obtener detalles de un producto
function getProductoById($id) {
    $sql = "SELECT c.*, m.UPC, m.DESCRIPCION, m.PRECIO, 
                   d.NOMBRE as DIVISION, dep.NOMBRE as DEPARTAMENTO, cat.NOMBRE as CATEGORIA
            FROM CATALOGO c
            JOIN MERCADERIA m ON c.ID_PROD = m.ID_PROD
            JOIN DIVISION d ON m.ID_DIV = d.ID_DIV
            JOIN DEPARTAMENTO dep ON m.ID_DEP = dep.ID_DEP
            JOIN CATEGORIA cat ON m.ID_CAT = cat.ID_CAT
            WHERE c.ID_CATT = ?";
    
    $result = executeQuery($sql, [$id]);
    return $result[0] ?? null;
}

// Función para insertar una nueva imagen
function insertarImagen($nombre, $contenido, $tipo_mime, $url_video, $user_new_data) {
    $sql = "INSERT INTO FOTOS (NOMBRE, FOTO, TIPO_MIME, URL_VIDEO, FECHA_ALTA, HORA_ALTA, USER_NEW_DATA) 
            VALUES (?, ?, ?, ?, CURDATE(), CURTIME(), ?)";
    return executeQuery($sql, [$nombre, $contenido, $tipo_mime, $url_video, $user_new_data]);
}

// Función para obtener el próximo ID de FOTOS
function getNextFotoId() {
    $sql = "SELECT MAX(ID_FOT) as max_id FROM FOTOS";
    $result = executeQuery($sql);
    return ($result[0]['max_id'] ?? 0) + 1;
}
?>
