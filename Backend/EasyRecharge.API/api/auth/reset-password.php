<?php
require_once '../../config/config.php';  // Importa a configuração de banco de dados
require_once '../../vendor/autoload.php'; // Carrega as dependências do Composer

use \Firebase\JWT\JWT;

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));  // Lê os dados enviados na requisição

// Verifica se o token e a nova senha foram enviados
if (!isset($data->token) || !isset($data->new_password)) {
    http_response_code(422);  // Retorna código de erro 422 se dados ausentes
    echo json_encode(array("message" => "Token and new password are required"));
    exit();
}

$jwt = $data->token;
$newPassword = $data->new_password;

// Validação de senha (mínimo de 8 caracteres, 1 maiúscula, 1 número e 1 caractere especial)
if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^\w\d\s]).{8,}$/', $newPassword)) {
    http_response_code(422);  // Retorna código de erro 422 se a senha for fraca
    echo json_encode(array("message" => "Password must be at least 8 characters long, contain at least one uppercase letter, one number, and one special character"));
    exit();
}

try {
    // Decodifica o token JWT
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));

    // Verifica se os dados necessários estão presentes no token
    if (!isset($decoded->email)) {
        http_response_code(400);  // Retorna código de erro 400 para token inválido
        echo json_encode(array("message" => "Token does not contain valid email information"));
        exit();
    }

    // O token foi validado com sucesso
    $email = $decoded->email;  // Obtém o e-mail do token
    $phone = isset($decoded->phone) ? $decoded->phone : null;  // Obtém o telefone, se necessário

    // Verifica se o token expirou
    if (isset($decoded->exp) && $decoded->exp < time()) {
        http_response_code(400);  // Retorna código de erro 400 se o token expirou
        echo json_encode(array("message" => "Token has expired"));
        exit();
    }

    // Obtém o user_id com base no email ou telefone
    if (isset($email)) {
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":email", $email);
    } else {
        $query = "SELECT id FROM users WHERE phone = :phone";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":phone", $phone);
    }
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(400);  // Retorna código de erro 400 se o usuário não for encontrado
        echo json_encode(array("message" => "User not found"));
        exit();
    }

    $user_id = $user['id'];

} catch (Exception $e) {
    // Mensagem detalhada para erro de token
    http_response_code(400);  // Retorna código de erro 400 se o token for inválido ou expirado
    echo json_encode(array("message" => "Invalid or expired token: " . $e->getMessage()));
    exit();
}

$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);  // Criptografa a nova senha

// Inicia uma transação
$pdo->beginTransaction();

try {
    // Verifica se a nova senha é igual à atual
    $query = "SELECT password FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $userData = $stmt->fetch();

    if (password_verify($newPassword, $userData['password'])) {
        http_response_code(400);  // Retorna código de erro 400 se a nova senha for a mesma
        echo json_encode(array("message" => "New password cannot be the same as the current password"));
        exit();
    }

    // Atualiza a senha do usuário no banco de dados
    $query = "UPDATE users SET password = :password WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":password", $hashedPassword);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

    // Registra a tentativa bem-sucedida de redefinição de senha
    $attemptQuery = "INSERT INTO password_reset_attempts (user_id, success) VALUES (:user_id, true)";
    $attemptStmt = $pdo->prepare($attemptQuery);
    $attemptStmt->bindParam(":user_id", $user_id);
    $attemptStmt->execute();

    // Confirma a transação
    $pdo->commit();

    echo json_encode(array("message" => "Password reset successful"));

} catch (Exception $e) {
    $pdo->rollBack();  // Reverte a transação em caso de erro
    error_log("Error resetting password: " . $e->getMessage());  // Log de erro
    echo json_encode(array("message" => "An unexpected error occurred. Please try again later"));
}
?>
