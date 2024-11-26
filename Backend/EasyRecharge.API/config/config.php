<?php

$host = 'localhost';
$dbname = 'easy_recharge_db';
$username = 'root'; 
$password = ''; 
$jwt_secret_key = 'your_jwt_secret_key';

header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type, Authorization"); 
header("Access-Control-Allow-Credentials: true"); 

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); 
    exit();
}

try {
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    
    die("Connection failed: " . $e->getMessage());
}

date_default_timezone_set('Asia/Kolkata');
