<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - List-e-Moon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-white flex items-center justify-center min-h-screen">
    <div class="bg-slate-800 p-8 rounded-lg shadow-lg w-full max-w-md border border-slate-700">
        <h1 class="text-3xl font-bold text-center mb-6 text-purple-400">List-e-Moon</h1>
        
        <?php if ($flash): ?>
            <div class="mb-4 p-3 rounded <?php echo $flash['type'] === 'error' ? 'bg-red-500/20 text-red-200 border border-red-500/50' : 'bg-green-500/20 text-green-200 border border-green-500/50'; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <form action="actions.php" method="POST" class="space-y-6">
            <input type="hidden" name="action" value="login">
            
            <div>
                <label class="block text-sm font-medium mb-2 text-slate-300">Username</label>
                <input type="text" name="username" required 
                    class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-white focus:outline-none focus:border-purple-500 transition-colors">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2 text-slate-300">Password</label>
                <input type="password" name="password" required 
                    class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-white focus:outline-none focus:border-purple-500 transition-colors">
            </div>
            
            <button type="submit" 
                class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded transition-colors">
                Login
            </button>
        </form>
    </div>
</body>
</html>
