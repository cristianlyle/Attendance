<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$role = $user['role'];

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            createLeaveRequest($user_id);
            break;
        case 'get_my_requests':
            getMyLeaveRequests($user_id);
            break;
        case 'get_pending':
            getPendingRequests();
            break;
        case 'get_all_requests':
            getAllRequests();
            break;
        case 'review':
            reviewLeaveRequest($user_id);
            break;
        case 'get_notifications':
            getNotifications($user_id);
            break;
        case 'mark_notification_read':
            markNotificationRead();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function createLeaveRequest($employee_id) {
    global $project_url, $apikey;
    
    $leave_type = $_POST['leave_type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = $_POST['reason'] ?? '';
    
    // Validation
    if (empty($leave_type) || empty($start_date) || empty($end_date)) {
        echo json_encode(['success' => false, 'error' => 'Please fill in all required fields']);
        return;
    }
    
    if (strtotime($start_date) > strtotime($end_date)) {
        echo json_encode(['success' => false, 'error' => 'End date cannot be before start date']);
        return;
    }
    
    // Insert leave request
    $data = [
        'employee_id' => $employee_id,
        'leave_type' => $leave_type,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'reason' => $reason,
        'status' => 'pending'
    ];
    
    $result = supabase_post('leave_requests', $data);
    
    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'error' => $result['error']['message'] ?? 'Failed to create leave request']);
        return;
    }
    
    // Create notification for admin
    createAdminNotification($employee_id, $result);
    
    echo json_encode(['success' => true, 'message' => 'Leave request submitted successfully', 'data' => $result]);
}

function getMyLeaveRequests($employee_id) {
    $query = http_build_query([
        'employee_id' => "eq.{$employee_id}",
        'order' => 'created_at.desc'
    ]);
    
    $result = supabase_get("leave_requests?{$query}");
    
    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'error' => $result['error']['message'] ?? 'Failed to fetch leave requests']);
        return;
    }
    
    // Get user data for each request
    $requests = [];
    foreach ($result as $row) {
        // Get user info
        $userQuery = http_build_query(['id' => "eq.{$row['employee_id']}", 'select' => 'name,email,profile_image']);
        $userData = supabase_get("users?{$userQuery}");
        $row['users'] = !empty($userData) && !isset($userData['error']) ? $userData[0] : null;
        $requests[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $requests]);
}

function getPendingRequests() {
    $query = http_build_query([
        'status' => 'eq.pending',
        'order' => 'created_at.asc'
    ]);
    
    $result = supabase_get("leave_requests?{$query}");
    
    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'error' => $result['error']['message'] ?? 'Failed to fetch pending requests']);
        return;
    }
    
    // Get user data for each request
    $requests = [];
    foreach ($result as $row) {
        // Get user info
        $userQuery = http_build_query(['id' => "eq.{$row['employee_id']}", 'select' => 'name,email,profile_image']);
        $userData = supabase_get("users?{$userQuery}");
        $row['users'] = !empty($userData) && !isset($userData['error']) ? $userData[0] : null;
        $requests[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $requests]);
}

function getAllRequests() {
    $query = http_build_query([
        'order' => 'created_at.desc'
    ]);
    
    $result = supabase_get("leave_requests?{$query}");
    
    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'error' => $result['error']['message'] ?? 'Failed to fetch requests']);
        return;
    }
    
    // Get user data for each request
    $requests = [];
    foreach ($result as $row) {
        // Get user info
        $userQuery = http_build_query(['id' => "eq.{$row['employee_id']}", 'select' => 'name,email,profile_image']);
        $userData = supabase_get("users?{$userQuery}");
        $row['users'] = !empty($userData) && !isset($userData['error']) ? $userData[0] : null;
        $requests[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $requests]);
}

