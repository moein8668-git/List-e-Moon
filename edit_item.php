<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login();

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
global $pdo;

// Fetch Item
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    set_flash_message('error', 'Item not found.');
    redirect('dashboard.php');
}

// Security Check: Admin or Owner
if (!isset($_SESSION['is_admin']) || (!$_SESSION['is_admin'] && $_SESSION['user_id'] != $item['added_by_user_id'])) {
    set_flash_message('error', 'You do not have permission to edit this item.');
    redirect("view_item.php?id=$item_id");
}

// Fetch Categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - List-e-Moon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col">
    <nav class="bg-slate-800 border-b border-slate-700 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold text-purple-400">Edit Item</h1>
            <a href="view_item.php?id=<?php echo $item['id']; ?>" class="text-slate-300 hover:text-white transition-colors">Cancel</a>
        </div>
    </nav>
    
    <div class="container mx-auto p-6 max-w-2xl">
        <div class="bg-slate-800 p-6 rounded-lg border border-slate-700 shadow-xl">
            <h2 class="text-xl font-bold mb-6 text-white border-b border-slate-700 pb-2">Update Details</h2>
            
            <form action="actions.php" method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="action" value="update_item">
                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">

                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Category</label>
                    <select name="category_id" class="w-full bg-slate-900 border border-slate-700 rounded p-3 focus:outline-none focus:border-purple-500 text-white">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $item['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required class="w-full bg-slate-900 border border-slate-700 rounded p-3 focus:outline-none focus:border-purple-500 text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Description</label>
                    <textarea name="description" rows="5" class="w-full bg-slate-900 border border-slate-700 rounded p-3 focus:outline-none focus:border-purple-500 text-white"><?php echo htmlspecialchars($item['description']); ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2 text-slate-300">Cover Image</label>
                    <div class="flex items-start gap-4 mb-3">
                        <?php if ($item['local_image_path']): ?>
                            <div class="shrink-0">
                                <img src="<?php echo 'uploads/thumbs/' . htmlspecialchars($item['local_image_path']); ?>" alt="Current Cover" class="w-24 h-auto rounded border border-slate-600">
                                <p class="text-[10px] text-slate-500 text-center mt-1">Current</p>
                            </div>
                        <?php endif; ?>
                        <div class="flex-grow">
                             <input type="file" name="cover_image" accept="image/*" class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-600 file:text-white hover:file:bg-purple-700 cursor-pointer">
                             <p class="text-xs text-slate-500 mt-2">Upload a new image to replace the current one. Leave empty to keep existing.</p>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex gap-4">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded transition-colors shadow-lg">Save Changes</button>
                    <a href="view_item.php?id=<?php echo $item['id']; ?>" class="flex-none px-6 py-3 rounded bg-slate-700 hover:bg-slate-600 text-slate-300 font-medium transition-colors">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
