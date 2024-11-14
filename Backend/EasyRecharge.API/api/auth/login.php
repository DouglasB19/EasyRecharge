<?php
require_once '../../config/config.php';
require_once '../../vendor/autoload.php';

use \Firebase\JWT\JWT;

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) && !isset($data->phone)) {
    http_response_code(400); // Código 400 para erro de requisição
    echo json_encode(array("error" => "Email or phone is required"));
    exit();
}

if (isset($data->email)) {
    $email = $data->email;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(array("error" => "Invalid email format"));
        exit();
    }
}

if (isset($data->phone)) {
    $phone = $data->phone;
    // Validação de telefone (se necessário)
}

// Verificação se o campo password está presente
if (!isset($data->password)) {
    http_response_code(400); // Código 400 para falta de campo obrigatório
    echo json_encode(array("error" => "Password is required"));
    exit();
}

try {
    if (isset($email)) {
        $query = "SELECT id, email, phone, password, role FROM users WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":email", $email);
    } else {
        $query = "SELECT id, email, phone, password, role FROM users WHERE phone = :phone LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":phone", $phone);
    }
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($data->password, $user['password'])) {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600;
        $payload = array(
            "iat" => $issuedAt,
            "exp" => $expirationTime,
            "user_id" => $user['id'],
            "email" => $user['email'],
            "role" => $user['role']
        );

        $jwt = JWT::encode($payload, $jwt_secret_key, 'HS256');

        echo json_encode(array(
            "message" => "Login successful",
            "jwt" => $jwt,
            "user" => array(
                "id" => $user['id'],
                "email" => $user['email'],
                "role" => $user['role']
            )
        ));
    } else {
        http_response_code(401); // Retorna código 401 para falha de autenticação
        echo json_encode(array("error" => "Invalid email or password"));
    }
} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    http_response_code(500); // Código 500 para erro interno do servidor
    echo json_encode(array("error" => "Error processing your request"));
}
?>
