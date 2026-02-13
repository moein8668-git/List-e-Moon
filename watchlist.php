<?php
// 1. جلوگیری از چاپ شدن ارورها در صفحه (برای اینکه ظاهر به هم نریزه)
error_reporting(0);
ini_set('display_errors', 0);

require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login();

global $pdo;

// 2. اصلاح کوئری: اضافه کردن display_order برای اینکه جابجایی کار کنه
$stmt = $pdo->prepare("
    SELECT i.*, w.id as watchlist_id, c.name as category_name, w.display_order
    FROM watchlist w
    JOIN items i ON w.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    WHERE w.user_id = ?
    ORDER BY w.display_order ASC, w.added_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Watchlist - List-e-Moon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <style> body { font-family: 'Inter', sans-serif; } .sortable-ghost { opacity: 0.5; background: #334155; } </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col">
    <?php require_once 'includes/header.php'; ?>

    <div class="container mx-auto p-4 md:p-8 flex-grow">
        <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-500 mb-6">My Watchlist</h1>
        
        <?php if (count($items) === 0): ?>
            <div class="bg-slate-800 p-8 rounded-lg border border-slate-700 text-center">
                <p class="text-slate-400 mb-4">Your watchlist is empty.</p>
                <a href="dashboard.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded transition-colors">Browse Items</a>
            </div>
        <?php else: ?>
            <div id="watchlist-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
                <?php foreach ($items as $item): ?>
                    <?php 
                        // متغیرهای امن (اگه عکس یا تایتل نبود، دیفالت بذاره)
                        $itemId = $item['id']; // اینجا مشکل قبلی بود که حل شد
                        $title = htmlspecialchars($item['title'] ?? 'Untitled');
                        $image = htmlspecialchars($item['local_image_path'] ?? 'default.jpg');
                        $category = htmlspecialchars($item['category_name'] ?? 'General');
                    ?>
                    <div data-id="<?php echo $itemId; ?>" class="bg-slate-800 rounded-lg overflow-hidden shadow-lg border border-slate-700 flex flex-col relative group cursor-move">
                        
                        <form action="actions.php" method="POST" class="absolute top-2 right-2 z-10 opacity-0 group-hover:opacity-100 transition-opacity">
                            <input type="hidden" name="action" value="toggle_watchlist">
                            <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white p-2 rounded-full shadow-lg" title="Remove">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </form>

                        <a href="view_item.php?id=<?php echo $itemId; ?>" class="block flex-grow draggable-link">
                            <div class="aspect-video overflow-hidden pointer-events-none">
                                <img src="<?php echo 'uploads/covers/' . $image; ?>" class="w-full h-full object-cover" onerror="this.src='uploads/thumbs/<?php echo $image; ?>'"> 
                            </div>
                            <div class="p-4">
                                <span class="bg-purple-900 text-purple-200 text-[10px] uppercase font-bold px-2 py-0.5 rounded mb-2 inline-block"><?php echo $category; ?></span>
                                <h3 class="font-bold text-white text-lg truncate"><?php echo $title; ?></h3>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var el = document.getElementById('watchlist-grid');
                    if(el){
                        var sortable = Sortable.create(el, {
                            animation: 150,
                            ghostClass: 'sortable-ghost',
                            onEnd: function (evt) {
                                var order = sortable.toArray();
                                fetch('actions.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: 'action=reorder_watchlist&order=' + JSON.stringify(order)
                                })
                                .then(response => response.json())
                                .then(data => { console.log('Saved'); })
                                .catch(error => console.error('Error:', error));
                            }
                        });
                    }
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>