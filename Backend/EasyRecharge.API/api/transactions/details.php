<?php
require_once '../../config/config.php';  
require_once '../../vendor/autoload.php';  
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");
header("Cache-Control: no-cache, no-store, must-revalidate");

function extractJwt() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
        if (!empty($jwt) && preg_match('/^[a-zA-Z0-9\-_]+(?:\.[a-zA-Z0-9\-_]+){2}$/', $jwt)) {
            return $jwt;
        }
    }
    return null;
}

$jwt = extractJwt();
if (!$jwt) {
    echo json_encode(["message" => "Token JWT is required"]);
    http_response_code(400);  // Bad Request
    exit();
}

try {
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    $user_id = $decoded->user_id;

    if ($decoded->role !== 'admin' && $decoded->role !== 'user') {
        echo json_encode(["message" => "Access denied"]);
        http_response_code(403);  // Forbidden
        exit();
    }
} catch (Exception $e) {
    echo json_encode(["message" => "Invalid or expired token"]);
    http_response_code(401);  // Unauthorized
    exit();
}

$transaction_id = isset($_GET['transaction_id']) ? $_GET['transaction_id'] : null;
if (!$transaction_id || !is_numeric($transaction_id) || $transaction_id <= 0) {
    echo json_encode(["message" => "Valid Transaction ID is required"]);
    http_response_code(400);
    exit();
}

try {
    // Adicionando JOIN com a tabela operators para trazer o nome do operador
    $query = "SELECT t.id, t.user_id, t.transaction_type, t.amount, t.transaction_date, t.status, 
                     b.name AS bank_name, o.name AS operator_name
              FROM transactions t
              LEFT JOIN banks b ON t.bank_id = b.id
              LEFT JOIN operators o ON t.operator_id = o.id
              WHERE t.id = :transaction_id AND t.user_id = :user_id";  

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($transaction) {
        // Formatar a data da transação
        $transaction_date = new DateTime($transaction['transaction_date']);
        $transaction['transaction_date'] = $transaction_date->format('Y-m-d H:i:s');
        echo json_encode(["transaction" => $transaction]);
    } else {
        echo json_encode(["message" => "Transaction not found"]);
        http_response_code(404);  // Not Found
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage() . " | Code: " . $e->getCode() . " | User ID: $user_id | Transaction ID: $transaction_id", 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Database error. Please try again later."]);
    http_response_code(500);  // Internal Server Error
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage() . " | User ID: $user_id | Transaction ID: $transaction_id", 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Failed to fetch transaction details"]);
    http_response_code(500);  // Internal Server Error
}
?>
