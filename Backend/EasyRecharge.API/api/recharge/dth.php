<?php
require_once '../../config/config.php'; 
require_once '../../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");


$data = json_decode(file_get_contents("php://input"));


$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401); 
    echo json_encode(["message" => "Authorization token is required"]);
    exit();
}


$jwt = str_replace('Bearer ', '', $headers['Authorization']);

try {
    
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    $user_id = $decoded->user_id;

    
    if (!isset($data->account_number) || !isset($data->amount) || !isset($data->operator_id) || !isset($data->payment_method)) {
        http_response_code(422);
        echo json_encode(["message" => "Account number, amount, operator ID, and payment method are required"]);
        exit();
    }

    $account_number = $data->account_number;
    $amount = $data->amount;
    $operator_id = $data->operator_id;
    $payment_method = $data->payment_method;

    
    if (!isset($data->customer_id) || !is_numeric($data->customer_id)) {
        http_response_code(400); 
        echo json_encode(["message" => "Invalid account number. Please provide a valid account number."]);
        exit();
    }

    
    if (!is_numeric($amount) || $amount < 10 || $amount > 500) {
        http_response_code(422);
        echo json_encode(["message" => "Invalid recharge amount. Please enter between 10 and 500."]);
        exit();
    }

    
    $operatorQuery = "SELECT id FROM operators WHERE id = :operator_id";
    $operatorStmt = $pdo->prepare($operatorQuery);
    $operatorStmt->bindParam(':operator_id', $operator_id);
    $operatorStmt->execute();

    if (!$operatorStmt->fetch()) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid operator. Please select a valid operator."]);
        exit();
    }

    
    if ($payment_method == 'balance') {
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
    }

    
    $pdo->beginTransaction();

    
    $rechargeQuery = "INSERT INTO recharges (user_id, account_number, operator, recharge_amount, payment_method) 
                      VALUES (:user_id, :account_number, :operator_id, :amount, :payment_method)";
    $rechargeStmt = $pdo->prepare($rechargeQuery);
    $rechargeStmt->bindParam(':user_id', $user_id);
    $rechargeStmt->bindParam(':account_number', $account_number);
    $rechargeStmt->bindParam(':operator_id', $operator_id);
    $rechargeStmt->bindParam(':amount', $amount);
    $rechargeStmt->bindParam(':payment_method', $payment_method);
    $rechargeStmt->execute();

    
    $transactionQuery = "INSERT INTO transactions (user_id, transaction_type, amount, operator_id, status) 
                         VALUES (:user_id, 'recharge', :amount, :operator_id, 'completed')";
    $transactionStmt = $pdo->prepare($transactionQuery);
    $transactionStmt->bindParam(':user_id', $user_id);
    $transactionStmt->bindParam(':amount', $amount);
    $transactionStmt->bindParam(':operator_id', $operator_id);
    $transactionStmt->execute();

    
    if ($payment_method == 'balance') {
        $updateBalanceQuery = "UPDATE user_balances SET balance = balance - :amount WHERE user_id = :user_id";
        $updateBalanceStmt = $pdo->prepare($updateBalanceQuery);
        $updateBalanceStmt->bindParam(':amount', $amount);
        $updateBalanceStmt->bindParam(':user_id', $user_id);
        $updateBalanceStmt->execute();

        if ($updateBalanceStmt->rowCount() == 0) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["message" => "Failed to update user balance"]);
            exit();
        }
    }

    
    $pdo->commit();
    echo json_encode(["message" => "DTH recharge was successful"]);

} catch (Exception $e) {
    
    $pdo->rollBack();
    error_log("Error processing DTH recharge: " . $e->getMessage()); 
    http_response_code(500);
    echo json_encode(["message" => "Failed to process DTH recharge. Please try again later."]);
}

