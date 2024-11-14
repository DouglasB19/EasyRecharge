<?php
require_once '../../config/config.php';
require_once '../../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");

// Verificação do token JWT
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

// Receber e validar os dados da requisição
$data = json_decode(file_get_contents("php://input"));
if (!isset($data->amount) || !isset($data->bank_id) || !isset($data->authentication_method)) {
    echo json_encode(["message" => "Amount, bank ID, and authentication method are required"]);
    exit();
}

$amount = $data->amount;
$bank_id = $data->bank_id;
$authentication_method = trim($data->authentication_method);
$agent_id = isset($data->agent_id) ? trim($data->agent_id) : null;

// Validação de valor de saque
if (!is_numeric($amount) || $amount <= 0) {
    echo json_encode(["message" => "Invalid amount. Must be a positive number."]);
    exit();
}

// Verificação do valor de saque
if ($amount < 50 || $amount > 5000) {
    echo json_encode(["message" => "Invalid withdrawal amount. Enter between 50 and 5000."]);
    exit();
}

// Verificação de transações pendentes
$pendingQuery = "SELECT COUNT(*) FROM transactions WHERE user_id = :user_id AND transaction_type = 'withdraw' AND status = 'pending'";
$pendingStmt = $pdo->prepare($pendingQuery);
$pendingStmt->bindParam(':user_id', $user_id);
$pendingStmt->execute();
if ($pendingStmt->fetchColumn() > 0) {
    echo json_encode(["message" => "Pending withdrawal already exists"]);
    exit();
}

// Autenticação do método de autenticação para saques acima de R$1.000
if ($amount > 1000 && $authentication_method !== 'biometric') {
    echo json_encode(["message" => "Biometric authentication required for withdrawals over R$1,000"]);
    exit();
}

// Verificação do saldo do usuário
$balanceQuery = "SELECT balance FROM user_balances WHERE user_id = :user_id";
$balanceStmt = $pdo->prepare($balanceQuery);
$balanceStmt->bindParam(':user_id', $user_id);
$balanceStmt->execute();
$userBalance = $balanceStmt->fetchColumn();

if ($userBalance < $amount) {
    echo json_encode(["message" => "Insufficient balance for withdrawal"]);
    exit();
}

// Verificação de limite diário de saque
$dailyLimitQuery = "SELECT SUM(amount) AS total_today FROM transactions WHERE user_id = :user_id AND transaction_type = 'withdraw' AND DATE(transaction_date) = CURDATE()";
$dailyLimitStmt = $pdo->prepare($dailyLimitQuery);
$dailyLimitStmt->bindParam(':user_id', $user_id);
$dailyLimitStmt->execute();
$totalToday = $dailyLimitStmt->fetchColumn();

if (($totalToday + $amount) > 10000) {
    echo json_encode(["message" => "Daily withdrawal limit exceeded (R$10.000)."]);
    exit();
}

// Verificação se o banco é válido e ativo
$bankCheckQuery = "SELECT COUNT(*) FROM banks WHERE id = :bank_id AND status = 'active'";
$bankCheckStmt = $pdo->prepare($bankCheckQuery);
$bankCheckStmt->bindParam(':bank_id', $bank_id);
$bankCheckStmt->execute();
$bankExists = $bankCheckStmt->fetchColumn();

if (!$bankExists) {
    echo json_encode(["message" => "Invalid or inactive bank ID"]);
    exit();
}

// Se o `agent_id` foi fornecido, verificar se é um agente válido pelo ID e pelo papel
if ($agent_id !== null) {
    $agentCheckQuery = "SELECT COUNT(*) FROM users WHERE id = :agent_id";
    $agentCheckStmt = $pdo->prepare($agentCheckQuery);
    $agentCheckStmt->bindParam(':agent_id', $agent_id);
    $agentCheckStmt->execute();
    $isAgentValid = $agentCheckStmt->fetchColumn();
   
    if (!$isAgentValid) {
        echo json_encode(["message" => "Invalid agent ID"]);
        exit();
    }
}

$withdrawal_date = date('Y-m-d H:i:s');  // Registro da data e hora de saque

try {
    $pdo->beginTransaction();

    // Inserir a transação de saque na tabela transactions
    $withdrawQuery = "INSERT INTO transactions (user_id, transaction_type, amount, bank_id, status) VALUES (:user_id, 'withdraw', :amount, :bank_id, 'pending')";
    $withdrawStmt = $pdo->prepare($withdrawQuery);
    $withdrawStmt->bindParam(':user_id', $user_id);
    $withdrawStmt->bindParam(':amount', $amount);
    $withdrawStmt->bindParam(':bank_id', $bank_id);
    $withdrawStmt->execute();

    // Obter o ID da transação para uso na tabela withdrawals
    $transaction_id = $pdo->lastInsertId();

    // Inserir detalhes do saque na tabela withdrawals
    $withdrawalQuery = "INSERT INTO withdrawals (transaction_id, agent_id, authentication_method, bank_id, withdrawal_date) VALUES (:transaction_id, :agent_id, :authentication_method, :bank_id, :withdrawal_date)";
    $withdrawalStmt = $pdo->prepare($withdrawalQuery);
    $withdrawalStmt->bindParam(':transaction_id', $transaction_id);
    $withdrawalStmt->bindParam(':agent_id', $agent_id);
    $withdrawalStmt->bindParam(':authentication_method', $authentication_method);
    $withdrawalStmt->bindParam(':bank_id', $bank_id);
    $withdrawalStmt->bindParam(':withdrawal_date', $withdrawal_date);
    $withdrawalStmt->execute();

    // Atualizar o saldo do usuário na tabela user_balances
    $updateBalanceQuery = "UPDATE user_balances SET balance = balance - :amount WHERE user_id = :user_id";
    $updateBalanceStmt = $pdo->prepare($updateBalanceQuery);
    $updateBalanceStmt->bindParam(':amount', $amount);
    $updateBalanceStmt->bindParam(':user_id', $user_id);
    $updateBalanceStmt->execute();

    // Atualizar o status da transação para 'completed'
    $updateTransactionStatus = "UPDATE transactions SET status = 'completed' WHERE id = :transaction_id";
    $updateTransactionStmt = $pdo->prepare($updateTransactionStatus);
    $updateTransactionStmt->bindParam(':transaction_id', $transaction_id);
    $updateTransactionStmt->execute();

    $pdo->commit();

    echo json_encode(["message" => "Withdrawal processed successfully", "transaction_id" => $transaction_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Withdrawal Error for User $user_id on " . date('Y-m-d H:i:s') . ": " . $e->getMessage(), 3, '../../logs/withdrawal_errors.log');
    echo json_encode(["message" => "Failed to process withdrawal."]);
    exit();
}
?>
