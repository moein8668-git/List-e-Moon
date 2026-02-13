<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Public page, but might want to require login to view others? 
// Context says "Users want to click...", usually implies logged in users, but could be public.
// Let's require login for consistency with the rest of the app (except index).
require_login(); 

global $pdo;

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id === 0) {
    redirect('dashboard.php');
}

// 1. Fetch User Info
$stmt = $pdo->prepare("SELECT id, username, avatar_path, xp, created_at, is_admin FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    set_flash_message('error', 'User not found.');
    redirect('dashboard.php');
}

// Level Info
$level_info = get_level_info($profile_user['xp']);

// 2. Fetch Top Rated Items by User (Join Items)
$rStmt = $pdo->prepare("
    SELECT r.score, r.created_at as rated_at, i.*, c.name as category_name
    FROM ratings r
    JOIN items i ON r.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    WHERE r.user_id = ?
    ORDER BY r.score DESC, r.created_at DESC
    LIMIT 20
");
$rStmt->execute([$user_id]);
$ratings = $rStmt->fetchAll();

// 3. Fetch Items Added by User
$iStmt = $pdo->prepare("
    SELECT i.*, c.name as category_name
    FROM items i
    JOIN categories c ON i.category_id = c.id
    WHERE i.added_by_user_id = ?
    ORDER BY i.external_rating DESC, i.created_at DESC
    LIMIT 20
");
$iStmt->execute([$user_id]);
$contributions = $iStmt->fetchAll();

$page_title = $profile_user['username'] . "'s Profile";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - List-e-Moon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col">
    <?php require_once 'includes/header.php'; ?>

    <div class="container mx-auto p-4 md:p-8 flex-grow">
        
        <!-- Header Section -->
        <div class="bg-slate-800 rounded-xl p-6 md:p-8 mb-8 border border-slate-700 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-500 to-purple-500"></div>
            
            <div class="flex flex-col md:flex-row items-center gap-6 md:gap-8 relative z-10">
                <!-- Avatar -->
                <div class="w-32 h-32 md:w-40 md:h-40 rounded-full bg-slate-700 border-4 border-slate-600 flex items-center justify-center overflow-hidden shadow-lg flex-shrink-0">
                    <?php if (!empty($profile_user['avatar_path']) && file_exists(UPLOAD_DIR . 'avatars/' . $profile_user['avatar_path'])): ?>
                        <img src="<?php echo 'uploads/avatars/' . htmlspecialchars($profile_user['avatar_path']); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="text-5xl font-bold text-slate-500"><?php echo strtoupper(substr($profile_user['username'], 0, 1)); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="text-center md:text-left flex-grow">
                    <div class="flex items-center justify-center md:justify-start gap-3 mb-2">
                        <h1 class="text-3xl md:text-4xl font-bold text-white"><?php echo htmlspecialchars($profile_user['username']); ?></h1>
                        <?php if ($profile_user['is_admin']): ?>
                            <span class="bg-red-500/20 text-red-300 text-xs font-bold px-2 py-1 rounded border border-red-500/30 uppercase tracking-wider">Admin</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 mb-4 text-sm font-medium">
                        <div class="flex items-center gap-1.5 text-purple-400 bg-purple-400/10 px-3 py-1 rounded-full border border-purple-400/20">
                            <span class="text-lg"><?php echo $level_info['icon'] ?? '🏅'; ?></span>
                            <span><?php echo $level_info['title']; ?> (Lvl <?php echo $level_info['level']; ?>)</span>
                        </div>
                        <div class="text-slate-400 bg-slate-700/50 px-3 py-1 rounded-full border border-slate-600">
                           <?php echo number_format($profile_user['xp']); ?> XP
                        </div>
                        <div class="text-slate-500 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Joined <?php echo date('M Y', strtotime($profile_user['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Section A: Top Rated -->
            <div>
                <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-500 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    Top Rated
                </h2>
                
                <?php if (empty($ratings)): ?>
                    <p class="text-slate-500 italic bg-slate-800/50 p-6 rounded-lg border border-slate-700">Hasn't rated anything yet.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($ratings as $r): ?>
                            <div class="bg-slate-800 p-4 rounded-lg flex items-center gap-4 hover:bg-slate-750 transition-colors border border-slate-700/50 group">
                                <a href="view_item.php?id=<?php echo $r['id']; ?>" class="block w-16 h-16 flex-shrink-0 relative overflow-hidden rounded bg-slate-900">
                                    <img src="<?php echo 'uploads/thumbs/' . htmlspecialchars($r['local_image_path']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                </a>
                                <div class="flex-grow min-w-0">
                                    <a href="view_item.php?id=<?php echo $r['id']; ?>" class="font-bold text-white hover:text-blue-400 truncate block text-lg mb-1">
                                        <?php echo htmlspecialchars($r['title']); ?>
                                    </a>
                                    <span class="text-xs text-purple-300 bg-purple-900/40 px-2 py-0.5 rounded border border-purple-500/20">
                                        <?php echo htmlspecialchars($r['category_name']); ?>
                                    </span>
                                </div>
                                <div class="flex-shrink-0 flex flex-col items-center">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg <?php echo $r['score'] >= 8 ? 'bg-green-500 text-white' : ($r['score'] >= 5 ? 'bg-yellow-500 text-slate-900' : 'bg-red-500 text-white'); ?>">
                                        <?php echo $r['score']; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section B: Contributions -->
            <div>
                 <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-500 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Contributions
                </h2>

                <?php if (empty($contributions)): ?>
                    <p class="text-slate-500 italic bg-slate-800/50 p-6 rounded-lg border border-slate-700">Hasn't added any items yet.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                         <?php foreach ($contributions as $item): ?>
                             <a href="view_item.php?id=<?php echo $item['id']; ?>" class="bg-slate-800 rounded-lg overflow-hidden border border-slate-700 group hover:border-slate-500 transition-colors block">
                                <div class="aspect-video relative overflow-hidden">
                                     <img src="<?php echo 'uploads/thumbs/' . htmlspecialchars($item['local_image_path']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                     <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-3 pt-8">
                                         <h4 class="text-white font-bold text-sm truncate"><?php echo htmlspecialchars($item['title']); ?></h4>
                                         <span class="text-[10px] text-slate-300"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                     </div>
                                </div>
                             </a>
                         <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</body>
</html>
