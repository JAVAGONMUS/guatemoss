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


















// Función para obtener todos los productos filtrados por empresa de la Base de Datos
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
// Función para buscar productos con filtros y exclusión de IDs
function buscarProductosFiltrados($filtros, $excluirIds = []) {
    $sql = "SELECT c.*, m.UPC, m.DESCRIPCION, m.PRECIO, 
                   d.NOMBRE as DIVISION, dep.NOMBRE as DEPARTAMENTO, cat.NOMBRE as CATEGORIA
            FROM CATALOGO c
            JOIN MERCADERIA m ON c.ID_PROD = m.ID_PROD
            JOIN DIVISION d ON m.ID_DIV = d.ID_DIV
            JOIN DEPARTAMENTO dep ON m.ID_DEP = dep.ID_DEP
            JOIN CATEGORIA cat ON m.ID_CAT = cat.ID_CAT
            WHERE c.VENDIDO = 0";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($filtros['id_cat'])) {
        $sql .= " AND m.ID_CAT = :id_cat";
        $params[':id_cat'] = $filtros['id_cat'];
    }
    
    if (!empty($filtros['id_dep'])) {
        $sql .= " AND m.ID_DEP = :id_dep";
        $params[':id_dep'] = $filtros['id_dep'];
    }
    
    if (!empty($filtros['id_div'])) {
        $sql .= " AND m.ID_DIV = :id_div";
        $params[':id_div'] = $filtros['id_div'];
    }
    
    // Excluir IDs ya encontrados (usando parámetros nombrados)
    if (!empty($excluirIds)) {
        $placeholders = [];
        foreach ($excluirIds as $index => $id) {
            $paramName = ':excluir_' . $index;
            $placeholders[] = $paramName;
            $params[$paramName] = $id;
        }
        $sql .= " AND c.ID_CATT NOT IN (" . implode(',', $placeholders) . ")";
    }
    
    $sql .= " ORDER BY cat.NOMBRE, d.NOMBRE";
    
    return executeQuery($sql, $params);
}
// Función para depuración (opcional)
function debugBusqueda($resultados) {
    echo "<pre>";
    echo "IDs excluidos en cada paso:\n";
    // Puedes agregar más información de depuración aquí
    print_r($resultados);
    echo "</pre>";
}













// Función para obtener todas las divisiones filtradas por empresa desde la Base de Datos
function getDivisionesByEmpresa($id_empresa) {
    $sql = "SELECT ID_DIV, NOMBRE 
        FROM DIVISION 
        WHERE ID_EMPR = :id_empresa
        ORDER BY NOMBRE";
    $params = [':id_empresa' => $id_empresa];    
    return executeQuery($sql, $params);
}
// Función para obtener todas las categorias filtradas por empresa desde la Base de Datos
function getCategoriasByEmpresa($id_empresa) {
    $sql = "SELECT ID_CAT, NOMBRE 
        FROM CATEGORIA 
        WHERE ID_EMPR = :id_empresa
        ORDER BY NOMBRE";
    $params = [':id_empresa' => $id_empresa];    
    return executeQuery($sql, $params);
}
// Función para obtener todos los departamentos filtradas por empresa desde la Base de Datos
function getDepartamentosByEmpresa($id_empresa) {
    $sql = "SELECT ID_DEP, NOMBRE 
        FROM DEPARTAMENTO 
        WHERE ID_EMPR = :id_empresa
        ORDER BY NOMBRE";
    $params = [':id_empresa' => $id_empresa];    
    return executeQuery($sql, $params);
}








// Función para obtener imágenes por IDs
function getImagesByIds($ids) {
    if (empty($ids)) return [];
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT * FROM FOTOS WHERE ID_FOT IN ($placeholders)";
    
    return executeQuery($sql, $ids);
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













// Función para obtener el total de productos en la tabla CATALOGO
function SaberMaximoCatalogoTodos() {
    $sql = "SELECT COUNT(*) as total FROM CATALOGO";
    $result = executeQuery($sql);
    return ($result[0]['total'] ?? 0) ;
}
// Función para obtener el total de productos de una sola empresa en la tabla CATALOGO
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
