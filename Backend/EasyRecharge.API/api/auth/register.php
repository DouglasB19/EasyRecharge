<?php
require_once '../../config/config.php';  

header("Content-Type: application/json");


function sendError($message, $statusCode) {
    http_response_code($statusCode);
    echo json_encode(array("message" => $message));
    exit();
}


function sendSuccess($message) {
    echo json_encode(array("message" => $message));
    exit();
}

$data = json_decode(file_get_contents("php://input"));  


if (!isset($data->name) || !isset($data->email) || !isset($data->phone) || !isset($data->password)) {
    sendError("All fields are required", 400);
}

$name = $data->name;
$email = $data->email;
$phone = $data->phone;
$password = $data->password;


$query = "SELECT * FROM users WHERE email = :email OR phone = :phone";
$stmt = $pdo->prepare($query);
$stmt->bindParam(":email", $email);
$stmt->bindParam(":phone", $phone);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    sendError("Email or phone already exists", 400);
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError("Invalid email format", 400);
}


if (!preg_match("/^\+?[0-9]{10,15}$/", $phone)) {
    sendError("Invalid phone number format", 400);
}


if (strlen($password) < 8) {
    sendError("Password must be at least 8 characters long", 400);
}

if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
    echo json_encode(["message" => "New password must contain at least one uppercase letter, one number, and one special character"]);
    exit();
}


$hashedPassword = password_hash($password, PASSWORD_BCRYPT);


try {
    $pdo->beginTransaction();

    
    $query = "INSERT INTO users (name, email, phone, password) VALUES (:name, :email, :phone, :password)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":phone", $phone);
    $stmt->bindParam(":password", $hashedPassword);

    if ($stmt->execute()) {
        $pdo->commit();
        http_response_code(201);  
        sendSuccess("User registered successfully");
    } else {
        $pdo->rollBack();
        sendError("Error registering user", 500);
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    sendError("Error: " . $e->getMessage(), 500);
}

