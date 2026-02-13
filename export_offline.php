<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login();

// Disable limits for generation
set_time_limit(0);

// Stream Output
$filename = 'list-e-moon-offline-' . date('Y-m-d') . '.html';
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// Start HTML
fwrite($out, '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List-e-Moon Offline Capsule</title>
    <style>
        /* Minimal Reset & Grid */
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: white; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { border-bottom: 1px solid #334155; padding-bottom: 20px; margin-bottom: 40px; text-align: center; }
        .header h1 { margin: 0; color: #a855f7; }
        
        .category-section { margin-bottom: 50px; }
        .category-title { color: #e2e8f0; border-bottom: 2px solid #a855f7; padding-bottom: 10px; margin-bottom: 20px; display: inline-block; font-size: 1.5rem; }
        
        .grid { display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
        @media (max-width: 768px) {
            .grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .card h3 { font-size: 0.9rem; }
            .card-body { padding: 10px; }
        }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 8px; overflow: hidden; transition: transform 0.2s; }
        .card img { width: 100%; aspect-ratio: 2/3; object-fit: cover; background: #000; }
        .card-body { padding: 15px; }
        .card h3 { margin: 0 0 5px 0; font-size: 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .badge { display: inline-block; padding: 2px 6px; font-size: 0.75rem; background: #7e22ce; border-radius: 4px; color: white; margin-top: 5px; }
        .vote { float: right; color: #eab308; font-weight: bold; }
        .ext-rating { display: block; font-size: 0.75rem; color: #94a3b8; margin-top: 5px; }
        .footer { text-align: center; margin-top: 60px; color: #475569; font-size: 0.8rem; border-top: 1px solid #334155; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>List-e-Moon Offline Capsule</h1>
            <p>Generated on ' . date('F j, Y') . '</p>
        </div>
');

// Fetch Categories first
global $pdo;
$cats_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");

while ($cat = $cats_stmt->fetch(PDO::FETCH_ASSOC)) {
    // Fetch Items for this Category
    $stmt = $pdo->prepare("
        SELECT i.*, 
        (SELECT AVG(score) FROM ratings WHERE item_id = i.id) as avg_score 
        FROM items i 
        WHERE i.category_id = ? 
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([$cat['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($items) === 0) continue;

    // Category Header
    fwrite($out, '<div class="category-section">');
    fwrite($out, '<h2 class="category-title">' . htmlspecialchars($cat['name']) . '</h2>');
    fwrite($out, '<div class="grid">');

    foreach ($items as $item) {
        // Image Processing (Base64)
        $img_src = '';
        $local_path = THUMB_DIR . $item['local_image_path'];
        
        if (file_exists($local_path)) {
            $type = pathinfo($local_path, PATHINFO_EXTENSION);
            $data = file_get_contents($local_path);
            $base64 = base64_encode($data);
            $img_src = 'data:image/' . $type . ';base64,' . $base64;
        } else {
             $img_src = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="300" viewBox="0 0 200 300"><rect width="200" height="300" fill="#333"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#555">No Image</text></svg>');
        }
        
        $score = $item['avg_score'] ? round($item['avg_score'], 1) : '-';
        $ext_rating = ($item['external_rating'] > 0) ? '⭐ ' . $item['external_rating'] . ' (Ext)' : '';

        // Card HTML
        $card = '
        <div class="card">
            <img src="' . $img_src . '" loading="lazy" alt="Cover">
            <div class="card-body">
                <h3>' . htmlspecialchars($item['title']) . '</h3>
                <span class="vote">' . $score . '/10</span>
                <span class="ext-rating">' . $ext_rating . '</span>
            </div>
        </div>';
        
        fwrite($out, $card);
    }
    
    fwrite($out, '</div></div>'); // End Grid & Section
}

fwrite($out, '
        <div class="footer">
            List-e-Moon Offline Capsule - ' . date('Y-m-d H:i') . '
        </div>
    </div>
</body>
</html>');

fclose($out);
exit;
