<?php
// اطمینان از استارت شدن سشن
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/functions.php';
// فایل auth رو هم صدا می‌زنیم ولی برای لاگین از کد خودمون استفاده می‌کنیم
require_once 'includes/auth.php'; 

// اتصال به دیتابیس (اگر در functions.php نیست، اینجا گلوبال می‌کنیم)
global $pdo;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        // --- لاگین دستی و مستقیم (اصلاح شده) ---
        $username = clean_input($_POST['username']);
        $password = trim($_POST['password']);
        
        // 1. گرفتن اطلاعات کاربر از دیتابیس
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // 2. بررسی وجود کاربر و صحت رمز عبور
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // چک کردن وضعیت اکانت (بن نباشه)
            if (isset($user['is_active']) && $user['is_active'] == 0) {
                set_flash_message('error', 'Your account has been deactivated.');
                redirect('index.php');
            }

            // 3. ست کردن سشن‌ها (مهم‌ترین بخش!)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin']; // نقش کاربر
            $_SESSION['logged_in'] = true; // نشانگر وضعیت ورود
            
            // لاگین موفق!
            redirect('dashboard.php');

        } else {
            // لاگین ناموفق
            set_flash_message('error', 'Invalid username or password.');
            redirect('index.php');
        }
        break;
        
    case 'change_password_initial':
        // Ensure user is logged in (but could be in reset-state)
        if (!isset($_SESSION['user_id'])) redirect('index.php');
        
        $new_pass = trim($_POST['new_password']);
        $confirm_pass = trim($_POST['confirm_password']);
        
        if (strlen($new_pass) < 6) {
            set_flash_message('error', 'Password must be at least 6 characters.');
            redirect('change_password.php');
        }
        
        if ($new_pass !== $confirm_pass) {
            set_flash_message('error', 'Passwords do not match.');
            redirect('change_password.php');
        }
        
        // هش کردن و آپدیت
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_needs_reset = 0 WHERE id = ?");
        
        if ($stmt->execute([$new_hash, $_SESSION['user_id']])) {
            $_SESSION['password_needs_reset'] = 0;
            set_flash_message('success', 'Password updated successfully!');
            redirect('dashboard.php');
        } else {
            set_flash_message('error', 'Error updating password.');
            redirect('change_password.php');
        }
        break;

    case 'change_password':
        // Standard user password change from Profile
        require_login();
        
        $current_pass = $_POST['current_password'];
        $new_pass = trim($_POST['new_password']);
        $confirm_pass = trim($_POST['confirm_password']);
        $user_id = $_SESSION['user_id'];
        
        // 1. Verify Current Password
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($current_pass, $user['password_hash'])) {
            set_flash_message('error', 'Incorrect current password.');
            redirect('profile.php');
        }
        
        // 2. Validate New Password
        if (strlen($new_pass) < 6) {
            set_flash_message('error', 'New password must be at least 6 characters.');
            redirect('profile.php');
        }
        
        if ($new_pass !== $confirm_pass) {
            set_flash_message('error', 'New passwords do not match.');
            redirect('profile.php');
        }
        
        // 3. Update
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");

        if ($stmt->execute([$new_hash, $user_id])) {
            set_flash_message('success', 'Password updated successfully.');
            redirect('profile.php');
        } else {
            set_flash_message('error', 'Database error updating password.');
            redirect('profile.php');
        }
        break;

    case 'create_user':
        require_login();
        if (!$_SESSION['is_admin']) {
            die('Unauthorized');
        }
        
        $username = clean_input($_POST['username']);
        $password = trim($_POST['password']);
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        
        // استفاده از هش استاندارد
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $hash, $is_admin])) {
                set_flash_message('success', "User '$username' created successfully!");
            }
        } catch (PDOException $e) {
            set_flash_message('error', "Failed to create user. Username might already exist.");
        }
        redirect('admin_users.php');
        break;

    case 'add_item':
        require_login();
        require_once 'includes/image_handler.php';

        $title = clean_input($_POST['title']);
        $description = clean_input($_POST['description']);
        $category_id = (int)$_POST['category_id'];
        $remote_id = $_POST['remote_id'] ?? null;
        $remote_image_url = $_POST['remote_image_url'] ?? null;
        $external_rating = !empty($_POST['external_rating']) ? (float)$_POST['external_rating'] : null;
        $user_id = $_SESSION['user_id'];

        $local_image_path = 'default_cover.jpg';

        // High Priority: File Upload (overrides remote)
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $uploaded_path = $_FILES['cover_image']['tmp_name'];
            $new_filename = process_image($uploaded_path, false);
            if ($new_filename) {
                $local_image_path = $new_filename;
            }
        } 
        // Secondary: Remote URL Processing
        elseif (!empty($remote_image_url)) {
            $new_filename = process_image($remote_image_url, true);
            if ($new_filename) {
                $local_image_path = $new_filename;
            }
        }

        // Duplicate Check
        if ($remote_id) {
            // Global Duplicate Check: Check against ALL items, not just the user's
            $checkStmt = $pdo->prepare("SELECT id FROM items WHERE remote_id = ?");
            $checkStmt->execute([$remote_id]);
            if ($checkStmt->fetch()) {
                set_flash_message('error', 'This item already exists in the library (added by another user).');
                redirect('add_item.php');
            }
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO items (category_id, added_by_user_id, title, description, remote_id, local_image_path, remote_original_image_url, external_rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$category_id, $user_id, $title, $description, $remote_id, $local_image_path, $remote_image_url, $external_rating]);
            
            // Gamification: Add 50 XP
            $pdo->prepare("UPDATE users SET xp = xp + 50 WHERE id = ?")->execute([$user_id]);
            
            set_flash_message('success', 'Item added successfully! (+50 XP)');
            redirect('dashboard.php');
        } catch (PDOException $e) {
            set_flash_message('error', 'Error adding item: ' . $e->getMessage());
            redirect('add_item.php');
        }
        break;

    case 'update_item':
        require_login();
        require_once 'includes/image_handler.php';
        
        $item_id = (int)$_POST['item_id'];
        $title = clean_input($_POST['title']);
        $description = clean_input($_POST['description']);
        $category_id = (int)$_POST['category_id'];
        
        // 1. Verify Ownership
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            set_flash_message('error', 'Item not found.');
            redirect('dashboard.php');
        }
        
        if (!$_SESSION['is_admin'] && $_SESSION['user_id'] != $item['added_by_user_id']) {
            die('Unauthorized: You cannot edit items you did not add.');
        }
        
        // 2. Handle Image Upload (Optional Update)
        $local_image_path = $item['local_image_path'];
        
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $uploaded_path = $_FILES['cover_image']['tmp_name'];
            $new_filename = process_image($uploaded_path, false);
            if ($new_filename) {
                $local_image_path = $new_filename;
            }
        }
        
        // 3. Update DB
        try {
            $stmt = $pdo->prepare("UPDATE items SET title = ?, description = ?, category_id = ?, local_image_path = ? WHERE id = ?");
            $stmt->execute([$title, $description, $category_id, $local_image_path, $item_id]);
            
            set_flash_message('success', 'Item updated successfully.');
            redirect("view_item.php?id=$item_id");
            
        } catch (PDOException $e) {
            set_flash_message('error', 'Error updating item: ' . $e->getMessage());
            redirect("edit_item.php?id=$item_id");
        }
        break;

    case 'rate_item':
        require_login();
        
        $item_id = (int)$_POST['item_id'];
        $score = (int)$_POST['score'];
        $user_id = $_SESSION['user_id'];
        
        if ($score < 1 || $score > 10) {
            set_flash_message('error', "Invalid score.");
            redirect("view_item.php?id=$item_id");
        }
        
        // Upsert Vote
        try {
            $stmt = $pdo->prepare("INSERT INTO ratings (item_id, user_id, score) VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE score = VALUES(score), created_at = CURRENT_TIMESTAMP");
            $stmt->execute([$item_id, $user_id, $score]);
            
            if ($stmt->rowCount() === 1) {
                $pdo->prepare("UPDATE users SET xp = xp + 10 WHERE id = ?")->execute([$user_id]);
                set_flash_message('success', 'Rating saved! (+10 XP)');
            } else {
                 set_flash_message('success', 'Rating updated!');
            }
        } catch (PDOException $e) {
            set_flash_message('error', 'Error saving rating.');
        }
        
        redirect("view_item.php?id=$item_id");
        break;

    case 'toggle_user_status':
        require_login();
        if (!$_SESSION['is_admin']) die('Unauthorized');
        
        $user_id = (int)$_POST['user_id'];
        $current_status = (int)$_POST['current_status'];
        $new_status = $current_status ? 0 : 1;
        
        if ($user_id == $_SESSION['user_id']) {
            set_flash_message('error', 'You cannot disable your own account.');
            redirect('admin_users.php');
        }
        
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $user_id]);
        
        set_flash_message('success', 'User status updated.');
        redirect('admin_users.php');
        break;

    case 'toggle_hidden':
        require_login();
        if (!$_SESSION['is_admin']) die('Unauthorized');
        
        $user_id = (int)$_POST['user_id'];
        
        // SQL Toggle: Sets 1 to 0, and 0 to 1 automatically.
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_hidden = 1 - is_hidden WHERE id = ?");
            $stmt->execute([$user_id]);
            set_flash_message('success', 'User visibility toggled.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Database error: ' . $e->getMessage());
        }
        
        redirect('admin_users.php');
        break;

    case 'admin_reset_password':
        require_login();
        if (!$_SESSION['is_admin']) die('Unauthorized');
        
        $user_id = (int)$_POST['user_id'];
        $new_temp_pass = trim($_POST['new_password']);
        
        if (empty($new_temp_pass)) {
            set_flash_message('error', 'Password cannot be empty.');
            redirect('admin_users.php');
        }
        
        $hash = password_hash($new_temp_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_needs_reset = 1 WHERE id = ?");
        $stmt->execute([$hash, $user_id]);
        
        set_flash_message('success', 'Password reset. User requires change on next login.');
        redirect('admin_users.php');
        break;

    case 'delete_item':
        require_login();
        
        $item_id = (int)$_POST['item_id'];
        
        // Fetch item to verify ownership
        $stmt = $pdo->prepare("SELECT added_by_user_id FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();

        if (!$item) {
            set_flash_message('error', 'Item not found.');
            redirect('dashboard.php');
        }

        // Security Check: Is Admin OR Is Owner
        if (!$_SESSION['is_admin'] && $_SESSION['user_id'] != $item['added_by_user_id']) {
            die('Unauthorized: You cannot delete items you did not add.');
        }
        
        // Delete item (cascade deletes ratings)
        $pdo->prepare("DELETE FROM ratings WHERE item_id = ?")->execute([$item_id]); 
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        
        // SYNC XP
        $owner_id = $item['added_by_user_id'];
        $xpStmt = $pdo->prepare("
            UPDATE users 
            SET xp = (
                (SELECT COUNT(*) FROM items WHERE added_by_user_id = ?) * 50 
                + 
                (SELECT COUNT(*) FROM ratings WHERE user_id = ?) * 10
            ) 
            WHERE id = ?
        ");
        $xpStmt->execute([$owner_id, $owner_id, $owner_id]);
        
        set_flash_message('success', 'Item deleted successfully. XP updated.');
        redirect('dashboard.php');
        break;

    case 'delete_all_users':
        require_login();
        if (!$_SESSION['is_admin']) die('Unauthorized');
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE is_admin = 0");
        $stmt->execute();
        
        set_flash_message('success', 'All non-admin users deleted.');
        redirect('admin_tools.php');
        break;

    case 'delete_all_items':
        require_login();
        if (!$_SESSION['is_admin']) die('Unauthorized');
        
        $pdo->query("DELETE FROM ratings");
        $pdo->query("DELETE FROM items");
        
        set_flash_message('success', 'All items and ratings deleted.');
        redirect('admin_tools.php');
        break;

    case 'delete_user':
        require_login();
        if (!$_SESSION['is_admin']) die('Unauthorized');
        
        $target_user_id = (int)$_POST['user_id'];
        
        if ($target_user_id == $_SESSION['user_id']) {
            set_flash_message('error', 'You cannot delete yourself.');
            redirect('admin_users.php');
        }

        $pdo->prepare("DELETE FROM ratings WHERE user_id = ?")->execute([$target_user_id]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$target_user_id]);
        
        set_flash_message('success', 'User and their ratings deleted.');
        redirect('admin_users.php');
        break;

    case 'delete_rating':
        require_login();
        
        $rating_id = (int)$_POST['rating_id'];
        $item_id = (int)$_POST['item_id'];
        
        $stmt = $pdo->prepare("SELECT user_id FROM ratings WHERE id = ?");
        $stmt->execute([$rating_id]);
        $rating = $stmt->fetch();

        if ($rating) {
            if (!$_SESSION['is_admin'] && $_SESSION['user_id'] != $rating['user_id']) {
                die('Unauthorized: You cannot delete other users ratings.');
            }

            $pdo->prepare("DELETE FROM ratings WHERE id = ?")->execute([$rating_id]);
            
            // SYNC XP
            $rate_user_id = $rating['user_id'];
            $xpStmt = $pdo->prepare("
                UPDATE users 
                SET xp = (
                    (SELECT COUNT(*) FROM items WHERE added_by_user_id = ?) * 50 
                    + 
                    (SELECT COUNT(*) FROM ratings WHERE user_id = ?) * 10
                ) 
                WHERE id = ?
            ");
            $xpStmt->execute([$rate_user_id, $rate_user_id, $rate_user_id]);
            
            set_flash_message('success', 'Rating deleted. XP updated.');
        } else {
             set_flash_message('error', 'Rating not found.');
        }
        
        redirect("view_item.php?id=$item_id");
        break;

    case 'reset_everything':
        require_login();
        if (!$_SESSION['is_admin']) die('Unauthorized');
        
        $pdo->query("DELETE FROM ratings");
        $pdo->query("DELETE FROM items");
        $pdo->query("DELETE FROM users WHERE is_admin = 0");
        
        set_flash_message('success', 'System reset complete. Fresh start!');
        redirect('admin_tools.php');
        break;

    case 'update_avatar':
        require_login();
        
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['avatar']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];
            
            if (!in_array($ext, $allowed)) {
                set_flash_message('error', 'Only JPG and PNG allowed.');
                redirect('profile.php');
            }
            
            // Cleanup old avatar
            $stmt = $pdo->prepare("SELECT avatar_path FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $current_user = $stmt->fetch();
            $old_path = $current_user['avatar_path'] ?? null;
            
            if ($old_path && file_exists(UPLOAD_DIR . 'avatars/' . $old_path) && $old_path !== 'default.png') {
                unlink(UPLOAD_DIR . 'avatars/' . $old_path);
            }
            
            $new_filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $upload_path = UPLOAD_DIR . 'avatars/';
            
            if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);
            
            $destination = $upload_path . $new_filename;
            
            // GD Resizing
            list($width, $height) = getimagesize($tmp_name);
            $source = ($ext == 'png') ? imagecreatefrompng($tmp_name) : imagecreatefromjpeg($tmp_name);
            
            if ($source) {
                $new_width = 150;
                $new_height = 150;
                $thumb = imagecreatetruecolor($new_width, $new_height);
                
                if ($ext == 'png') {
                     imagealphablending($thumb, false);
                     imagesavealpha($thumb, true);
                }
                
                $smallest_side = min($width, $height);
                $sx = ($width - $smallest_side) / 2;
                $sy = ($height - $smallest_side) / 2;
                
                imagecopyresampled($thumb, $source, 0, 0, $sx, $sy, $new_width, $new_height, $smallest_side, $smallest_side);
                
                if ($ext == 'png') imagepng($thumb, $destination, 7);
                else imagejpeg($thumb, $destination, 70);
                
                imagedestroy($thumb);
                imagedestroy($source);
                
                try {
                    $stmt = $pdo->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
                    $stmt->execute([$new_filename, $_SESSION['user_id']]);
                    
                    $_SESSION['avatar_path'] = $new_filename; 
                    set_flash_message('success', 'Avatar updated successfully!');
                
                } catch (PDOException $e) {
                    set_flash_message('error', 'Database Error: ' . $e->getMessage());
                }
            } else {
                set_flash_message('error', 'Error processing image: Invalid file or format.');
            }
        }
        redirect('profile.php');
        exit();
        break;

    case 'remove_avatar':
        require_login();
        if (!$_SESSION['is_admin']) die('Unauthorized');
        
        $target_user_id = (int)$_POST['user_id'];
        
        $stmt = $pdo->prepare("SELECT avatar_path FROM users WHERE id = ?");
        $stmt->execute([$target_user_id]);
        $target = $stmt->fetch();
        
        if ($target && !empty($target['avatar_path'])) {
            $file_path = UPLOAD_DIR . 'avatars/' . $target['avatar_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            $pdo->prepare("UPDATE users SET avatar_path = NULL WHERE id = ?")->execute([$target_user_id]);
            set_flash_message('success', 'Avatar removed.');
        } else {
             set_flash_message('error', 'No avatar to remove.');
        }
        
        $ref = $_POST['ref'] ?? '';
        if ($ref === 'admin') {
            redirect('admin_users.php');
        } elseif ($ref === 'item' && isset($_POST['item_id'])) {
            redirect('view_item.php?id=' . (int)$_POST['item_id']);
        } else {
            redirect('dashboard.php');
        }
        break;

    case 'toggle_watchlist':
        require_login();
        
        $item_id = (int)$_POST['item_id'];
        $user_id = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("SELECT id FROM watchlist WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$user_id, $item_id]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $pdo->prepare("DELETE FROM watchlist WHERE id = ?")->execute([$exists['id']]);
            set_flash_message('success', 'Removed from Watchlist.');
        } else {
            try {
                $pdo->prepare("INSERT INTO watchlist (user_id, item_id) VALUES (?, ?)")->execute([$user_id, $item_id]);
                set_flash_message('success', 'Added to Watchlist.');
            } catch (PDOException $e) { }
        }
        
        if (isset($_SERVER['HTTP_REFERER'])) {
             redirect($_SERVER['HTTP_REFERER']);
        } else {
             redirect('dashboard.php');
        }
        break;

    case 'reorder_watchlist':
        require_login();
        
        $order = json_decode($_POST['order'], true);
        if (is_array($order)) {
            try {
                $stmt = $pdo->prepare("UPDATE watchlist SET display_order = ? WHERE user_id = ? AND item_id = ?");
                foreach ($order as $index => $item_id) {
                    $stmt->execute([$index, $_SESSION['user_id'], $item_id]);
                }
                echo json_encode(['status' => 'success']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        }
        exit();
        break;

    default:
        redirect('index.php');
}
?>