<?php
require_once '../../config/config.php';
require_once '../../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

function sendError($message, $statusCode, $errorDetails = null) {
    echo json_encode(["message" => $message, "error" => $errorDetails]);
    http_response_code($statusCode);
    exit();
}

function extractJwt() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
        return filter_var($jwt, FILTER_SANITIZE_STRING) ? $jwt : null;
    }
    return null;
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

try {
    
    $jwt = extractJwt();
    if (!$jwt) {
        sendError("Token JWT is required", 400);
    }

    
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    if ($decoded->role !== 'admin') {
        sendError("Access denied", 403);
    }

    
    $ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : null;
    if ($ticket_id && $ticket_id <= 0) {
        sendError("Invalid ticket_id", 400);
    }

    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;
    $validStatuses = ['open', 'in_progress', 'closed'];
    if ($status && !in_array($status, $validStatuses)) {
        sendError("Invalid status parameter", 400);
    }

    
    $conditions = [];
    if ($ticket_id) {
        $conditions[] = "id = :ticket_id";
    }
    if ($status) {
        $conditions[] = "status = :status";
    }

    $sql = "SELECT * FROM support_requests";
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 100) : 10;  
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    $sql .= " LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    
    if ($ticket_id) {
        $stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
    }
    if ($status) {
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    try {
        $stmt->execute();
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
        sendError("Error retrieving ticket status", 500);
    }

    
    if (empty($tickets)) {
        echo json_encode(["tickets" => []]);
        http_response_code(200);
        exit();
    }

    
    $countSql = "SELECT COUNT(*) FROM support_requests";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute();
    $totalTickets = $countStmt->fetchColumn();

    
    echo json_encode([
        "tickets" => $tickets,
        "total" => $totalTickets,
        "limit" => $limit,
        "offset" => $offset
    ]);
    http_response_code(200);

} catch (Exception $e) { 
    
    error_log("[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    sendError("Internal server error", 500);
}

