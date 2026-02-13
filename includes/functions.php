<?php

/**
 * Sanitize user input
 */
function clean_input($data) {
    $data = trim($data);
    //$data = stripslashes($data);
    //$data = htmlspecialchars($data);
    return $data;
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Return JSON response
 */
function json_response($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit();
}

/**
 * Flash message helper (using session)
 */
function set_flash_message($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash_message() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'])) {
        $msg = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $msg;
    }
    return null;
}

/**
 * Gamification: Calculate User Level and XP
 */
function get_level_info($xp) {
    if ($xp >= 50000) return ['level' => 10, 'title' => 'Godlike', 'icon' => '🌟', 'next_xp' => null];
    if ($xp >= 25000) return ['level' => 9, 'title' => 'Immortal', 'icon' => '⚡', 'next_xp' => 50000];
    if ($xp >= 12000) return ['level' => 8, 'title' => 'Mythic', 'icon' => '🔮', 'next_xp' => 25000];
    if ($xp >= 6000)  return ['level' => 7, 'title' => 'Legend', 'icon' => '🦁', 'next_xp' => 12000];
    if ($xp >= 3000)  return ['level' => 6, 'title' => 'Grandmaster', 'icon' => '♟️', 'next_xp' => 6000];
    if ($xp >= 1500)  return ['level' => 5, 'title' => 'Master', 'icon' => '👑', 'next_xp' => 3000];
    if ($xp >= 701)   return ['level' => 4, 'title' => 'Critic', 'icon' => '🧐', 'next_xp' => 1500];
    if ($xp >= 301)   return ['level' => 3, 'title' => 'Reviewer', 'icon' => '📝', 'next_xp' => 700];
    if ($xp >= 101)   return ['level' => 2, 'title' => 'Explorer', 'icon' => '🔭', 'next_xp' => 300];
    return ['level' => 1, 'title' => 'Newbie', 'icon' => '🌱', 'next_xp' => 100];
}

function calculate_user_xp($user_id) {
    global $pdo;
    
    // 50 XP per Item Added
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE added_by_user_id = ?");
    $stmt->execute([$user_id]);
    $items_count = $stmt->fetchColumn();
    
    // 10 XP per Rating Given
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ratings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $ratings_count = $stmt->fetchColumn();
    
    $xp = ($items_count * 50) + ($ratings_count * 10);
    
    // Update DB if changed (simple optimization could be added here, but direct update is safer for sync)
    // We update XP column on login or specific triggers usually, or just calc on fly. 
    // Requirement says "XP (INT default 0)" in DB, so we should persist it.
    // Let's allow this function to just RETURN the calculated XP. 
    // Persisting it should happen in actions or a periodic sync, 
    // OR we just run this update whenever this function is called if we want real-time.
    // Let's update the DB here for consistency.
    $pdo->prepare("UPDATE users SET xp = ? WHERE id = ?")->execute([$xp, $user_id]);
    
    return $xp;
}
