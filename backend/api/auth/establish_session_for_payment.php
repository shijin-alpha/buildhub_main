<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

require_once __DIR__ . "/../../config/database.php";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    
    $input = json_decode(file_get_contents("php://input"), true);
    $payment_id = $input["payment_id"] ?? null;
    
    if (!$payment_id) {
        echo json_encode([
            "success" => false,
            "message" => "Payment ID is required"
        ]);
        exit;
    }
    
    // Find the homeowner for this payment
    $stmt = $db->prepare("
        SELECT spr.homeowner_id, u.first_name, u.last_name, u.email 
        FROM stage_payment_requests spr 
        LEFT JOIN users u ON spr.homeowner_id = u.id 
        WHERE spr.id = ?
    ");
    $stmt->execute([$payment_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $_SESSION["user_id"] = $result["homeowner_id"];
        $_SESSION["user_role"] = "homeowner";
        $_SESSION["user_name"] = $result["first_name"] . " " . $result["last_name"];
        $_SESSION["user_email"] = $result["email"];
        $_SESSION["logged_in"] = true;
        
        echo json_encode([
            "success" => true,
            "message" => "Session established successfully",
            "data" => [
                "user_id" => $result["homeowner_id"],
                "user_name" => $result["first_name"] . " " . $result["last_name"],
                "session_id" => session_id()
            ]
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Payment not found"
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>