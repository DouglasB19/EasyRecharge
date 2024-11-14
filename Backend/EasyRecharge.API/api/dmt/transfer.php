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
    error_log("Token Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Invalid or expired token"]);
    exit();
}

// Receber e validar os dados da requisição
$data = json_decode(file_get_contents("php://input"));
if (!isset($data->amount) || !isset($data->recipient_user_id) || !isset($data->payment_method)) {
    echo json_encode(["message" => "Amount, recipient user ID, and payment method are required"]);
    exit();
}

$amount = $data->amount;
$recipient_user_id = (int)$data->recipient_user_id;
$payment_method = trim($data->payment_method);

// Validação de valor de transferência
if (!is_numeric($amount) || $amount <= 0) {
    echo json_encode(["message" => "Invalid amount. Must be a positive number."]);
    exit();
}

$min_transfer = 10.00;
$max_transfer = 10000.00;

if ($amount < $min_transfer || $amount > $max_transfer) {
    echo json_encode(["message" => "Transfer amount must be between $min_transfer and $max_transfer"]);
    exit();
}

$valid_payment_methods = ['card', 'balance'];
if (!in_array($payment_method, $valid_payment_methods)) {
    echo json_encode(["message" => "Invalid payment method"]);
    exit();
}

try {
    // Iniciar transação no banco de dados
    $pdo->beginTransaction();

    // Verificação de saldo do usuário
    $balanceQuery = "SELECT balance FROM user_balances WHERE user_id = :user_id FOR UPDATE";
    $balanceStmt = $pdo->prepare($balanceQuery);
    $balanceStmt->bindParam(':user_id', $user_id);
    $balanceStmt->execute();
    $userBalance = $balanceStmt->fetchColumn();

    if ($userBalance < $amount) {
        echo json_encode(["message" => "Insufficient balance for transfer"]);
        $pdo->rollBack();
        exit();
    }

    // Verificação se o destinatário existe
    $recipientCheckQuery = "SELECT COUNT(*) FROM users WHERE id = :recipient_user_id";
    $recipientCheckStmt = $pdo->prepare($recipientCheckQuery);
    $recipientCheckStmt->bindParam(':recipient_user_id', $recipient_user_id);
    $recipientCheckStmt->execute();
    $recipientExists = $recipientCheckStmt->fetchColumn();

    if (!$recipientExists) {
        echo json_encode(["message" => "Recipient user ID is invalid"]);
        $pdo->rollBack();
        exit();
    }

    // Inserir a transferência na tabela transfers com status 'processing'
    $transaction_date = date('Y-m-d H:i:s');
    $transactionQuery = "INSERT INTO transfers (sender_user_id, recipient_user_id, amount, payment_method, transaction_date, status) 
                         VALUES (:sender_user_id, :recipient_user_id, :amount, :payment_method, :transaction_date, 'processing')";
    $transactionStmt = $pdo->prepare($transactionQuery);
    $transactionStmt->bindParam(':sender_user_id', $user_id);
    $transactionStmt->bindParam(':recipient_user_id', $recipient_user_id);
    $transactionStmt->bindParam(':amount', $amount);
    $transactionStmt->bindParam(':payment_method', $payment_method);
    $transactionStmt->bindParam(':transaction_date', $transaction_date);

    if (!$transactionStmt->execute()) {
        throw new Exception("Error inserting transfer");
    }

    $transfer_id = $pdo->lastInsertId();

    // Atualizar os saldos dos usuários usando decremento/incremento
    $updateSenderBalanceQuery = "UPDATE user_balances SET balance = balance - :amount WHERE user_id = :user_id";
    $updateSenderStmt = $pdo->prepare($updateSenderBalanceQuery);
    $updateSenderStmt->bindParam(':amount', $amount);
    $updateSenderStmt->bindParam(':user_id', $user_id);

    $updateRecipientBalanceQuery = "UPDATE user_balances SET balance = balance + :amount WHERE user_id = :recipient_user_id";
    $updateRecipientStmt = $pdo->prepare($updateRecipientBalanceQuery);
    $updateRecipientStmt->bindParam(':amount', $amount);
    $updateRecipientStmt->bindParam(':recipient_user_id', $recipient_user_id);

    if (!$updateSenderStmt->execute() || !$updateRecipientStmt->execute()) {
        throw new Exception("Error updating balances");
    }

    // Atualizar o status da transferência para 'completed'
    $updateTransferStatusQuery = "UPDATE transfers SET status = 'completed' WHERE id = :transfer_id";
    $updateTransferStatusStmt = $pdo->prepare($updateTransferStatusQuery);
    $updateTransferStatusStmt->bindParam(':transfer_id', $transfer_id);
    $updateTransferStatusStmt->execute();

    $pdo->commit();

    // Log de sucesso da transferência
    $logMessage = "Transfer successful: Sender ID: $user_id, Recipient ID: $recipient_user_id, Amount: $amount, Transfer ID: $transfer_id, Status: completed, Date: $transaction_date";
    error_log($logMessage, 3, '../../logs/transfer_logs.log');

    echo json_encode(["message" => "Transfer successful"]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Transfer Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Failed to process transfer", "error" => $e->getMessage()]);
    exit();
}
?>
