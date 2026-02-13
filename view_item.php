<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login();

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
global $pdo;

// Fetch Item Details
$stmt = $pdo->prepare("
    SELECT i.*, c.name as category_name, u.username as added_by 
    FROM items i 
    JOIN categories c ON i.category_id = c.id 
    JOIN users u ON i.added_by_user_id = u.id 
    WHERE i.id = ?
");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    redirect('dashboard.php');
}

// Fetch Ratings
$stmt = $pdo->prepare("
    SELECT r.*, u.username 
    FROM ratings r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.item_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$item_id]);
$ratings = $stmt->fetchAll();

// Calculate Average
$total_score = 0;
$count = count($ratings);
if ($count > 0) {
    foreach ($ratings as $r) $total_score += $r['score'];
    $avg_score = round($total_score / $count, 1);
} else {
    $avg_score = 'N/A';
}

// Check if current user has voted
$user_vote = null;
foreach ($ratings as $r) {
    if ($r['user_id'] == $_SESSION['user_id']) {
        $user_vote = $r['score'];
        break;
    }
}

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['title']); ?> - List-e-Moon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col">
    <nav class="bg-slate-800 border-b border-slate-700 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold text-purple-400">List-e-Moon</h1>
            <a href="dashboard.php" class="text-slate-300 hover:text-white transition-colors text-sm md:text-base">
                <span class="md:hidden">&lt; Back</span>
                <span class="hidden md:inline">Back to Dashboard</span>
            </a>
        </div>
    </nav>
    
    <!-- Main Content Wrapper (No Padding on Mobile for Edge-to-Edge) -->
    <div class="w-full md:container md:mx-auto md:px-4 md:py-8 max-w-6xl">
        
        <?php if ($flash): ?>
            <div class="mx-4 md:mx-0 mb-6 p-4 rounded <?php echo $flash['type'] === 'error' ? 'bg-red-500/20 text-red-200 border border-red-500/50' : 'bg-green-500/20 text-green-200 border border-green-500/50'; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-col md:flex-row gap-0 md:gap-8 items-start">
            
            <!-- Left Column: Image (Edge-to-Edge on Mobile) -->
            <div class="w-full md:w-1/3 flex flex-col relative z-0">
                <div class="w-full relative shadow-2xl bg-slate-800 md:rounded-xl overflow-hidden group">
                     <img src="<?php echo 'uploads/thumbs/' . htmlspecialchars($item['local_image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($item['title']); ?>"
                         class="w-full h-auto max-h-[500px] object-cover block md:group-hover:scale-105 transition-transform duration-700">
                     
                     <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-transparent to-transparent md:hidden"></div>
                     
                     <!-- Mobile Title Overlay (Visible only on Mobile) -->
                     <div class="absolute bottom-0 left-0 right-0 p-6 md:hidden">
                        <span class="bg-purple-600/90 backdrop-blur text-white text-[10px] font-bold px-2 py-1 rounded mb-2 inline-block uppercase tracking-wider"><?php echo htmlspecialchars($item['category_name']); ?></span>
                        <h1 class="text-3xl font-bold text-white leading-tight drop-shadow-md"><?php echo htmlspecialchars($item['title']); ?></h1>
                     </div>

                     <?php if(isset($item['external_rating']) && $item['external_rating'] > 0): ?>
                        <div class="absolute top-4 right-4 bg-black/60 text-yellow-400 font-bold px-3 py-1.5 rounded-full border border-yellow-500/30 text-sm backdrop-blur-md shadow-lg">
                            ★ <?php echo $item['external_rating']; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Desktop-Only Meta (Hidden on Mobile, moved to body) -->
                <div class="hidden md:block mt-6 space-y-4">
                    <div class="bg-slate-800/80 p-6 rounded-xl border border-slate-700/50 text-center backdrop-blur">
                        <span class="block text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-500"><?php echo $avg_score; ?></span>
                        <span class="text-xs uppercase tracking-widest text-slate-400 mt-2 block">Average Score</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Details -->
            <div class="w-full md:w-2/3 flex flex-col gap-6 px-4 py-6 md:p-0 md:pt-2">
                
                <!-- Desktop Title (Hidden on Mobile) -->
                <div class="hidden md:block">
                    <span class="bg-purple-600 text-white text-xs font-bold px-2 py-1 rounded mb-2 inline-block uppercase tracking-wider shadow-lg shadow-purple-900/20"><?php echo htmlspecialchars($item['category_name']); ?></span>
                    <h1 class="text-5xl font-bold text-white mb-2 leading-tight"><?php echo htmlspecialchars($item['title']); ?></h1>
                </div>

                <!-- Admin Tools -->
                <!-- Actions: Edit/Delete/Watchlist -->
                <div class="flex flex-wrap gap-3 mb-4">
                    <!-- Watchlist Toggle -->
                    <?php
                    // Check Watchlist Status
                    $w_stmt = $pdo->prepare("SELECT id FROM watchlist WHERE user_id = ? AND item_id = ?");
                    $w_stmt->execute([$_SESSION['user_id'], $item['id']]);
                    $in_watchlist = $w_stmt->fetch();
                    ?>
                    <form action="actions.php" method="POST">
                        <input type="hidden" name="action" value="toggle_watchlist">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        <button type="submit" class="<?php echo $in_watchlist ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-slate-700 hover:bg-slate-600'; ?> text-white font-bold py-3 px-6 rounded transition-colors text-xs uppercase tracking-wider flex items-center gap-2">
                            <span><?php echo $in_watchlist ? '★ Saved' : '☆ Watchlist'; ?></span>
                        </button>
                    </form>

                    <?php if ((isset($_SESSION['is_admin']) && $_SESSION['is_admin']) || ($_SESSION['user_id'] == $item['added_by_user_id'])): ?>
                         <a href="edit_item.php?id=<?php echo $item['id']; ?>" class="bg-blue-600/10 hover:bg-blue-600/20 text-blue-500 border border-blue-600/30 text-xs font-bold py-3 px-6 rounded transition-all uppercase tracking-wider">
                            Edit
                        </a>
                        
                        <form action="actions.php" method="POST" onsubmit="return confirm('Delete this item?');">
                            <input type="hidden" name="action" value="delete_item">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="bg-red-600/10 hover:bg-red-600/20 text-red-500 border border-red-600/30 text-xs font-bold py-3 px-6 rounded transition-all uppercase tracking-wider">
                                Delete
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Added By User Info (with Avatar) -->
                <div class="flex items-center gap-3 bg-slate-800/50 p-3 rounded border border-slate-700/50 w-fit">
                    <?php 
                    // Fetch User Data for Creator
                    $u_stmt = $pdo->prepare("SELECT avatar_path, xp FROM users WHERE id = ?");
                    $u_stmt->execute([$item['added_by_user_id']]);
                    $creator = $u_stmt->fetch();
                    $creator_lvl = get_level_info($creator['xp'] ?? 0);
                    ?>
                    <div class="w-10 h-10 rounded-full bg-slate-700 overflow-hidden border border-slate-600 flex-shrink-0">
                         <?php if (!empty($creator['avatar_path']) && file_exists(UPLOAD_DIR . 'avatars/' . $creator['avatar_path'])): ?>
                                <img src="uploads/avatars/<?php echo htmlspecialchars($creator['avatar_path']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-white font-bold text-xs"><?php echo substr($item['added_by'],0,1); ?></div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Added by</p>
                        <p class="text-sm font-bold text-white flex items-center gap-2">
                            <a href="user_profile.php?id=<?php echo $item['added_by_user_id']; ?>" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                                <?php echo htmlspecialchars($item['added_by']); ?>
                                <span class="text-[10px] bg-slate-700 px-1.5 rounded text-purple-300" title="<?php echo $creator_lvl['title']; ?>">Lvl <?php echo $creator_lvl['level']; ?></span>
                            </a>
                        </p>
                    </div>
                </div>


                <!-- Details & Vote (Mobile Optimization) -->
                <div class="space-y-6">
                    <!-- Mobile Score (Visible only on Mobile) -->
                    <div class="md:hidden flex items-center justify-between bg-slate-800 p-4 rounded-lg border border-slate-700">
                        <span class="text-slate-400 font-bold uppercase text-sm">Average Score</span>
                        <span class="text-3xl font-bold text-yellow-500"><?php echo $avg_score; ?></span>
                    </div>

                     <!-- Description -->
                    <div class="prose prose-invert prose-lg max-w-none text-slate-300 leading-relaxed" style="font-family: 'Vazirmatn', 'Inter', sans-serif !important;">
                        <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                    </div>
                    
                    <div class="border-t border-slate-800 my-6"></div>

                     <!-- Voting Section -->
                    <div class="bg-slate-800 p-6 rounded-lg border border-slate-700 shadow-lg">
                    <h3 class="text-lg font-bold mb-4 border-b border-slate-700 pb-2">Your Rating</h3>
                    <form action="actions.php" method="POST" class="flex flex-col gap-4">
                        <input type="hidden" name="action" value="rate_item">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        
                        <!-- 1-10 Radio Buttons (Responsive Flex Wrap) -->
                        <div class="flex flex-wrap gap-2 justify-center md:justify-start">
                            <?php for($i=1; $i<=10; $i++): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="score" value="<?php echo $i; ?>" class="peer sr-only" <?php echo ($user_vote == $i) ? 'checked' : ''; ?>>
                                    <div class="w-8 h-8 md:w-10 md:h-10 rounded-full flex items-center justify-center bg-slate-700 peer-checked:bg-yellow-500 peer-checked:text-black font-bold hover:bg-slate-600 transition-colors text-sm md:text-base">
                                        <?php echo $i; ?>
                                    </div>
                                </label>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="mt-2 text-center md:text-left">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded transition-colors shadow-lg">
                                <?php echo $user_vote ? 'Update Rating' : 'Submit Rating'; ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Community Votes -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Community Votes</h3>
                    <?php 
                    // Refetch Ratings with User Details (Avatar, XP)
                    global $pdo;
                    $rParams = [$item_id];
                    $rSql = "SELECT r.*, u.username, u.avatar_path, u.xp
                             FROM ratings r 
                             JOIN users u ON r.user_id = u.id 
                             WHERE r.item_id = ? 
                             ORDER BY r.created_at DESC";
                    $rStmt = $pdo->prepare($rSql);
                    $rStmt->execute($rParams);
                    $ratings_enhanced = $rStmt->fetchAll();
                    ?>

                    <?php if (count($ratings_enhanced) > 0): ?>
                        <div class="space-y-4 max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                            <?php foreach ($ratings_enhanced as $r): ?>
                                <!-- Single Vote Row (Grid Layout: Left | Center | Right) -->
                                <div class="bg-slate-800 p-3 rounded-lg border border-slate-700 grid grid-cols-[1fr_auto_1fr] items-center gap-4">
                                     
                                     <!-- Left: User Info (Justify Start) -->
                                     <div class="flex items-center gap-3 justify-self-start min-w-0">
                                         <!-- Avatar -->
                                         <a href="user_profile.php?id=<?php echo $r['user_id']; ?>" class="flex-shrink-0 relative group block">
                                            <div class="w-10 h-10 rounded-full bg-slate-700 overflow-hidden border border-slate-600 transition-transform group-hover:scale-105">
                                                <?php if (!empty($r['avatar_path']) && file_exists(UPLOAD_DIR . 'avatars/' . $r['avatar_path'])): ?>
                                                    <img src="uploads/avatars/<?php echo htmlspecialchars($r['avatar_path']); ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <div class="w-full h-full flex items-center justify-center text-xs font-bold text-white bg-slate-600">
                                                        <?php echo strtoupper(substr($r['username'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <!-- Level Badge (Overlay) -->
                                            <?php 
                                                $lvl_num = function_exists('get_level_info') ? get_level_info($r['xp'] ?? 0)['level'] : '?';
                                            ?>
                                            <span class="absolute -bottom-1 -right-1 bg-slate-900 text-[9px] text-purple-400 font-bold px-1 rounded border border-purple-500/30">
                                                L<?php echo $lvl_num; ?>
                                            </span>
                                         </a>
                                         
                                         <!-- Name -->
                                        <a href="user_profile.php?id=<?php echo $r['user_id']; ?>" class="font-bold text-slate-200 hover:text-blue-400 transition-colors text-sm truncate block"><?php echo htmlspecialchars($r['username']); ?></a>
                                     </div>

                                     <!-- Center: Rating (Justify Center) -->
                                     <div class="justify-self-center flex flex-col items-center">
                                         <div class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-br from-yellow-300 to-yellow-600 drop-shadow-sm">
                                             <?php echo $r['score']; ?>
                                         </div>
                                         <div class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Score</div>
                                     </div>

                                     <!-- Right: Meta & Actions (Justify End) -->
                                     <div class="justify-self-end flex items-center gap-4">
                                         <span class="text-xs text-slate-500 hidden sm:inline-block"><?php echo date('M j, Y', strtotime($r['created_at'])); ?></span>
                                         
                                         <!-- Actions -->
                                         <div class="flex items-center gap-2">
                                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                                <!-- Delete Avatar (Admin) -->
                                                 <?php if (!empty($r['avatar_path'])): ?>
                                                    <form action="actions.php" method="POST" onsubmit="return confirm('Remove avatar?');" class="inline-block" title="Remove Avatar">
                                                        <input type="hidden" name="action" value="remove_avatar">
                                                        <input type="hidden" name="user_id" value="<?php echo $r['user_id']; ?>">
                                                        <input type="hidden" name="ref" value="item">
                                                        <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                                        <button type="submit" class="text-orange-400 hover:text-orange-300 opacity-50 hover:opacity-100 transition-opacity">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <!-- Delete Rating (Admin) -->
                                                <form action="actions.php" method="POST" onsubmit="return confirm('Delete rating?');" class="inline-block" title="Delete Rating">
                                                    <input type="hidden" name="action" value="delete_rating">
                                                    <input type="hidden" name="rating_id" value="<?php echo $r['id']; ?>">
                                                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                                    <button type="submit" class="text-red-400 hover:text-red-300 opacity-50 hover:opacity-100 transition-opacity">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </form>
                                            <?php elseif ($_SESSION['user_id'] == $r['user_id']): ?>
                                                <!-- Delete Own Rating -->
                                                <form action="actions.php" method="POST" onsubmit="return confirm('Delete your rating?');">
                                                    <input type="hidden" name="action" value="delete_rating">
                                                    <input type="hidden" name="rating_id" value="<?php echo $r['id']; ?>">
                                                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                                    <button type="submit" class="text-slate-500 hover:text-red-400 text-xs transition-colors">
                                                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                         </div>
                                     </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-slate-800/30 p-4 rounded border border-slate-700/30 text-slate-500 italic text-center">
                            No ratings yet. Be the first to vote!
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
