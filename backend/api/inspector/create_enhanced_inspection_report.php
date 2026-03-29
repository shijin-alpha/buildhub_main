<?php
/**
 * Enhanced Site Inspection Report Creation API
 * Supports stage-aware inspections with approval decisions and photo evidence
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in and is a site inspector OR admin
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isSiteInspector = isset($_SESSION['user_id']) && $_SESSION['role'] === 'site_inspector';

if (!$isAdmin && !$isSiteInspector) {
    http_response_code(401);
    echo json_encode(['success' =