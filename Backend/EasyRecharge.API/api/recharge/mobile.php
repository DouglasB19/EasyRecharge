<?php
require_once '../../config/config.php';
require_once '../../vendor/autoload.php'; 

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

header("Content-Type: application/json");

$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(["message" => "Authorization header not found"]);
    exit();
}

list(, $jwt) = explode(' ', $headers['Authorization']);
try {
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    $user_id = $decoded->user_id;
} catch (Exception $e) {
    echo json_encode(["message" => "Invalid or expired token"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->phone_number) || empty($data->country_code) || empty($data->amount) || empty($data->operator_id)) {
    echo json_encode(["message" => "Phone number, country code, amount, and operator ID are required"]);
    exit();
}

$phone_number = $data->phone_number;
$country_code = $data->country_code;
$amount = $data->amount;
$operator_id = $data->operator_id;

$phoneUtil = PhoneNumberUtil::getInstance();
try {
    $numberProto = $phoneUtil->parse($phone_number, strtoupper($country_code));
    if (!$phoneUtil->isValidNumber($numberProto)) {
        http_response_code(422);
        echo json_encode(["message" => "Invalid phone number for the given country"]);
        exit();
    }
    $formatted_phone = $phoneUtil->format($numberProto, \libphonenumber\PhoneNumberFormat::E164);
} catch (NumberParseException $e) {
    echo json_encode(["message" => "Invalid phone number format"]);
    exit();
}

if (!is_numeric($amount) || $amount < 10 || $amount > 500) {
    http_response_code(422);
    echo json_encode(["message" => "Invalid recharge amount. Please enter between 10 and 500."]);
    exit();
}

// Consulta para verificar a operadora e garantir que o ID é válido
$operatorQuery = "SELECT id FROM operators WHERE id = :operator_id";
$operatorStmt = $pdo->prepare($operatorQuery);
$operatorStmt->bindParam(':operator_id', $operator_id);
$operatorStmt->execute();
$operator = $operatorStmt->fetch();

if (!$operator) {
    echo json_encode(["message" => "Invalid operator. Please select a valid operator."]);
    exit();
}

// Deixa $bank_id como null, já que não há relacionamento direto entre banks e operators
$bank_id = null;

// Verificar o saldo do usuário
$balanceQuery = "SELECT balance FROM user_balances WHERE user_id = :user_id";
$balanceStmt = $pdo->prepare($balanceQuery);
$balanceStmt->bindParam(':user_id', $user_id);
$balanceStmt->execute();
$balance = $balanceStmt->fetchColumn();

if ($balance < $amount) {
    http_response_code(400);
    echo json_encode(["message" => "Insufficient balance for the recharge"]);
    exit();
}

try {
    $pdo->beginTransaction();

    // Inserir recarga
    $rechargeQuery = "INSERT INTO recharges (user_id, phone_number, operator, recharge_amount, payment_method) 
                      VALUES (:user_id, :phone_number, :operator_id, :amount, 'balance')";
    $rechargeStmt = $pdo->prepare($rechargeQuery);
    $rechargeStmt->bindParam(':user_id', $user_id);
    $rechargeStmt->bindParam(':phone_number', $formatted_phone);
    $rechargeStmt->bindParam(':operator_id', $operator_id);
    $rechargeStmt->bindParam(':amount', $amount);
    $rechargeStmt->execute();

    // Registrar a transação
    $transactionQuery = "INSERT INTO transactions (user_id, transaction_type, amount, bank_id, operator_id, status) 
                         VALUES (:user_id, 'recharge', :amount, :bank_id, :operator_id, 'completed')";
    $transactionStmt = $pdo->prepare($transactionQuery);
    $transactionStmt->bindParam(':user_id', $user_id);
    $transactionStmt->bindParam(':amount', $amount);
    $transactionStmt->bindParam(':bank_id', $bank_id);
    $transactionStmt->bindParam(':operator_id', $operator_id);
    $transactionStmt->execute();

    // Atualizar o saldo do usuário
    $updateBalanceQuery = "UPDATE user_balances SET balance = balance - :amount WHERE user_id = :user_id";
    $updateBalanceStmt = $pdo->prepare($updateBalanceQuery);
    $updateBalanceStmt->bindParam(':amount', $amount);
    $updateBalanceStmt->bindParam(':user_id', $user_id);
    $updateBalanceStmt->execute();

    if ($updateBalanceStmt->rowCount() == 0) {
        $pdo->rollBack();
        echo json_encode(["message" => "Failed to update user balance"]);
        exit();
    }

    $pdo->commit();
    echo json_encode(["message" => "Mobile recharge successful"]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["message" => "Failed to process mobile recharge: " . $e->getMessage()]);
}
?>
