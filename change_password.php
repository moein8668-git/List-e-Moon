<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Manually check login state to avoid infinite redirect loop from require_login()
if (!is_logged_in()) {
    redirect('index.php');
}

// If user does NOT need reset, send them to dashboard
if (!isset($_SESSION['password_needs_reset']) || !$_SESSION['password_needs_reset']) {
    redirect('dashboard.php');
}

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - List-e-Moon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-900 text-white flex items-center justify-center min-h-screen">
    <div class="bg-slate-800 p-8 rounded-lg shadow-lg w-full max-w-md border border-slate-700">
        <h2 class="text-2xl font-bold text-center mb-2 text-yellow-400">Password Change Required</h2>
        <p class="text-center text-slate-400 mb-6 text-sm">Please Update your temporary password to continue.</p>
        
        <?php if ($flash): ?>
            <div class="mb-4 p-3 rounded <?php echo $flash['type'] === 'error' ? 'bg-red-500/20 text-red-200 border border-red-500/50' : 'bg-green-500/20 text-green-200 border border-green-500/50'; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <form action="actions.php" method="POST" class="space-y-6">
            <input type="hidden" name="action" value="change_password_initial">
            
            <div>
                <label class="block text-sm font-medium mb-2 text-slate-300">New Password</label>
                <input type="password" name="new_password" required minlength="6"
                    class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-white focus:outline-none focus:border-yellow-500 transition-colors">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2 text-slate-300">Confirm Password</label>
                <input type="password" name="confirm_password" required minlength="6"
                    class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-white focus:outline-none focus:border-yellow-500 transition-colors">
            </div>
            
            <button type="submit" 
                class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded transition-colors">
                Update Password
            </button>
        </form>
    </div>
</body>
</html>
