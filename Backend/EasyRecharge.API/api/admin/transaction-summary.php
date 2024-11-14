<?php  
require_once '../../config/config.php';  // Configuração de banco de dados e conexão
require_once '../../vendor/autoload.php';  // Caminho da biblioteca JWT

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");

// Função para extrair e verificar o token JWT
function extractJwt() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
        return filter_var($jwt, FILTER_SANITIZE_STRING) ? $jwt : null;
    }
    return null;
}

// Verificar Token
$jwt = extractJwt();
if (!$jwt) {
    echo json_encode(["message" => "Token JWT is required"]);
    http_response_code(400);  // Bad Request
    exit();
}

try {
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    if ($decoded->role !== 'admin') {
        echo json_encode(["message" => "Access denied"]);
        http_response_code(403);  // Forbidden
        exit();
    }
} catch (Exception $e) {
    if ($e->getMessage() === 'Expired token') {
        echo json_encode(["message" => "Token has expired"]);
        http_response_code(401);  // Unauthorized
    } else {
        echo json_encode(["message" => "Invalid token"]);
        http_response_code(400);  // Bad Request
    }
    exit();
}

// Filtros (Data e Status)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;  // Default: 10 por página
$offset = ($page - 1) * $per_page;

// Verificar cache
$cache_file = '../../cache/transaction_summary_cache.json';
$cache_expiration = 60 * 10;  // Cache válido por 10 minutos

if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_expiration)) {
    $cached_data = file_get_contents($cache_file);
    echo $cached_data;
    exit();
}

// Consultar o resumo das transações
try {
    // Construir a query com base nos filtros
    $query = "SELECT COUNT(id) as total_transactions, SUM(amount) as total_amount, status 
              FROM transactions 
              WHERE 1";

    if ($start_date) {
        $query .= " AND transaction_date >= :start_date";
    }

    if ($end_date) {
        $query .= " AND transaction_date <= :end_date";
    }

    if ($status) {
        $query .= " AND status = :status";
    }

    $query .= " GROUP BY status LIMIT :offset, :per_page";  // Paginação

    $stmt = $pdo->prepare($query);

    // Bind parameters
    if ($start_date) {
        $stmt->bindParam(':start_date', $start_date);
    }

    if ($end_date) {
        $stmt->bindParam(':end_date', $end_date);
    }

    if ($status) {
        $stmt->bindParam(':status', $status);
    }

    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':per_page', $per_page, PDO::PARAM_INT);

    // Executar a consulta
    $execute = $stmt->execute();
    if (!$execute) {
        echo json_encode(["message" => "Error executing query"]);
        http_response_code(500);  // Internal Server Error
        exit();
    }

    // Buscar os dados
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($summary)) {
        echo json_encode(["message" => "No transaction data found"]);
        http_response_code(404);  // Not Found
        exit();
    }

    // Organizar a resposta
    $response = [];
    foreach ($summary as $row) {
        $response[] = [
            "status" => $row['status'],
            "total_transactions" => (int) $row['total_transactions'],
            "total_amount" => (float) $row['total_amount']
        ];
    }

    // Cache dos resultados
    $cache_data = json_encode([
        "message" => "Transaction summary fetched successfully",
        "data" => $response
    ]);
    file_put_contents($cache_file, $cache_data);

    // Retornar a resposta com os dados das transações
    echo $cache_data;
    http_response_code(200);  // OK

} catch (PDOException $e) {
    // Tratar erro de banco de dados
    error_log("[" . date('Y-m-d H:i:s') . "] Database Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Database error. Please try again later."]);
    http_response_code(500);  // Internal Server Error
}
?>
