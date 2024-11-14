<?php
require_once '../../config/config.php';
require_once '../../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(["message" => "Token JWT is required"]);
    exit();
}

$jwt = str_replace('Bearer ', '', $headers['Authorization']);
try {
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    $user_id = $decoded->user_id;
} catch (Exception $e) {
    echo json_encode(["message" => "Invalid or expired token"]);
    exit();
}

$page = isset($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) && $_GET['limit'] > 0 && $_GET['limit'] <= 100 ? (int)$_GET['limit'] : 10;
$page = min($page, 1000);  // Limitar valor de page

$offset = ($page - 1) * $limit;

$validTransactionTypes = ['recharge', 'withdraw', 'deposit', 'transfer'];
$transaction_type = isset($_GET['transaction_type']) && in_array($_GET['transaction_type'], $validTransactionTypes) ? $_GET['transaction_type'] : null;

date_default_timezone_set('UTC');

try {
    // Contagem das transações
    $countQuery = "SELECT COUNT(DISTINCT id) FROM transactions WHERE user_id = :user_id";
    if ($transaction_type) {
        $countQuery .= " AND transaction_type = :transaction_type";
    }
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    if ($transaction_type) {
        $countStmt->bindParam(':transaction_type', $transaction_type, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalTransactions = $countStmt->fetchColumn();

    // Consultando transações
    $query = "SELECT id AS transaction_id, amount, transaction_type, transaction_date FROM transactions WHERE user_id = :user_id";
    if ($transaction_type) {
        $query .= " AND transaction_type = :transaction_type";
    }
    $query .= " ORDER BY transaction_date DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    if ($transaction_type) {
        $stmt->bindParam(':transaction_type', $transaction_type, PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatação das transações
    $transactions = array_map(function($transaction) {
        $transaction['transaction_date'] = (new DateTime($transaction['transaction_date'], new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('Y-m-d H:i:s');
        return $transaction;
    }, $transactions);

    $totalPages = ceil($totalTransactions / $limit);

    echo json_encode([
        "transactions" => $transactions,
        "page" => $page,
        "limit" => $limit,
        "total_transactions" => $totalTransactions,
        "total_pages" => $totalPages
    ]);
} catch (PDOException $e) {
    error_log("Database Error: User ID: $user_id, Query: $countQuery, Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Database error. Please try again later."]);
} catch (Exception $e) {
    error_log("Error: User ID: $user_id, Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Failed to fetch transaction history"]);
}
?>