function reviewLeaveRequest($admin_id) {
    global $apikey;
    
    $request_id = $_POST['request_id'] ?? '';
    $status = $_POST['status'] ?? ''; // 'approved' or 'rejected'
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    if (empty($request_id) || empty($status)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        return;
    }
    
    if (!in_array($status, ['approved', 'rejected'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        return;
    }
    
    // Get the leave request
    $query = http_build_query([
        'id' => "eq.{$request_id}"
    ]);
    
    $leaveRequest = supabase_get("leave_requests?{$query}");
    
    if (empty($leaveRequest) || isset($leaveRequest['error'])) {
        echo json_encode(['success' => false, 'error' => 'Leave request not found']);
        return;
    }
    
    $leaveRequest = $leaveRequest[0];
    $employee_id = $leaveRequest['employee_id'];
    
    // Get user info separately
    $userQuery = http_build_query(['id' => "eq.{$employee_id}", 'select' => 'name,email']);
    $userData = supabase_get("users?{$userQuery}");
    $user = !empty($userData) && !isset($userData['error']) ? $userData[0] : ['name' => 'Unknown', 'email' => ''];
    $employee_name = $user['name'];
    
    // Update leave request status
    $updateData = [
        'status' => $status,
        'admin_notes' => $admin_notes,
        'reviewed_by' => $admin_id,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $updateQuery = http_build_query(['id' => "eq.{$request_id}"]);
    $result = supabase_patch('leave_requests', $updateData, $updateQuery);
    
    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'error' => $result['error']['message'] ?? 'Failed to update leave request']);
        return;
    }
    
    // Create notification for employee
    $notificationData = [
        'user_id' => $employee_id,
        'type' => 'leave_review',
        'title' => 'Leave Request ' . ucfirst($status),
        'message' => "Your leave request for {$leaveRequest['leave_type']} from {$leaveRequest['start_date']} to {$leaveRequest['end_date']} has been {$status}.",
        'related_id' => $request_id
    ];
    
    supabase_post('notifications', $notificationData);
    
    $statusText = $status === 'approved' ? 'approved' : 'rejected';
    echo json_encode(['success' => true, 'message' => "Leave request has been {$statusText}"]);
}

function createAdminNotification($employee_id, $leaveRequest) {
    // Get admin users
    $query = http_build_query([
        'role' => 'eq.admin',
        'select' => 'id'
    ]);
    
    $admins = supabase_get("users?{$query}");
    
    if (empty($admins) || isset($admins['error'])) {
        return;
    }
    
    foreach ($admins as $admin) {
        $notificationData = [
            'user_id' => $admin['id'],
            'type' => 'leave_request',
            'title' => 'New Leave Request',
            'message' => 'A new leave request is waiting for your approval.',
            'related_id' => $leaveRequest['id'] ?? null
        ];
        
        supabase_post('notifications', $notificationData);
    }
}

function getNotifications($user_id) {
    $query = http_build_query([
        'user_id' => "eq.{$user_id}",
        'order' => 'created_at.desc',
        'limit' => 20
    ]);
    
    $result = supabase_get("notifications?{$query}");
    
    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'error' => 'Failed to fetch notifications']);
        return;
    }
    
    // Get unread count
    $unreadQuery = http_build_query([
        'user_id' => "eq.{$user_id}",
        'is_read' => 'eq.false',
        'select' => 'id'
    ]);
    
    $unreadResult = supabase_get("notifications?{$unreadQuery}");
    $unreadCount = is_array($unreadResult) ? count($unreadResult) : 0;
    
    echo json_encode([
        'success' => true, 
        'data' => $result ?? [],
        'unread_count' => $unreadCount
    ]);
}

function markNotificationRead() {
    $notification_id = $_POST['notification_id'] ?? '';
    
    if (empty($notification_id)) {
        echo json_encode(['success' => false, 'error' => 'Notification ID required']);
        return;
    }
    
    $query = http_build_query(['id' => "eq.{$notification_id}"]);
    $result = supabase_patch('notifications', ['is_read' => true], $query);
    
    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'error' => 'Failed to mark notification as read']);
        return;
    }
    
    echo json_encode(['success' => true]);
}
