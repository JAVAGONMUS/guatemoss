<?php
// database.php - Archivo único de conexión a la base de datos

// Configuración de la base de datos para GitHub
define('DB_HOST', getenv('MYSQLHOST') ?: 'turntable.proxy.rlwy.net:47866');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: 'JRVMHEVvCjiiYNJetQIWQQIelcrMBTcm');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'railway');


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
function getProductosFiltradosTodos($marca = null, $talla = null) {
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
// Función para obtener productos con filtros por empresa
function getProductosFiltrados($id_empresa = null, $marca = null, $articulo = null) {
    $sql = "SELECT c.*, m.UPC, m.DESCRIPCION, m.PRECIO, 
                   d.NOMBRE as DIVISION, dep.NOMBRE as DEPARTAMENTO, cat.NOMBRE as CATEGORIA
            FROM CATALOGO c
            JOIN MERCADERIA m ON c.ID_PROD = m.ID_PROD
            JOIN DIVISION d ON m.ID_DIV = d.ID_DIV
            JOIN DEPARTAMENTO dep ON m.ID_DEP = dep.ID_DEP
            JOIN CATEGORIA cat ON m.ID_CAT = cat.ID_CAT
            WHERE c.VENDIDO = 0";
    
    $params = [];
    
    // Filtro por empresa (si se proporciona)
    if ($id_empresa !== null) {
        $sql .= " AND EXISTS (
                    SELECT 1 
                    FROM DIVISION d2 
                    WHERE d2.ID_DIV = d.ID_DIV 
                    AND d2.ID_EMPR = :id_empresa
                  )";
        $params[':id_empresa'] = $id_empresa;
    }
    
    // Filtro por marca (categoría)
    if (!empty($marca)) {
        $sql .= " AND cat.NOMBRE LIKE :marca";
        $params[':marca'] = "%$marca%";
    }
    
    // Filtro por tipo de producto (division)
    if (!empty($articulo)) {
        $sql .= " AND d.NOMBRE LIKE :articulo";
        $params[':articulo'] = "%$articulo%";
    }

    
    $sql .= " ORDER BY cat.NOMBRE, d.NOMBRE";
    
    return executeQuery($sql, $params);
}
// Función para obtener todos los productos
function getAllProductosTodos() {
    $sql = "SELECT c.*, m.UPC, m.DESCRIPCION, m.PRECIO, 
                    d.NOMBRE as DIVISION, dep.NOMBRE as DEPARTAMENTO, cat.NOMBRE as CATEGORIA,
            FROM CATALOGO c
            JOIN MERCADERIA m ON c.ID_PROD = m.ID_PROD
            JOIN DIVISION d ON m.ID_DIV = d.ID_DIV
            JOIN DEPARTAMENTO dep ON m.ID_DEP = dep.ID_DEP
            JOIN CATEGORIA cat ON m.ID_CAT = cat.ID_CAT
            WHERE c.VENDIDO = 0
            ORDER BY cat.NOMBRE, c.TALLA_USS";
    
    return executeQuery($sql);
}
// Función para obtener todos los productos filtrados por empresa
function getAllProductos($id_empresa = null) {
    $sql = "SELECT c.*, m.UPC, m.DESCRIPCION, m.PRECIO, 
                   d.NOMBRE as DIVISION, dep.NOMBRE as DEPARTAMENTO, cat.NOMBRE as CATEGORIA
            FROM CATALOGO c
            JOIN MERCADERIA m ON c.ID_PROD = m.ID_PROD
            JOIN DIVISION d ON m.ID_DIV = d.ID_DIV
            JOIN DEPARTAMENTO dep ON m.ID_DEP = dep.ID_DEP
            JOIN CATEGORIA cat ON m.ID_CAT = cat.ID_CAT
            WHERE c.VENDIDO = 0
            AND EXISTS (
                SELECT 1 
                FROM DIVISION d2 
                WHERE d2.ID_DIV = d.ID_DIV 
                AND d2.ID_EMPR = :id_empresa
            )
            ORDER BY cat.NOMBRE, c.TALLA_USS";
    
    $params = [':id_empresa' => $id_empresa];
    
    return executeQuery($sql, $params);
}

