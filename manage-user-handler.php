<?php
session_start();
require 'db.php';

$table = "users";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Handle DELETE action
    if ($action === 'delete') {
        $user_id = $_POST['user_id'] ?? '';
        
        if (empty($user_id)) {
            $_SESSION['error'] = 'User ID is required';
            header("Location: manage-user-dashboard.php");
            exit();
        }
        
        // Don't allow deleting yourself
        if (isset($_SESSION['user']['id']) && $_SESSION['user']['id'] === $user_id) {
            $_SESSION['error'] = 'You cannot delete your own account';
            header("Location: manage-user-dashboard.php");
            exit();
        }
        
        // Get current user data to delete profile image from Supabase Storage
        $currentUser = supabase_get($table, "id=eq.$user_id&limit=1");
        if (!empty($currentUser) && isset($currentUser[0]['profile_image'])) {
            supabase_delete_image($currentUser[0]['profile_image']);
        }
        
        $result = supabase_delete($table, "id=eq.$user_id");
        
        if ($result || $result === null) {
            $_SESSION['success'] = 'User deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete user';
        }
        
        header("Location: manage-user-dashboard.php");
        exit();
    }
    
    // Handle UPDATE action
    if ($action === 'update') {
        $user_id = $_POST['user_id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'employee';
        $status = $_POST['status'] ?? 'active';
        
        if (empty($user_id) || empty($name) || empty($email)) {
            $_SESSION['error'] = 'All fields are required';
            header("Location: manage-user-dashboard.php");
            exit();
        }
        
        $data = [
            "name" => $name,
            "email" => $email,
            "role" => $role,
            "status" => $status
        ];
        
        // Handle profile image upload to Supabase Storage
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            // Upload to Supabase Storage
            $uploadResult = supabase_upload_image($_FILES['profile_image']);
            
            if (isset($uploadResult['path'])) {
                // Delete old image from Supabase Storage if exists
                $currentUser = supabase_get($table, "id=eq.$user_id&limit=1");
                if (!empty($currentUser) && isset($currentUser[0]['profile_image'])) {
                    supabase_delete_image($currentUser[0]['profile_image']);
                }
                
                $data['profile_image'] = $uploadResult['path'];
            } else {
                $_SESSION['error'] = 'Image upload failed: ' . ($uploadResult['error'] ?? 'Unknown error');
                header("Location: manage-user-dashboard.php");
                exit();
            }
        }
        
        $result = supabase_patch($table, $data, "id=eq.$user_id");
        
        if ($result || $result === null) {
            $_SESSION['success'] = 'User updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update user';
        }
        
        header("Location: manage-user-dashboard.php");
        exit();
    }
}

// If no action matched, redirect back
header("Location: manage-user-dashboard.php");
exit();
