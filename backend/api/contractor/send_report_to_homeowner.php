<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Database connection
require_once '../../config/database.php';
require_once '../../utils/notification_helper.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if user is logged in and is a contractor
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contractor') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    $contractor_id = $_SESSION['user_id'];
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['project_id']) || !isset($input['report_type']) || !isset($input['report_data'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $project_id = intval($input['project_id']);
    $report_type = $input['report_type'];
    $report_data = $input['report_data'];
    $date_range = $input['date_range'] ?? [];
    
    // Verify project belongs to contractor and get homeowner info
    $projectStmt = $pdo->prepare("
        SELECT lr.*, h.first_name as homeowner_first_name, h.last_name as homeowner_last_name, 
               h.email as homeowner_email, h.id as homeowner_id,
               cls.contractor_id, cls.acknowledged_at
        FROM layout_requests lr
        LEFT JOIN users h ON lr.homeowner_id = h.id
        LEFT JOIN contractor_layout_sends cls ON lr.id = cls.layout_id AND cls.homeowner_id = lr.homeowner_id
        WHERE lr.id = ? AND cls.contractor_id = ? AND cls.acknowledged_at IS NOT NULL
    ");
    $projectStmt->execute([$project_id, $contractor_id]);
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found or not assigned to contractor']);
        exit;
    }
    
    // Get contractor information
    $contractorStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $contractorStmt->execute([$contractor_id]);
    $contractor = $contractorStmt->fetch(PDO::FETCH_ASSOC);
    
    // Store report in database
    $reportStmt = $pdo->prepare("
        INSERT INTO progress_reports (
            project_id, contractor_id, homeowner_id, report_type, 
            report_period_start, report_period_end, report_data, 
            created_at, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'sent')
    ");
    
    $reportStmt->execute([
        $project_id,
        $contractor_id,
        $project['homeowner_id'],
        $report_type,
        $date_range['start'] ?? null,
        $date_range['end'] ?? null,
        json_encode($report_data)
    ]);
    
    $report_id = $pdo->lastInsertId();
    
    // Create notification for homeowner
    $contractor_name = trim($contractor['first_name'] . ' ' . $contractor['last_name']);
    $project_name = $report_data['project']['name'] ?? 'Your Construction Project';
    
    $notification_title = "📊 New Progress Report Available";
    $notification_message = "A new {$report_type} progress report has been submitted by {$contractor_name} for {$project_name}. Click to view detailed progress, photos, and work updates.";
    
    $notification_data = [
        'type' => 'progress_report',
        'report_id' => $report_id,
        'project_id' => $project_id,
        'contractor_id' => $contractor_id,
        'report_type' => $report_type,
        'date_range' => $date_range,
        'summary' => [
            'total_days' => $report_data['summary']['total_days'] ?? 0,
            'total_workers' => $report_data['summary']['total_workers'] ?? 0,
            'progress_percentage' => $report_data['summary']['progress_percentage'] ?? 0,
            'photos_count' => $report_data['summary']['photos_count'] ?? 0
        ]
    ];
    
    // Send notification to homeowner
    $notificationResult = createNotification(
        $pdo,
        $project['homeowner_id'],
        $notification_title,
        $notification_message,
        'progress_report',
        $notification_data
    );
    
    if (!$notificationResult['success']) {
        error_log("Failed to create notification: " . $notificationResult['message']);
    }
    
    // Send email notification (optional)
    if ($project['homeowner_email']) {
        try {
            $email_subject = "Progress Report - {$project_name}";
            $email_body = generateReportEmailBody($contractor_name, $project_name, $report_type, $report_data, $date_range);
            
            // You can implement email sending here using your preferred email service
            // For now, we'll just log it
            error_log("Email notification would be sent to: " . $project['homeowner_email']);
            error_log("Subject: " . $email_subject);
        } catch (Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Progress report sent to homeowner successfully',
        'data' => [
            'report_id' => $report_id,
            'homeowner_name' => trim($project['homeowner_first_name'] . ' ' . $project['homeowner_last_name']),
            'notification_sent' => $notificationResult['success']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Send report to homeowner error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

function generateReportEmailBody($contractor_name, $project_name, $report_type, $report_data, $date_range) {
    $start_date = isset($date_range['start']) ? date('M j, Y', strtotime($date_range['start'])) : '';
    $end_date = isset($date_range['end']) ? date('M j, Y', strtotime($date_range['end'])) : '';
    $period = $start_date && $end_date ? "({$start_date} - {$end_date})" : '';
    
    $summary = $report_data['summary'] ?? [];
    
    return "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;'>
                📊 Progress Report - {$project_name}
            </h2>
            
            <p>Dear Homeowner,</p>
            
            <p>A new <strong>{$report_type} progress report</strong> has been submitted by <strong>{$contractor_name}</strong> for your construction project {$period}.</p>
            
            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #2c3e50;'>📋 Report Summary</h3>
                <ul style='list-style: none; padding: 0;'>
                    <li style='margin: 8px 0;'><strong>📅 Working Days:</strong> " . ($summary['total_days'] ?? 0) . "</li>
                    <li style='margin: 8px 0;'><strong>👷 Total Workers:</strong> " . ($summary['total_workers'] ?? 0) . "</li>
                    <li style='margin: 8px 0;'><strong>⏰ Total Hours:</strong> " . ($summary['total_hours'] ?? 0) . "h</li>
                    <li style='margin: 8px 0;'><strong>📈 Progress Made:</strong> " . ($summary['progress_percentage'] ?? 0) . "%</li>
                    <li style='margin: 8px 0;'><strong>📸 Photos Taken:</strong> " . ($summary['photos_count'] ?? 0) . "</li>
                </ul>
            </div>
            
            <p>To view the complete detailed report with photos, work progress, labour analysis, and quality metrics, please log in to your BuildHub dashboard.</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='http://localhost:3000/login' 
                   style='background: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                    View Full Report
                </a>
            </div>
            
            <p>If you have any questions about this report, please don't hesitate to contact {$contractor_name} directly.</p>
            
            <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
            
            <p style='font-size: 12px; color: #666;'>
                This is an automated notification from BuildHub Construction Management System.<br>
                Generated on " . date('M j, Y \a\t g:i A') . "
            </p>
        </div>
    </body>
    </html>
    ";
}
?>