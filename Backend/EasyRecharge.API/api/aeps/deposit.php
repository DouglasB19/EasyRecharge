<?php 
require_once '../../config/config.php';
require_once '../../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");

// Verificação do token JWT
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(["error_code" => "ERR_AUTH_REQUIRED", "message" => "JWT token is required"]);
    exit();
}

$jwt = str_replace('Bearer ', '', $headers['Authorization']);
try {
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    $user_id = $decoded->user_id;
} catch (Exception $e) {
    echo json_encode(["error_code" => "ERR_INVALID_TOKEN", "message" => "Invalid or expired token"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));
if (!isset($data->amount) || !isset($data->bank_id) || !isset($data->authentication_method)) {
    echo json_encode(["error_code" => "ERR_MISSING_FIELDS", "message" => "Amount, bank ID, and authentication method are required"]);
    exit();
}

$amount = filter_var($data->amount, FILTER_VALIDATE_FLOAT);
$bank_id = filter_var($data->bank_id, FILTER_VALIDATE_INT);
$authentication_method = filter_var($data->authentication_method, FILTER_SANITIZE_STRING);
$agent_id = isset($data->agent_id) ? filter_var($data->agent_id, FILTER_VALIDATE_INT) : null;

if ($amount <= 0) {
    echo json_encode(["error_code" => "ERR_INVALID_AMOUNT", "message" => "Invalid deposit amount. Must be greater than 0."]);
    exit();
}

$max_deposit_limit = 10000;
if ($amount > $max_deposit_limit) {
    echo json_encode(["error_code" => "ERR_LIMIT_EXCEEDED", "message" => "Deposit amount exceeds the maximum limit of $max_deposit_limit."]);
    exit();
}

$bankCheckQuery = "SELECT COUNT(*) FROM banks WHERE id = :bank_id AND status = 'active'";
$bankCheckStmt = $pdo->prepare($bankCheckQuery);
$bankCheckStmt->bindParam(':bank_id', $bank_id, PDO::PARAM_INT);
$bankCheckStmt->execute();
$bankExists = $bankCheckStmt->fetchColumn();

if (!$bankExists) {
    echo json_encode(["error_code" => "ERR_INVALID_BANK", "message" => "Invalid or inactive bank ID"]);
    exit();
}

if ($agent_id !== null) {
    $agentCheckQuery = "SELECT COUNT(*) FROM users WHERE id = :agent_id";
    $agentCheckStmt = $pdo->prepare($agentCheckQuery);
    $agentCheckStmt->bindParam(':agent_id', $agent_id, PDO::PARAM_INT);
    $agentCheckStmt->execute();
    $agentExists = $agentCheckStmt->fetchColumn();

    if (!$agentExists) {
        echo json_encode(["error_code" => "ERR_INVALID_AGENT", "message" => "Invalid agent ID"]);
        exit();
    }
}

try {
    $pdo->beginTransaction();

    $depositQuery = "INSERT INTO transactions (user_id, transaction_type, amount, bank_id, status) 
                      VALUES (:user_id, 'deposit', :amount, :bank_id, 'pending')";
    $depositStmt = $pdo->prepare($depositQuery);
    $depositStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $depositStmt->bindParam(':amount', $amount);
    $depositStmt->bindParam(':bank_id', $bank_id, PDO::PARAM_INT);
    $depositStmt->execute();

    $transaction_id = $pdo->lastInsertId();

    $depositDetailsQuery = "INSERT INTO deposits (transaction_id, agent_id, authentication_method, bank_id) 
                            VALUES (:transaction_id, :agent_id, :authentication_method, :bank_id)";
    $depositDetailsStmt = $pdo->prepare($depositDetailsQuery);
    $depositDetailsStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $depositDetailsStmt->bindParam(':agent_id', $agent_id, PDO::PARAM_INT);
    $depositDetailsStmt->bindParam(':authentication_method', $authentication_method);
    $depositDetailsStmt->bindParam(':bank_id', $bank_id, PDO::PARAM_INT);
    $depositDetailsStmt->execute();

    $balanceCheckQuery = "SELECT COUNT(*) FROM user_balances WHERE user_id = :user_id";
    $balanceCheckStmt = $pdo->prepare($balanceCheckQuery);
    $balanceCheckStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $balanceCheckStmt->execute();
    $balanceExists = $balanceCheckStmt->fetchColumn();

    if (!$balanceExists) {
        $insertBalanceQuery = "INSERT INTO user_balances (user_id, balance) VALUES (:user_id, :amount)";
        $insertBalanceStmt = $pdo->prepare($insertBalanceQuery);
        $insertBalanceStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $insertBalanceStmt->bindParam(':amount', $amount);
        $insertBalanceStmt->execute();
    } else {
        $updateBalanceQuery = "UPDATE user_balances SET balance = balance + :amount WHERE user_id = :user_id";
        $updateBalanceStmt = $pdo->prepare($updateBalanceQuery);
        $updateBalanceStmt->bindParam(':amount', $amount);
        $updateBalanceStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $updateBalanceStmt->execute();
    }

    $updateTransactionStatus = "UPDATE transactions SET status = 'completed' WHERE id = :transaction_id";
    $updateTransactionStmt = $pdo->prepare($updateTransactionStatus);
    $updateTransactionStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $updateTransactionStmt->execute();

    $pdo->commit();

    echo json_encode(["message" => "Deposit processed successfully"]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Failed to process deposit: " . $e->getMessage(), 3, "../../logs/error_logs.log");
    echo json_encode(["error_code" => "ERR_DEPOSIT_FAILED", "message" => "Failed to process deposit."]);
}
?>
