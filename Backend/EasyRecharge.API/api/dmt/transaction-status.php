<?php
require_once '../../config/config.php';
require_once '../../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");

define('ERROR_TOKEN_MISSING', 'Authorization header is missing');
define('ERROR_JWT_INVALID', 'Invalid or expired token');
define('ERROR_TRANSACTION_NOT_FOUND', 'Transaction not found or access denied');
define('ERROR_TRANSACTION_ID_INVALID', 'Invalid or missing transaction ID');

// Verificação do token JWT (autenticação)
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(["error" => ERROR_TOKEN_MISSING]);
    exit();
}

$jwt = str_replace('Bearer ', '', $headers['Authorization']);
if (empty($jwt)) {
    echo json_encode(["error" => "Token JWT is missing"]);
    exit();
}

try {
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    $user_id = $decoded->user_id;
} catch (Exception $e) {
    echo json_encode(["error" => ERROR_JWT_INVALID, "details" => $e->getMessage()]);
    exit();
}

// Verificar se o parâmetro transaction_id foi fornecido e válido
if (!isset($_GET['transaction_id']) || !filter_var($_GET['transaction_id'], FILTER_VALIDATE_INT)) {
    echo json_encode(["error" => ERROR_TRANSACTION_ID_INVALID]);
    exit();
}

$transaction_id = $_GET['transaction_id'];

// Consultar a transação no banco de dados
try {
    $query = "SELECT t.*, o.name as operator_name FROM transactions t
              LEFT JOIN operators o ON t.operator_id = o.id
              WHERE t.id = :transaction_id AND t.user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':transaction_id', $transaction_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar se a transação existe
    if (!$transaction) {
        echo json_encode(["error" => ERROR_TRANSACTION_NOT_FOUND]);
        exit();
    }

    // Formatar a data da transação
    $transaction_date = date('Y-m-d H:i:s', strtotime($transaction['transaction_date']));

    // Validar tipo de transação
    $valid_types = ['recharge', 'withdraw', 'deposit', 'transfer'];
    if (!in_array($transaction['transaction_type'], $valid_types)) {
        echo json_encode(["error" => "Invalid transaction type"]);
        exit();
    }

    // Retornar os detalhes da transação
    echo json_encode([
        "transaction_id" => $transaction['id'],
        "status" => $transaction['status'],
        "amount" => $transaction['amount'],
        "transaction_type" => $transaction['transaction_type'],
        "transaction_date" => $transaction_date,
        "currency" => 'USD',
        "description" => $transaction['description'] ?? 'N/A',
        "operator_name" => $transaction['operator_name'] ?? 'N/A',
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(["error" => "Error retrieving transaction", "details" => $e->getMessage()]);
    exit();
}
?>
