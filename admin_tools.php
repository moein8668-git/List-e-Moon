<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login();

// Admin Only
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    redirect('dashboard.php');
}

$flash = get_flash_message();

require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="flex items-center gap-4 mb-8 border-b border-slate-700 pb-4">
        <a href="dashboard.php" class="text-slate-400 hover:text-white transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <h1 class="text-3xl font-bold text-red-500">Admin Tools & Danger Zone</h1>
    </div>

    <?php if ($flash): ?>
        <div class="mb-6 p-4 rounded <?php echo $flash['type'] === 'error' ? 'bg-red-500/20 text-red-200 border border-red-500/50' : 'bg-green-500/20 text-green-200 border border-green-500/50'; ?>">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Cleanup Content -->
        <div class="bg-slate-800 p-6 rounded-lg border border-slate-700">
            <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Content Cleanup
            </h2>
            <p class="text-slate-400 text-sm mb-6">Bulk delete items by category. This action removes items and their ratings.</p>
            
            <form action="actions.php" method="POST" class="space-y-4" onsubmit="return confirm('WARNING: Are you sure you want to delete ALL Items?');">
                <input type="hidden" name="action" value="delete_all_items">
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded transition-colors">
                    Delete ALL Items
                </button>
            </form>
        </div>

        <!-- User Management -->
        <div class="bg-slate-800 p-6 rounded-lg border border-slate-700">
            <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                User Purge
            </h2>
            <p class="text-slate-400 text-sm mb-6">Remove all non-admin users. You (the current admin) will be safe.</p>
            
            <form action="actions.php" method="POST" class="space-y-4" onsubmit="return confirm('WARNING: This will delete ALL users except admins. Continue?');">
                <input type="hidden" name="action" value="delete_all_users">
                <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 rounded transition-colors">
                    Delete All Non-Admin Users
                </button>
            </form>
        </div>

        <!-- Factory Reset -->
        <div class="md:col-span-2 bg-red-900/20 p-6 rounded-lg border border-red-500/30">
            <h2 class="text-xl font-bold text-red-400 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                Factory Reset
            </h2>
            <p class="text-red-300 text-sm mb-6">Wipes EVERYTHING: Items, Ratings, and Non-Admin Users. Only your admin account remains.</p>
            
            <form action="actions.php" method="POST" onsubmit="return confirm('CRITICAL WARNING: This will WIPE THE ENTIRE DATABASE content. Are you ABSOLUTELY SURE?');">
                <input type="hidden" name="action" value="reset_everything">
                <button type="submit" class="w-full bg-red-800 hover:bg-red-900 text-white font-bold py-4 rounded transition-colors uppercase tracking-widest border border-red-600 shadow-xl shadow-red-900/50">
                    NUKE IT ALL (Reset System)
                </button>
            </form>
        </div>

    </div>
</div>
</body>
</html>
