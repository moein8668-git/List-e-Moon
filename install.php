<?php
/**
 * List-e-Moon Automated Installer
 * Handles configuration generation, database setup, and admin creation.
 */

$config_dir = __DIR__ . '/config';
$config_file = $config_dir . '/db.php';
$is_writable = is_writable($config_dir);

$error = '';
$success = false;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    
    $admin_user = $_POST['admin_user'] ?? '';
    $admin_pass = $_POST['admin_pass'] ?? '';
    
    $tmdb_key = $_POST['tmdb_key'] ?? '';
    $rawg_key = $_POST['rawg_key'] ?? '';

    // Basic Validation
    if (empty($db_name) || empty($db_user) || empty($admin_user) || empty($admin_pass)) {
        $error = "Please fill in all required fields.";
    } elseif (!$is_writable) {
        $error = "Config directory is not writable. Please check permissions.";
    } else {
        try {
            // Step 1: Test Connection
            $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, $db_user, $db_pass, $options);

            // Step 2: Generate Config File
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']);
            
            $config_content = "<?php\n";
            $config_content .= "// Database Settings\n";
            $config_content .= "define('DB_HOST', '" . addslashes($db_host) . "');\n";
            $config_content .= "define('DB_NAME', '" . addslashes($db_name) . "');\n";
            $config_content .= "define('DB_USER', '" . addslashes($db_user) . "');\n";
            $config_content .= "define('DB_PASS', '" . addslashes($db_pass) . "');\n\n";
            
            $config_content .= "// Application Settings\n";
            $config_content .= "define('APP_NAME', 'List-e-Moon');\n";
            $config_content .= "define('BASE_URL', '" . addslashes($base_url) . "');\n\n";
            
            $config_content .= "// API Keys\n";
            $config_content .= "define('TMDB_API_KEY', '" . addslashes($tmdb_key) . "');\n";
            $config_content .= "define('RAWG_API_KEY', '" . addslashes($rawg_key) . "');\n";
            $config_content .= "define('GOOGLE_BOOKS_API_KEY', ''); // Added for compatibility\n\n";
            
            $config_content .= "// File Paths\n";
            $config_content .= "define('UPLOAD_DIR', __DIR__ . '/../uploads/');\n";
            $config_content .= "define('THUMB_DIR', UPLOAD_DIR . 'thumbs/');\n\n";
            
            $config_content .= "// Database Connection\n";
            $config_content .= "try {\n";
            $config_content .= "    \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\";\n";
            $config_content .= "    \$options = [\n";
            $config_content .= "        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
            $config_content .= "        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
            $config_content .= "        PDO::ATTR_EMULATE_PREPARES => false,\n";
            $config_content .= "    ];\n";
            $config_content .= "    \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);\n";
            $config_content .= "} catch (\\PDOException \$e) {\n";
            $config_content .= "    die(\"Database Connection Failed: \" . \$e->getMessage());\n";
            $config_content .= "}\n";

            if (file_put_contents($config_file, $config_content) === false) {
                throw new Exception("Failed to write to config/db.php");
            }

            // Step 3: Create Tables
            $queries = [
                "CREATE TABLE IF NOT EXISTS `users` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` varchar(50) NOT NULL,
                    `password_hash` varchar(255) NOT NULL,
                    `avatar_path` varchar(255) DEFAULT NULL,
                    `is_admin` tinyint(1) NOT NULL DEFAULT 0,
                    `is_active` tinyint(1) NOT NULL DEFAULT 1,
                    `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
                    `password_needs_reset` tinyint(1) NOT NULL DEFAULT 1,
                    `xp` int(11) NOT NULL DEFAULT 0,
                    `last_seen_level` int(11) NOT NULL DEFAULT 1,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `username` (`username`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `categories` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(50) NOT NULL,
                    `slug` varchar(50) NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `slug` (`slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `items` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `category_id` int(11) NOT NULL,
                    `added_by_user_id` int(11) NOT NULL,
                    `title` varchar(255) NOT NULL,
                    `description` text,
                    `remote_id` varchar(100) DEFAULT NULL,
                    `local_image_path` varchar(255) DEFAULT 'default_cover.jpg',
                    `remote_original_image_url` varchar(500) DEFAULT NULL,
                    `external_rating` decimal(3,1) DEFAULT NULL,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `category_id` (`category_id`),
                    KEY `added_by_user_id` (`added_by_user_id`),
                    CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_items_user` FOREIGN KEY (`added_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `ratings` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `item_id` int(11) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `score` tinyint(2) NOT NULL,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_vote` (`item_id`, `user_id`),
                    KEY `user_id` (`user_id`),
                    CONSTRAINT `fk_ratings_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `watchlist` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `item_id` int(11) NOT NULL,
                    `display_order` int(11) NOT NULL DEFAULT 0,
                    `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_user_item` (`user_id`, `item_id`),
                    CONSTRAINT `fk_watchlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_watchlist_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
            ];

            foreach ($queries as $sql) {
                $pdo->exec($sql);
            }

            // Seed Categories
            $checkCats = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
            if ($checkCats == 0) {
                $cats = [
                    ['Movies', 'movies'],
                    ['Series', 'series'],
                    ['Animated Movies', 'animated-movies'],
                    ['Animated Series', 'animated-series'],
                    ['Games', 'games'],
                    ['Podcasts', 'podcasts'],
                    ['Books', 'books']
                ];
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                foreach ($cats as $cat) {
                    $stmt->execute($cat);
                }
            }

            // Step 4: Create Admin
            // Check if admin/user exists to avoid duplicates or errors
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$admin_user]);
            if ($stmt->fetchColumn() == 0) {
                $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, is_admin, is_active, password_needs_reset, created_at) VALUES (?, ?, 1, 1, 1, NOW())");
                $stmt->execute([$admin_user, $hash]);
            }

            $success = true;

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install List-e-Moon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Vazirmatn', 'Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Vazirmatn', sans-serif !important; }
        .glass { background: rgba(30, 41, 59, 0.9); backdrop-filter: blur(12px); }
    </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex items-center justify-center p-4 bg-[url('https://tailwindcss.com/_next/static/media/hero-dark.92751102.png')] bg-cover bg-center">

    <div class="glass p-8 rounded-3xl shadow-2xl border border-slate-700/50 max-w-2xl w-full relative overflow-hidden">
        
        <?php if ($success): ?>
            <!-- Success Screen -->
            <div class="text-center py-8 animate-fade-in-up">
                <div class="w-24 h-24 bg-green-500/10 rounded-full flex items-center justify-center mx-auto mb-6 border border-green-500/30 shadow-[0_0_20px_rgba(34,197,94,0.3)]">
                    <svg class="w-12 h-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                
                <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-emerald-600 mb-4">
                    Installation Complete!
                </h1>
                
                <p class="text-slate-300 mb-8 max-w-md mx-auto">
                    List-e-Moon has been successfully installed. Your database is ready and the admin user created.
                </p>

                <div class="bg-red-500/10 border border-red-500/50 p-6 rounded-xl mb-8 text-red-200 shadow-inner flex items-start gap-4 text-left">
                    <svg class="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <strong class="block text-red-400 mb-1">SECURITY WARNING</strong>
                        Please delete this <code class="bg-red-900/50 px-1 rounded">install.php</code> file immediately before logging in to prevent unauthorized access.
                    </div>
                </div>

                <a href="index.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-10 rounded-full transition-all shadow-lg hover:shadow-indigo-500/25 transform hover:-translate-y-1">
                    Go to Login
                </a>
            </div>
        <?php else: ?>
            <!-- Installation Form -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-indigo-500/10 rounded-full flex items-center justify-center mx-auto mb-4 border border-indigo-500/30">
                    <i class="fas fa-rocket text-indigo-400 text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Install List-e-Moon</h1>
                <p class="text-slate-400">Configure your application settings below.</p>
            </div>

            <!-- Pre-Installation Guide -->
            <details class="bg-slate-800/50 border border-indigo-500/30 rounded-xl mb-8 group overflow-hidden transition-all duration-300">
                <summary class="flex items-center gap-3 p-4 cursor-pointer hover:bg-slate-700/50 transition-colors list-none select-none">
                    <i class="fas fa-info-circle text-indigo-400 text-lg"></i>
                    <span class="text-indigo-200 font-bold text-sm">Read Before Installing</span>
                    <i class="fas fa-chevron-down text-indigo-400/50 ml-auto group-open:rotate-180 transition-transform"></i>
                </summary>
                <div class="p-4 pt-0 text-left text-sm text-slate-300 space-y-3 bg-slate-800/20">
                    <div class="flex gap-3">
                        <i class="fas fa-database text-blue-400 mt-1"></i>
                        <div>
                            <strong class="text-white block">Database</strong>
                            You must manually create an empty MySQL database in your hosting panel (e.g., cPanel/phpMyAdmin) before filling out this form.
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <i class="fas fa-folder-open text-yellow-400 mt-1"></i>
                        <div>
                            <strong class="text-white block">Permissions</strong>
                            Ensure the <code class="text-xs bg-slate-900 px-1 rounded">config/</code> directory has 755 or 777 write permissions so this script can automatically generate the db.php file.
                            <br><span class="text-slate-400 text-xs">(Note: The standard permission for the install.php file itself is 644, but some strict hosts may require 755).</span>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <i class="fas fa-key text-purple-400 mt-1"></i>
                        <div>
                            <strong class="text-white block">API Keys</strong>
                            TMDB API key (for movies) and RAWG API key (for games) are optional but recommended. You must generate them from their respective developer portals.
                        </div>
                    </div>
                </div>
            </details>

            <?php if (!$is_writable): ?>
                <div class="bg-red-500/10 border border-red-500/50 p-4 rounded-lg mb-6 text-red-200 text-sm text-center">
                    <strong>Error:</strong> The <code>config/</code> directory is not writable. Please verify permissions.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/50 p-4 rounded-lg mb-6 text-red-200 text-sm text-center">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" <?php if(!$is_writable) echo 'hidden'; ?>>
                
                <!-- Database Section -->
                <div class="bg-slate-800/50 p-6 rounded-2xl border border-slate-700/50">
                    <h3 class="text-lg font-bold text-indigo-400 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                        Database Settings
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-slate-400 text-sm font-bold mb-2">Database Host</label>
                            <input type="text" name="db_host" value="localhost" class="w-full bg-slate-900 text-white border border-slate-700 rounded-lg p-3 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="localhost" required>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-sm font-bold mb-2">Database Name</label>
                            <input type="text" name="db_name" class="w-full bg-slate-900 text-white border border-slate-700 rounded-lg p-3 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="list_e_moon" required>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-sm font-bold mb-2">Database User</label>
                            <input type="text" name="db_user" class="w-full bg-slate-900 text-white border border-slate-700 rounded-lg p-3 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="root" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-slate-400 text-sm font-bold mb-2">Database Password</label>
                            <input type="password" name="db_pass" class="w-full bg-slate-900 text-white border border-slate-700 rounded-lg p-3 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <!-- Admin Section -->
                <div class="bg-slate-800/50 p-6 rounded-2xl border border-slate-700/50">
                    <h3 class="text-lg font-bold text-emerald-400 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Admin Account
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-400 text-sm font-bold mb-2">Admin Username</label>
                            <input type="text" name="admin_user" value="admin" class="w-full bg-slate-900 text-white border border-slate-700 rounded-lg p-3 focus:outline-none focus:border-emerald-500 transition-colors" required>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-sm font-bold mb-2">Admin Password</label>
                            <input type="password" name="admin_pass" class="w-full bg-slate-900 text-white border border-slate-700 rounded-lg p-3 focus:outline-none focus:border-emerald-500 transition-colors" placeholder="Secure Password" required>
                        </div>
                    </div>
                </div>

                <!-- API Keys Section -->
                <div class="bg-slate-800/50 p-6 rounded-2xl border border-slate-700/50">
                    <h3 class="text-lg font-bold text-purple-400 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11.536 19.464a2.41 2.41 0 01-1.708.707 2.41 2.41 0 01-1.708-.707l-1.414-1.414a2.41 2.41 0 01-.707-1.708V15.536a2.41 2.41 0 01.707-1.708l4.086-4.086A6 6 0 1121 9z"/></svg>
                        API Keys (Optional)
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-slate-400 text-sm font-bold mb-2">TMDB Key (Movies/Series)</label>
                            <input type="text" name="tmdb_key" class="w-full bg-slate-900 text-white border border-slate-700 rounded-lg p-3 focus:outline-none focus:border-purple-500 transition-colors" placeholder="v3 API Key">
                        </div>
                        <div>
                            <label class="block text-slate-400 text-sm font-bold mb-2">RAWG Key (Games)</label>
                            <input type="text" name="rawg_key" class="w-full bg-slate-900 text-white border border-slate-700 rounded-lg p-3 focus:outline-none focus:border-purple-500 transition-colors" placeholder="API Key">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold py-4 rounded-xl transition-all shadow-lg hover:shadow-indigo-500/25 transform hover:-translate-y-0.5">
                    Install List-e-Moon
                </button>

            </form>
        <?php endif; ?>
        

    </div>

</body>
</html>
