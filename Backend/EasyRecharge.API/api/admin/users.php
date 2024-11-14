<?php
require_once '../../config/config.php';
require_once '../../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");

// Função para extrair e verificar o token JWT
function extractJwt() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
        return filter_var($jwt, FILTER_SANITIZE_STRING) ? $jwt : null;
    }
    return null;
}

// Verificar Token
$jwt = extractJwt();
if (!$jwt) {
    echo json_encode(["message" => "Token JWT is required"]);
    exit();
}

// Verificar Autorização do Admin
try {
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    if ($decoded->role !== 'admin') {
        echo json_encode(["message" => "Access denied"]);
        exit();
    }
} catch (\Firebase\JWT\ExpiredException $e) {
    echo json_encode(["message" => "Token expired"]);
    exit();
} catch (\Firebase\JWT\SignatureInvalidException $e) {
    echo json_encode(["message" => "Invalid token signature"]);
    exit();
} catch (Exception $e) {
    echo json_encode(["message" => "Invalid token"]);
    exit();
}

// Parâmetros de paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Consultar Usuários no Banco de Dados com paginação
try {
    $query = "SELECT id, name, email, phone, created_at FROM users LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatar o campo de data para padrão ISO8601
    foreach ($users as &$user) {
        $user['created_at'] = date('c', strtotime($user['created_at']));
    }

    // Obter o número total de usuários para a paginação
    $totalQuery = "SELECT COUNT(*) as total FROM users";
    $totalStmt = $pdo->query($totalQuery);
    $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode(["total" => $total, "page" => $page, "users" => $users]);
} catch (PDOException $e) {
    error_log("Database Error [admin/users.php]: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Database error. Please try again later."]);
    exit();
}
?>