<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

define('SYNC_API_KEY', 'CHANGE_THIS_TO_SECURE_TOKEN_9988');

$dbHost = 'sqlXXX.infinityfree.com';
$dbName = 'if0_xxxxxxx_atd_bingo_db';
$dbUser = 'if0_xxxxxxx';
$dbPass = 'YOUR_PASSWORD_HERE';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'DB Connection Failed: ' . $e->getMessage()
    ]);
    exit;
}

$headers = function_exists('getallheaders') ? getallheaders() : [];
$apiKey = $_POST['api_key'] ?? $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? null;

if ($apiKey !== SYNC_API_KEY) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Invalid API Key'
    ]);
    exit;
}

$action = $_POST['action'] ?? 'pull';
$username = $_POST['username'] ?? null;

if (!$username) {
    echo json_encode([
        'success' => false,
        'message' => 'Username required'
    ]);
    exit;
}

if ($action === 'pull') {
    try {
        $stmt = $pdo->prepare("SELECT balance, last_updated FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            echo json_encode([
                'success' => true,
                'balance' => (float)$user['balance'],
                'timestamp' => $user['last_updated']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User not found online'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Query Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

if ($action === 'push') {
    $newBalance = $_POST['balance'] ?? null;
    if ($newBalance === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Balance value missing'
        ]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, balance, last_updated) 
                               VALUES (?, ?, NOW()) 
                               ON DUPLICATE KEY UPDATE balance = VALUES(balance), last_updated = NOW()");
        $stmt->execute([$username, (float)$newBalance]);

        echo json_encode([
            'success' => true,
            'message' => 'Online balance updated successfully',
            'updated_balance' => (float)$newBalance
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Update Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Invalid action'
]);
exit;
