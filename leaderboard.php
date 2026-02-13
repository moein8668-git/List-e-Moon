<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Allow public access or require login? 
// Prompt says "your choice, preferably logged in". Let's require login to encourage signups.
require_login();

// Page Logic
require_once 'includes/header.php';

global $pdo;

// Fetch Top Users (Ghost Mode active)
$stmt = $pdo->query("
    SELECT * FROM users 
    WHERE is_hidden = 0 
    AND is_active = 1 
    ORDER BY xp DESC 
    LIMIT 50
");
$users = $stmt->fetchAll();
?>

<div class="container mx-auto p-4 md:p-8 flex-grow">
    
    <div class="text-center mb-10">
        <h1 class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-500 mb-2">
            🏆 Hall of Fame
        </h1>
    </div>

    <div class="max-w-3xl mx-auto bg-slate-800 rounded-xl shadow-2xl border border-slate-700 overflow-hidden">
        
        <?php if (count($users) === 0): ?>
            <div class="p-8 text-center text-slate-500">
                No active users found. Be the first to join!
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-900/50 text-slate-400 text-xs uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="p-4 text-center w-16">Rank</th>
                            <th class="p-4">User</th>
                            <th class="p-4 text-right">Level</th>
                            <th class="p-4 text-right">Total XP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <?php 
                        $rank = 0;
                        foreach ($users as $u): 
                            $rank++;
                            $level = get_level_info($u['xp']);
                            
                            // Rank Styling
                            $rank_style = "text-slate-400 font-bold";
                            $row_bg = "";
                            
                            if ($rank === 1) {
                                $rank_style = "text-yellow-400 text-2xl drop-shadow-md";
                                $row_bg = "bg-yellow-500/5";
                            } elseif ($rank === 2) {
                                $rank_style = "text-slate-300 text-xl";
                                $row_bg = "bg-slate-500/5";
                            } elseif ($rank === 3) {
                                $rank_style = "text-orange-400 text-xl";
                                $row_bg = "bg-orange-500/5";
                            }
                            
                            // Highlight current user
                            if ($u['id'] == $_SESSION['user_id']) {
                                $row_bg = "bg-purple-900/20 border-l-4 border-purple-500";
                            }
                        ?>
                        <tr class="hover:bg-slate-700/30 transition-colors <?php echo $row_bg; ?>">
                            <td class="p-4 text-center">
                                <span class="<?php echo $rank_style; ?>">
                                    <?php if ($rank === 1): ?>👑<?php elseif ($rank === 2): ?>🥈<?php elseif ($rank === 3): ?>🥉<?php else: ?>#<?php echo $rank; ?><?php endif; ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <a href="user_profile.php?id=<?php echo $u['id']; ?>" class="flex items-center gap-3 group">
                                    <div class="w-10 h-10 rounded-full bg-slate-700 overflow-hidden border border-slate-600 group-hover:border-purple-500 transition-colors">
                                        <?php if (!empty($u['avatar_path']) && file_exists(UPLOAD_DIR . 'avatars/' . $u['avatar_path'])): ?>
                                            <img src="uploads/avatars/<?php echo htmlspecialchars($u['avatar_path']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-xs font-bold text-white bg-slate-600">
                                                <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="font-bold text-white group-hover:text-purple-400 transition-colors">
                                            <?php echo htmlspecialchars($u['username']); ?>
                                            <?php if ($u['is_admin']): ?>
                                                <span class="ml-1 text-[10px] bg-red-900/50 text-red-300 px-1 py-0.5 rounded border border-red-800">ADMIN</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td class="p-4 text-right">
                                <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-slate-900/50 border border-slate-700">
                                    <span class="text-lg"><?php echo $level['icon']; ?></span>
                                    <span class="text-xs font-bold text-slate-300"><?php echo $level['title']; ?> <span class="text-slate-500">L<?php echo $level['level']; ?></span></span>
                                </div>
                            </td>
                            <td class="p-4 text-right font-mono font-bold text-purple-300">
                                <?php echo number_format($u['xp']); ?> XP
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
