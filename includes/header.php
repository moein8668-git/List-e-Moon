<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('APP_NAME') ? APP_NAME : 'List-e-Moon'; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        // فونت وزیر رو می‌ذاریم اول لیست که پیش‌فرض بشه
                        sans: ['Vazirmatn', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    typography: {
                        DEFAULT: {
                            css: {
                                // به پلاگین typography هم دستور میدیم که از فونت ما استفاده کنه
                                'code::before': { content: '""' },
                                'code::after': { content: '""' },
                                fontFamily: 'Vazirmatn, sans-serif',
                            },
                        },
                    },
                }
            }
        }
    </script>

    <style>
        /* این قسمت گارانتی می‌کنه که حتی اگه Tailwind لجبازی کرد، فونت اعمال بشه */
        body, .prose, .prose p, .prose h1, .prose h2, .prose h3, .prose strong, .prose li {
            font-family: 'Vazirmatn', 'Inter', sans-serif !important;
        }
    </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col transition-colors duration-200">
    <?php
    // Notification Logic (Level Up)
    if (isset($_SESSION['user_id'])) {
        try {
            global $pdo;
            // Fetch fresh XP and last seen level
            if (!isset($pdo)) { require_once 'includes/functions.php'; } // Ensure PDO is available
            
            $stmt = $pdo->prepare("SELECT xp, last_seen_level FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $n_user = $stmt->fetch();
            
            if ($n_user) {
                // Determine Current Level
                $n_level_info = get_level_info($n_user['xp']);
                $n_current_level = $n_level_info['level'];
                $n_last_level = $n_user['last_seen_level'] ?? 1; // Default to 1 if null
                
                if ($n_current_level > $n_last_level) {
                     // Display Notification
                     ?>
                     <div class="bg-gradient-to-r from-yellow-500 to-orange-600 text-white p-3 text-center font-bold shadow-lg animate-pulse relative z-[60]">
                        🎉 Congratulations! You've reached Level <?php echo $n_current_level; ?>: <?php echo $n_level_info['title'] . ' ' . $n_level_info['icon']; ?>! 🎉
                     </div>
                     <?php
                     // Update DB to stop showing it
                     $pdo->prepare("UPDATE users SET last_seen_level = ? WHERE id = ?")->execute([$n_current_level, $_SESSION['user_id']]);
                }
            }
        } catch (PDOException $e) {
            // Silently fail if column doesn't exist or DB error, to avoid breaking the header
        }
    }
    ?>

    <nav class="bg-slate-800 border-b border-slate-700 sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="dashboard.php" class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-600">
                    List-e-Moon
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-6">
                    <span class="text-slate-300 text-sm">Welcome, <b class="text-white"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></b></span>
                    
                    <!-- Leaderboard Link -->
                    <a href="leaderboard.php" class="<?php echo $current_page == 'leaderboard.php' ? 'text-yellow-400 font-bold' : 'text-slate-300 hover:text-white'; ?> transition-colors text-sm font-medium flex items-center gap-1">
                        <i class="fas fa-trophy text-yellow-500"></i> Leaderboard
                    </a>

                    <!-- Admin Links -->
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <a href="admin_users.php" class="<?php echo $current_page == 'admin_users.php' ? 'text-purple-400' : 'text-slate-300 hover:text-white'; ?> transition-colors text-sm font-medium">Admin Panel</a>
                        <a href="admin_backup.php" class="<?php echo $current_page == 'admin_backup.php' ? 'text-purple-400' : 'text-slate-300 hover:text-white'; ?> transition-colors text-sm font-medium">Backups</a>
                    <?php endif; ?>

                     <!-- Public Links (Logged In) -->
                     <a href="watchlist.php" class="<?php echo $current_page == 'watchlist.php' ? 'text-yellow-400' : 'text-slate-300 hover:text-white'; ?> text-sm font-medium transition-colors">Watchlist</a>
                     <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'text-purple-400' : 'text-slate-300 hover:text-white'; ?> text-sm font-medium transition-colors">Profile</a>
                     <a href="export_offline.php" class="text-yellow-600 hover:text-yellow-500 text-sm font-medium" target="_blank">Offline Capsule</a>
                     <a href="about.php" class="text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors"><i class="fas fa-info-circle"></i> About</a>
                    <a href="logout.php" class="text-red-400 hover:text-red-300 text-sm font-medium">Logout</a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-btn" class="text-slate-300 hover:text-white focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-slate-900 border-t border-b border-slate-700 shadow-xl relative z-[9999]">
             <div class="px-4 py-2 space-y-2">
                <p class="text-slate-400 text-sm py-2 border-b border-slate-700">User: <span class="text-white"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span></p>
                
                <!-- Leaderboard Link -->
                <a href="leaderboard.php" class="block py-2 text-yellow-400 hover:text-white flex items-center gap-2">
                    <i class="fas fa-trophy"></i> Leaderboard
                </a>

                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <a href="admin_users.php" class="block py-2 text-slate-300 hover:text-white">Admin Panel</a>
                    <a href="admin_backup.php" class="block py-2 text-slate-300 hover:text-white">Backups</a>
                <?php endif; ?>
                
                <a href="watchlist.php" class="block py-2 text-yellow-400 hover:text-white">My Watchlist</a>
                <a href="profile.php" class="block py-2 text-slate-300 hover:text-white">My Profile</a>
                <a href="export_offline.php" class="block py-2 text-yellow-600 hover:text-yellow-500">Download Offline Capsule</a>
                <a href="about.php" class="block py-2 text-blue-400 hover:text-blue-300">About</a>
                <a href="logout.php" class="block py-2 text-red-400 hover:text-red-300">Logout</a>
             </div>
        </div>
    </nav>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('mobile-menu-btn');
            const menu = document.getElementById('mobile-menu');
            if(btn && menu) {
                btn.addEventListener('click', () => {
                    menu.classList.toggle('hidden');
                });
            }
        });
    </script>
