<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login();

$flash = get_flash_message();

// FIX: Fetch Fresh User Data (Bypassing Session)
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    // Should not happen if logged in, but just in case
    logout();
    redirect('index.php');
}

// Ensure XP is up to date (Calculated on fly)
$user['xp'] = calculate_user_xp($user['id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - List-e-Moon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col">
    <?php require_once 'includes/header.php'; ?>

    <div class="container mx-auto p-4 md:p-8 max-w-2xl flex-grow">
        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded <?php echo $flash['type'] === 'error' ? 'bg-red-500/20 text-red-200 border border-red-500/50' : 'bg-green-500/20 text-green-200 border border-green-500/50'; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <div class="bg-slate-800 rounded-lg shadow-lg border border-slate-700 overflow-hidden">
            <div class="p-6 border-b border-slate-700">
                <h2 class="text-2xl font-bold text-white mb-1">User Profile</h2>
                <p class="text-slate-400 text-sm">Manage your account settings</p>
            </div>
            
            <div class="p-6 space-y-8">
                <!-- User Info -->
                <!-- User Info -->
                <div class="flex flex-col sm:flex-row items-center gap-6 text-center sm:text-left">
                    <div class="relative group flex-shrink-0">
                        <div class="w-24 h-24 rounded-full bg-slate-700 flex items-center justify-center overflow-hidden border-4 border-slate-600 shadow-xl">
                            <?php 
                            // Avatar Logic with Cache Busting
                            $avatar_url = "assets/default_avatar.png"; // Placeholder if needed, or just the initials logic
                            $has_avatar = false;
                            
                            if (!empty($user['avatar_path']) && file_exists(UPLOAD_DIR . 'avatars/' . $user['avatar_path'])) {
                                $avatar_url = "uploads/avatars/" . htmlspecialchars($user['avatar_path']) . "?t=" . time();
                                $has_avatar = true;
                            }
                            ?>
                            
                            <?php if ($has_avatar): ?>
                                <img src="<?php echo $avatar_url; ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="text-3xl font-bold text-white uppercase"><?php echo substr($user['username'], 0, 1); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Avatar Upload Form -->
                        <form action="actions.php" method="POST" enctype="multipart/form-data" class="absolute bottom-0 right-0">
                            <input type="hidden" name="action" value="update_avatar">
                            <label class="bg-blue-600 hover:bg-blue-700 text-white p-1.5 rounded-full cursor-pointer shadow-lg block transition-colors" title="Change Avatar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <input type="file" name="avatar" class="hidden" onchange="this.form.submit()">
                            </label>
                        </form>
                    </div>

                    <div class="flex-grow w-full">
                        <div class="flex flex-wrap items-baseline justify-center sm:justify-start gap-3 mb-1">
                            <h3 class="text-2xl font-bold text-white leading-none"><?php echo htmlspecialchars($user['username']); ?></h3>
                            
                            <!-- Inline Role/Level -->
                            <?php 
                                $xp = $user['xp']; 
                                $level_info = get_level_info($xp);
                                $progress = ($level_info['next_xp']) ? min(100, round(($xp / $level_info['next_xp']) * 100)) : 100;
                            ?>
                            <div class="flex items-center gap-1.5 text-slate-400 opacity-90">
                                <span class="text-sm grayscale opacity-70"><?php echo $level_info['icon'] ?? '🏅'; ?></span>
                                <span class="text-sm font-medium text-slate-300"><?php echo $level_info['title']; ?> <span class="text-slate-500 text-xs ml-0.5">(Lvl <?php echo $level_info['level']; ?>)</span></span>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-3">
                            <div class="flex justify-between text-xs text-slate-400 mb-1">
                                <span><?php echo $xp; ?> XP</span>
                                <span><?php echo $level_info['next_xp'] ? $level_info['next_xp'] . ' XP' : 'Max'; ?></span>
                            </div>
                            <div class="w-full bg-slate-700 rounded-full h-2.5">
                                <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2.5 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>

                        <!-- Offline Backup -->
                        <div class="mt-6">
                            <a href="download_offline.php" class="inline-flex items-center gap-2 bg-slate-700 hover:bg-slate-600 text-slate-200 text-sm font-bold py-2 px-4 rounded transition-colors border border-slate-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Download Offline Archive
                            </a>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-700 pt-6">
                    <h3 class="text-lg font-bold text-purple-400 mb-4">Change Password</h3>
                    <form action="actions.php" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Current Password</label>
                            <input type="password" name="current_password" required class="w-full bg-slate-900 border border-slate-700 rounded p-2.5 focus:border-purple-500 focus:outline-none text-white">
                            <p class="text-xs text-slate-500 mt-1">Required to verify your identity.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">New Password</label>
                                <input type="password" name="new_password" required minlength="6" class="w-full bg-slate-900 border border-slate-700 rounded p-2.5 focus:border-purple-500 focus:outline-none text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Confirm New Password</label>
                                <input type="password" name="confirm_password" required class="w-full bg-slate-900 border border-slate-700 rounded p-2.5 focus:border-purple-500 focus:outline-none text-white">
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded transition-colors shadow-lg w-full md:w-auto">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
