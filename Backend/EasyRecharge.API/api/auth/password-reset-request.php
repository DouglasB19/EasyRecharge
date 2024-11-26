<?php
require_once '../../config/config.php';  
require_once '../../vendor/autoload.php'; 

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

$data = json_decode(file_get_contents("php://input"));  


if (!isset($data->email) && !isset($data->phone)) {
    sendError("Email or phone is required");
}

$email = isset($data->email) ? $data->email : null;
$phone = isset($data->phone) ? $data->phone : null;


if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError("Invalid email format", 422);
}

if ($phone && !preg_match("/^\+?[1-9]\d{1,14}$/", $phone)) {
    sendError("Invalid phone number format", 422);
}


$query = "SELECT id FROM users WHERE email = :email OR phone = :phone";
$stmt = $pdo->prepare($query);
$stmt->bindParam(":email", $email);
$stmt->bindParam(":phone", $phone);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    sendError("Email or phone not found");
}

$user_id = $user['id'];  


$insertAttemptQuery = "INSERT INTO password_reset_attempts (user_id, success) VALUES (:user_id, FALSE)";
$insertAttemptStmt = $pdo->prepare($insertAttemptQuery);
$insertAttemptStmt->bindParam(":user_id", $user_id);
$insertAttemptStmt->execute();


$attemptsQuery = "SELECT COUNT(*) FROM password_reset_attempts 
                  WHERE user_id = :user_id AND attempt_time > (NOW() - INTERVAL 1 HOUR)";
$attemptsStmt = $pdo->prepare($attemptsQuery);
$attemptsStmt->bindParam(":user_id", $user_id);
$attemptsStmt->execute();
$attemptsCount = $attemptsStmt->fetchColumn();

if ($attemptsCount >= 3) {
    sendError("Too many reset attempts. Please try again later.");
}


$issuedAt = time();
$expirationTime = $issuedAt + 900;  
$payload = array(
    "iat" => $issuedAt,
    "exp" => $expirationTime,
    "email" => $email,
    "phone" => $phone
);

$jwt = JWT::encode($payload, $jwt_secret_key);


$resetLink = "https://yourdomain.com/reset-password?token=" . $jwt;


$mail = new PHPMailer(true);

try {
    
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

    
    $updateAttemptQuery = "UPDATE password_reset_attempts SET success = TRUE WHERE id = :attempt_id";
    $updateAttemptStmt = $pdo->prepare($updateAttemptQuery);
    $updateAttemptStmt->bindParam(":attempt_id", $pdo->lastInsertId());
    $updateAttemptStmt->execute();

    sendSuccess("Password reset link has been sent to your email");
} catch (Exception $e) {
    sendError("Failed to send password reset email. Error: " . $mail->ErrorInfo);
}