// Función para obtener detalles de un producto
function getProductoById($id) {
    $sql = "SELECT c.*, m.UPC, m.DESCRIPCION, m.PRECIO, 
                    d.NOMBRE as DIVISION, dep.NOMBRE as DEPARTAMENTO, cat.NOMBRE as CATEGORIA,
                    d.ID_EMPR AS ID_EMRP_DIV,
                    dep.ID_EMPR AS ID_EMPR_DEP,
                    cat.ID_EMPR AS ID_EMPR_CAT
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
function insertarImagen($EMPR, $nombre, $contenido, $tipo_mime, $url_video, $user_new_data) {
    $sql = "INSERT INTO FOTOS (ID_EMPR, NOMBRE, FOTO, TIPO_MIME, URL_VIDEO, FECHA_ALTA, HORA_ALTA, USER_NEW_DATA) 
            VALUES (?, ?, ?, ?, ?, CURDATE(), CURTIME(), ?)";
    return executeQuery($sql, [$EMPR, $nombre, $contenido, $tipo_mime, $url_video, $user_new_data]);
}

// Función para obtener el próximo ID de FOTOS
function getNextFotoId() {
    $sql = "SELECT MAX(ID_FOT) as max_id FROM FOTOS";
    $result = executeQuery($sql);
    return ($result[0]['max_id'] ?? 0) + 1;
}

// Función para obtener el total de productos en CATALOGO
function SaberMaximoCatalogoTodos() {
    $sql = "SELECT COUNT(*) as total FROM CATALOGO";
    $result = executeQuery($sql);
    return ($result[0]['total'] ?? 0) ;
}

function SaberMaximoCatalogo($id_empresa=null) {
    $sql = "SELECT COUNT(*) as total 
            FROM CATALOGO c
            WHERE EXISTS (
                SELECT 1 FROM MERCADERIA m
                INNER JOIN DIVISION d ON m.ID_DIV = d.ID_DIV
                WHERE c.ID_PROD = m.ID_PROD
                AND d.ID_EMPR = :id_empresa
            )";
    
    $params = [':id_empresa' => $id_empresa];
    $result = executeQuery($sql, $params);
    return ($result[0]['total'] ?? 0);
}

// Función para obtener los productos de la respectiva pagina
function MostrarSoloPaginaTodos($offset, $productosPorPagina) {

    $sql = "SELECT c.*, m.UPC, m.DESCRIPCION, m.PRECIO, 
                    d.NOMBRE as DIVISION, dep.NOMBRE as DEPARTAMENTO, cat.NOMBRE as CATEGORIA
            FROM CATALOGO c
            JOIN MERCADERIA m ON c.ID_PROD = m.ID_PROD
            JOIN DIVISION d ON m.ID_DIV = d.ID_DIV
            JOIN DEPARTAMENTO dep ON m.ID_DEP = dep.ID_DEP
            JOIN CATEGORIA cat ON m.ID_CAT = cat.ID_CAT
            ORDER BY cat.NOMBRE, c.TALLA_USS 
            LIMIT ?,?
            ";            
    
    return executeQuery($sql,[$offset,$productosPorPagina]);
}
// Función para obtener los productos de la respectiva pagina filtrados por empresa
function MostrarSoloPagina($offset, $productosPorPagina, $id_empresa = null) {
    $sql = "SELECT c.*, m.UPC, m.DESCRIPCION, m.PRECIO, 
                   d.NOMBRE as DIVISION, dep.NOMBRE as DEPARTAMENTO, cat.NOMBRE as CATEGORIA
            FROM CATALOGO c
            JOIN MERCADERIA m ON c.ID_PROD = m.ID_PROD
            JOIN DIVISION d ON m.ID_DIV = d.ID_DIV
            JOIN DEPARTAMENTO dep ON m.ID_DEP = dep.ID_DEP
            JOIN CATEGORIA cat ON m.ID_CAT = cat.ID_CAT
            WHERE EXISTS (
                SELECT 1 
                FROM DIVISION d2 
                WHERE d2.ID_DIV = d.ID_DIV 
                AND d2.ID_EMPR = :id_empresa
            )
            ORDER BY cat.NOMBRE
            LIMIT :offset, :limit";
    
    $params = [
        ':id_empresa' => $id_empresa,
        ':offset' => (int)$offset, 
        ':limit' => (int)$productosPorPagina
    ];
    
    return executeQuery($sql, $params);
}

?>
