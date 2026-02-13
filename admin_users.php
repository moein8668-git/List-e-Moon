<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login();

if (!$_SESSION['is_admin']) {
    redirect('dashboard.php');
}

$flash = get_flash_message();

global $pdo;
$stmt = $pdo->query("SELECT id, username, is_admin, is_active, is_hidden, created_at, avatar_path, xp FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Include the new responsive header
$page_title = 'Admin Panel';
require_once 'includes/header.php';
?>

<div class="container mx-auto p-4 md:p-6 max-w-6xl">
    <?php if ($flash): ?>
        <div class="mb-6 p-4 rounded <?php echo $flash['type'] === 'error' ? 'bg-red-500/20 text-red-200 border border-red-500/50' : 'bg-green-500/20 text-green-200 border border-green-500/50'; ?>">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Create User Form -->
        <div class="bg-slate-800 p-6 rounded-lg border border-slate-700 h-fit shadow-md">
            <h2 class="text-xl font-bold mb-4 text-purple-400 border-b border-slate-700 pb-2">Create New User</h2>
            <form action="actions.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create_user">
                
                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Username</label>
                    <input type="text" name="username" required class="w-full bg-slate-900 border border-slate-700 rounded p-2 focus:outline-none focus:border-purple-500 text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Temporary Password</label>
                    <input type="text" name="password" required class="w-full bg-slate-900 border border-slate-700 rounded p-2 focus:outline-none focus:border-purple-500 text-white">
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="is_admin" id="is_admin" class="mr-2 rounded bg-slate-700 border-slate-600 text-purple-600 focus:ring-purple-500">
                    <label for="is_admin" class="text-sm text-slate-300 cursor-pointer">Grant Admin Privileges</label>
                </div>
                
                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 px-4 rounded transition-colors shadow-lg">Create User</button>
                <p class="text-xs text-slate-500 mt-2">* User must change password on first login.</p>
            </form>
        </div>

        <!-- User List -->
        <div class="lg:col-span-2 bg-slate-800 p-6 rounded-lg border border-slate-700 shadow-md overflow-hidden">
            <h2 class="text-xl font-bold mb-4 text-purple-400 border-b border-slate-700 pb-2">Existing Users</h2>
            <!-- Mobile View: Cards (Disabled in favor of Responsive Table) -->
            <div class="hidden space-y-4">
                <?php foreach ($users as $u): ?>
                    <!-- ... cards hidden ... -->
                <?php endforeach; ?>
            </div>

            <!-- Responsive User Table -->
            <div class="overflow-x-auto" style="-webkit-overflow-scrolling: touch;">
                <table class="w-full text-left text-sm text-slate-300 whitespace-nowrap">
                    <thead class="bg-slate-900 text-slate-400 uppercase font-semibold">
                        <tr>
                            <th class="p-3">User</th>
                            <th class="p-3">Avatar</th>
                            <th class="p-3">Level / XP</th>
                            <th class="p-3">Role</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-700/50 transition-colors">
                            <td class="p-3 font-medium text-white"><?php echo htmlspecialchars($u['username']); ?></td>
                            <td class="p-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-slate-700 overflow-hidden border border-slate-600">
                                        <?php if (!empty($u['avatar_path']) && file_exists(UPLOAD_DIR . 'avatars/' . $u['avatar_path'])): ?>
                                            <img src="uploads/avatars/<?php echo htmlspecialchars($u['avatar_path']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-[10px] font-bold text-white bg-slate-600">N/A</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($u['avatar_path'])): ?>
                                        <form action="actions.php" method="POST" onsubmit="return confirm('Remove avatar?');">
                                            <input type="hidden" name="action" value="remove_avatar">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <input type="hidden" name="ref" value="admin">
                                            <button type="submit" class="text-orange-400 hover:text-orange-300 text-xs font-bold" title="Delete Avatar">
                                                [x]
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-3">
                                <?php 
                                    $lvl = get_level_info($u['xp'] ?? 0);
                                ?>
                                <div class="text-xs">
                                    <div class="font-bold text-purple-300"><?php echo $lvl['title']; ?> (L<?php echo $lvl['level']; ?>)</div>
                                    <div class="text-slate-500"><?php echo number_format($u['xp'] ?? 0); ?> XP</div>
                                </div>
                            </td>
                            <td class="p-3">
                                <?php if ($u['is_admin']): ?>
                                    <span class="bg-purple-900 text-purple-300 px-2 py-0.5 rounded text-xs font-bold border border-purple-700">Admin</span>
                                <?php else: ?>
                                    <span class="bg-slate-700 text-slate-300 px-2 py-0.5 rounded text-xs border border-slate-600">User</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <?php if (isset($u['is_active']) && $u['is_active']): ?>
                                    <span class="text-green-400 flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500"></span> Active</span>
                                <?php else: ?>
                                    <span class="text-red-400 flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span> Banned</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Toggle Status -->
                                    <form action="actions.php" method="POST" onsubmit="return confirm('Change status?');">
                                        <input type="hidden" name="action" value="toggle_user_status">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $u['is_active'] ?? 1; ?>">
                                        <button type="submit" class="px-3 py-1 rounded text-xs font-bold text-white transition-colors <?php echo ($u['is_active'] ?? 1) ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'; ?>">
                                            <?php echo ($u['is_active'] ?? 1) ? 'Ban' : 'Unban'; ?>
                                        </button>
                                    </form>

                                    <!-- Toggle Ghost (Hidden) -->
                                    <form method="POST" action="actions.php" class="inline-block">
                                        <input type="hidden" name="action" value="toggle_hidden">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="current_hidden" value="<?php echo ($u['is_hidden'] == 1) ? '1' : '0'; ?>">
                                        
                                        <?php if ($u['is_hidden'] == 1): ?>
                                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs transition flex items-center gap-1" title="Make Visible">
                                                <i class="fas fa-eye-slash"></i> <span>Show</span>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition flex items-center gap-1" title="Hide User">
                                                <i class="fas fa-eye"></i> <span>Hide</span>
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                    <!-- Reset Password -->
                                    <button onclick="document.getElementById('reset-form-<?php echo $u['id']; ?>').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-bold transition-colors">
                                        Reset PW
                                    </button>
                                    
                                     <!-- Delete User -->
                                    <form action="actions.php" method="POST" onsubmit="return confirm('WARNING: This will delete use \'<?php echo htmlspecialchars($u['username']); ?>\' AND all their ratings. Continue?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="bg-red-800 hover:bg-red-900 text-white px-2 py-1 rounded text-xs font-bold transition-colors">
                                            X
                                        </button>
                                    </form>
                                </div>
                                <div id="reset-form-<?php echo $u['id']; ?>" class="hidden mt-2 p-2 bg-slate-900 rounded border border-slate-700">
                                    <form action="actions.php" method="POST" class="flex gap-2">
                                        <input type="hidden" name="action" value="admin_reset_password">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="text" name="new_password" placeholder="New Pass" required class="w-24 px-2 py-1 text-xs rounded bg-slate-800 border border-slate-600 text-white">
                                        <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-2 py-1 rounded text-xs">Save</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
