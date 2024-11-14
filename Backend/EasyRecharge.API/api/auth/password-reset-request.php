<?php
require_once '../../config/config.php';  // Importa a configuração de banco de dados
require_once '../../vendor/autoload.php'; // Carrega as dependências do Composer

use Firebase\JWT\JWT;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Content-Type: application/json");

function sendError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(array("message" => $message));
    exit();
}

function sendSuccess($message, $data = null) {
    echo json_encode(array("message" => $message, "data" => $data));
    exit();
}

$data = json_decode(file_get_contents("php://input"));  // Lê os dados enviados na requisição

// Verifica se o e-mail ou telefone foi enviado
if (!isset($data->email) && !isset($data->phone)) {
    sendError("Email or phone is required");
}

$email = isset($data->email) ? $data->email : null;
$phone = isset($data->phone) ? $data->phone : null;

// Verifica se o e-mail ou telefone estão no formato correto
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError("Invalid email format", 422);
}

if ($phone && !preg_match("/^\+?[1-9]\d{1,14}$/", $phone)) {
    sendError("Invalid phone number format", 422);
}

// Verificar se o e-mail ou telefone existe no banco de dados
$query = "SELECT id FROM users WHERE email = :email OR phone = :phone";
$stmt = $pdo->prepare($query);
$stmt->bindParam(":email", $email);
$stmt->bindParam(":phone", $phone);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    sendError("Email or phone not found");
}

$user_id = $user['id'];  // Obtém o ID do usuário para registrar a tentativa

// Registrar a tentativa de redefinição no banco de dados
$insertAttemptQuery = "INSERT INTO password_reset_attempts (user_id, success) VALUES (:user_id, FALSE)";
$insertAttemptStmt = $pdo->prepare($insertAttemptQuery);
$insertAttemptStmt->bindParam(":user_id", $user_id);
$insertAttemptStmt->execute();

// Verificar o número de tentativas no último intervalo de 1 hora
$attemptsQuery = "SELECT COUNT(*) FROM password_reset_attempts 
                  WHERE user_id = :user_id AND attempt_time > (NOW() - INTERVAL 1 HOUR)";
$attemptsStmt = $pdo->prepare($attemptsQuery);
$attemptsStmt->bindParam(":user_id", $user_id);
$attemptsStmt->execute();
$attemptsCount = $attemptsStmt->fetchColumn();

if ($attemptsCount >= 3) {
    sendError("Too many reset attempts. Please try again later.");
}

// Gerar o token JWT com a expiração de 15 minutos
$issuedAt = time();
$expirationTime = $issuedAt + 900;  // Token expira em 15 minutos
$payload = array(
    "iat" => $issuedAt,
    "exp" => $expirationTime,
    "email" => $email,
    "phone" => $phone
);

$jwt = JWT::encode($payload, $jwt_secret_key);

// Gerar o link de redefinição de senha
$resetLink = "https://yourdomain.com/reset-password?token=" . $jwt;

// Enviar o link de redefinição de senha via PHPMailer
$mail = new PHPMailer(true);

try {
    // Configurações do servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = getenv('SMTP_USERNAME');
    $mail->Password = getenv('SMTP_PASSWORD');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('youremail@gmail.com', 'Your Name');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request';
    $mail->Body    = "Click on this link to reset your password: " . $resetLink;

    $mail->send();

    // Atualiza a tentativa no banco de dados como "sucesso"
    $updateAttemptQuery = "UPDATE password_reset_attempts SET success = TRUE WHERE id = :attempt_id";
    $updateAttemptStmt = $pdo->prepare($updateAttemptQuery);
    $updateAttemptStmt->bindParam(":attempt_id", $pdo->lastInsertId());
    $updateAttemptStmt->execute();

    sendSuccess("Password reset link has been sent to your email");
} catch (Exception $e) {
    sendError("Failed to send password reset email. Error: " . $mail->ErrorInfo);
}
?>
