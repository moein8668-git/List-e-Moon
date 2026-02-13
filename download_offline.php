<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login();

// Start buffering to prevent accidental output and calculate length
ob_start();

global $pdo;

// 1. Fetch User Info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// 2. Fetch Personal Watchlist (Ordered)
$wStmt = $pdo->prepare("
    SELECT i.*, c.name as category_name
    FROM watchlist w
    JOIN items i ON w.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    WHERE w.user_id = ?
    ORDER BY w.display_order ASC, w.added_at DESC
");
$wStmt->execute([$_SESSION['user_id']]);
$watchlist = $wStmt->fetchAll();

// 3. Fetch Full Public Library
$iStmt = $pdo->query("
    SELECT i.*, c.name as category_name, u.username as added_by
    FROM items i
    JOIN categories c ON i.category_id = c.id
    JOIN users u ON i.added_by_user_id = u.id
    ORDER BY i.created_at DESC
");
$library = $iStmt->fetchAll();

// 4. CSS
$css = "
    :root { --bg: #0f172a; --card: #1e293b; --text: #f8fafc; --accent: #9333ea; --border: #334155; }
    body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 15px; line-height: 1.5; font-size: 16px; }
    .container { max-width: 1200px; margin: 0 auto; width: 100%; }
    h1, h2, h3 { color: #e2e8f0; }
    .header { border-bottom: 2px solid var(--border); padding-bottom: 20px; margin-bottom: 40px; display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
    .avatar { width: 60px; height: 60px; background: var(--card); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 24px; border: 2px solid var(--border); overflow: hidden; flex-shrink: 0; }
    .avatar img { width: 100%; height: 100%; object-fit: cover; }
    .section { margin-bottom: 60px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; }
    .card { background: var(--card); border: 1px solid var(--border); border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; }
    .thumb { aspect-ratio: 16/9; background: #000; overflow: hidden; }
    .thumb img { width: 100%; height: 100%; object-fit: cover; }
    .content { padding: 12px; flex-grow: 1; }
    .tag { display: inline-block; background: #3b0764; color: #d8b4fe; padding: 2px 8px; border-radius: 4px; font-size: 10px; text-transform: uppercase; font-weight: bold; margin-bottom: 8px; }
    .title { font-size: 18px; font-weight: bold; margin: 0 0 10px 0; }
    .desc { font-size: 14px; color: #94a3b8; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .meta { font-size: 12px; color: #64748b; margin-top: 10px; font-style: italic; }
    .search-bar { width: 100%; padding: 10px; margin-bottom: 20px; background: var(--card); border: 1px solid var(--border); color: #fff; border-radius: 4px; font-size: 16px; }
    .print-only { display: none; }
    @media print { .no-print { display: none; } .print-only { display: block; } body { background: #fff; color: #000; } .card { border: 1px solid #ccc; background: #fff; } h1, h2 { color: #000; } }
";

function get_image_data($path) {
    // Only embed if file exists.
    $full_path = UPLOAD_DIR . 'thumbs/' . $path;
    if (file_exists($full_path)) {
        $type = pathinfo($full_path, PATHINFO_EXTENSION);
        $data = file_get_contents($full_path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List-e-Moon Archive - <?php echo date('Y-m-d'); ?></title>
    <style><?php echo $css; ?></style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="avatar">
                <?php if (!empty($user['avatar_path']) && file_exists(UPLOAD_DIR . 'avatars/' . $user['avatar_path'])): 
                    $av_path = UPLOAD_DIR . 'avatars/' . $user['avatar_path'];
                    $av_data = file_get_contents($av_path);
                    $av_type = pathinfo($av_path, PATHINFO_EXTENSION);
                    $av_base64 = 'data:image/' . $av_type . ';base64,' . base64_encode($av_data);
                ?>
                    <img src="<?php echo $av_base64; ?>">
                <?php else: ?>
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div>
                <h1><?php echo htmlspecialchars($user['username']); ?>'s Backup</h1>
                <p style="color: #94a3b8; margin: 0;">Generated on <?php echo date('F j, Y'); ?> • <?php echo count($watchlist); ?> Watched Items</p>
            </div>
        </header>

        <div class="section">
            <h2>⭐ My Watchlist (Ordered)</h2>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">Your personalized, sorted list.</p>
            <?php if (empty($watchlist)): ?>
                <p>No items in watchlist.</p>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($watchlist as $item): 
                        $img_src = get_image_data($item['local_image_path']);
                    ?>
                        <div class="card">
                            <div class="thumb">
                                <?php if($img_src): ?>
                                    <img src="<?php echo $img_src; ?>">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;background:#333;display:flex;align-items:center;justify-content:center;color:#555;">No Img</div>
                                <?php endif; ?>
                            </div>
                            <div class="content">
                                <span class="tag"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                <h3 class="title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p class="desc"><?php echo htmlspecialchars($item['description']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>📚 Full Public Library</h2>
            <input type="text" id="search" class="search-bar no-print" placeholder="Search library..." onkeyup="filterLibrary()">
            <div class="grid" id="library-grid">
                <?php foreach ($library as $item): 
                     $img_src = get_image_data($item['local_image_path']);
                ?>
                    <div class="card library-item">
                        <div class="thumb">
                           <?php if($img_src): ?>
                                <img src="<?php echo $img_src; ?>">
                            <?php else: ?>
                                <div style="width:100%;height:100%;background:#333;"></div>
                            <?php endif; ?>
                        </div>
                        <div class="content">
                            <span class="tag"><?php echo htmlspecialchars($item['category_name']); ?></span>
                            <h3 class="title"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p class="desc"><?php echo htmlspecialchars($item['description']); ?></p>
                            <div class="meta">Added by <?php echo htmlspecialchars($item['added_by']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
            function filterLibrary() {
                var input = document.getElementById('search');
                var filter = input.value.toUpperCase();
                var grid = document.getElementById('library-grid');
                var cards = grid.getElementsByClassName('library-item');

                for (var i = 0; i < cards.length; i++) {
                    var title = cards[i].getElementsByClassName('title')[0].innerText;
                    var desc = cards[i].getElementsByClassName('desc')[0].innerText;
                    if (title.toUpperCase().indexOf(filter) > -1 || desc.toUpperCase().indexOf(filter) > -1) {
                        cards[i].style.display = "";
                    } else {
                        cards[i].style.display = "none";
                    }
                }
            }
        </script>
    </div>
</body>
</html>
<?php
// Capture Buffer and Output with Headers
$html = ob_get_clean();

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="List-e-Moon_Backup_' . date('Y-m-d') . '.html"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($html));

echo $html;
exit();
?>
