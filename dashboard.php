<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login();

$flash = get_flash_message();

// Page Logic
require_once 'includes/header.php'; // Use the new header
?>

<div class="container mx-auto p-4 md:p-6 flex-grow">
    <?php if ($flash): ?>
        <div class="mb-6 p-4 rounded <?php echo $flash['type'] === 'error' ? 'bg-red-500/20 text-red-200 border border-red-500/50' : 'bg-green-500/20 text-green-200 border border-green-500/50'; ?>">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 relative">
        <!-- Sidebar Toggle (Mobile Only) -->
        <button id="sidebar-toggle" class="md:hidden bg-slate-800 text-white p-3 rounded mb-4 w-full text-left font-bold flex justify-between items-center border border-slate-700 hover:bg-slate-700 transition-colors">
            <span>Filter Categories</span>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>

        <!-- Sidebar -->
        <aside id="sidebar" class="hidden md:block md:col-span-1 space-y-2">
            <div class="bg-slate-800 p-4 rounded-lg border border-slate-700">
                <h3 class="text-slate-400 text-xs uppercase font-bold mb-3 tracking-wider">Categories</h3>
                <nav class="space-y-1">
                    <a href="dashboard.php" class="<?php echo !isset($_GET['cat']) ? 'bg-purple-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?> block px-3 py-2 rounded transition-colors text-sm font-medium">All Items</a>
                    <?php
                    global $pdo;
                    $cats_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
                    while ($c = $cats_stmt->fetch()) {
                        $active = (isset($_GET['cat']) && $_GET['cat'] == $c['slug']) ? 'bg-purple-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white';
                        echo "<a href='dashboard.php?cat={$c['slug']}' class='block $active px-3 py-2 rounded transition-colors text-sm font-medium'>{$c['name']}</a>";
                    }
                    ?>
                </nav>
                <div class="pt-4 mt-4 border-t border-slate-700">
                    <a href="add_item.php" class="block bg-green-600 hover:bg-green-700 text-white text-center font-bold py-2 px-4 rounded transition-colors shadow-lg shadow-green-900/20 text-sm">
                        + Add New Item
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Area -->
        <main class="md:col-span-3">
            <?php
            // 1. Capture GET params (Moved Up)
            $cat_slug = $_GET['cat'] ?? null;
            $sort = $_GET['sort'] ?? 'newest';
            $filter_users = isset($_GET['filter_users']) ? $_GET['filter_users'] : [];

            // 2. Fetch Contributors for Filter (Context-Aware)
            $contribSql = "SELECT DISTINCT u.id, u.username, u.avatar_path 
                           FROM users u 
                           JOIN items i ON u.id = i.added_by_user_id";
            $contribParams = [];

            if ($cat_slug) {
                // Filter contributors by current category
                $contribSql .= " JOIN categories c ON i.category_id = c.id WHERE c.slug = ?";
                $contribParams[] = $cat_slug;
            }
            
            $contribSql .= " ORDER BY u.username ASC";
            
            $contribStmt = $pdo->prepare($contribSql);
            $contribStmt->execute($contribParams);
            $contributors = $contribStmt->fetchAll();
            ?>

            <!-- Mobile Backdrop (Hidden by default) -->
            <div id="filterBackdrop" class="hidden fixed inset-0 bg-black/50 z-40 md:hidden transition-opacity"></div>

            <form id="dashboardToolbar" method="GET" action="dashboard.php" class="mb-6 relative z-30">
                <?php if ($cat_slug): ?>
                    <input type="hidden" name="cat" value="<?php echo htmlspecialchars($cat_slug); ?>">
                <?php endif; ?>

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <?php echo $cat_slug ? 'Category: ' . htmlspecialchars($cat_slug) : 'All Items'; ?>
                        <?php if(!empty($filter_users)): ?>
                            <span class="text-xs bg-purple-500 text-white px-2 py-0.5 rounded-full"><?php echo count($filter_users); ?> Filters</span>
                        <?php endif; ?>
                    </h2>
                    
                    <div class="flex items-center gap-3 w-full sm:w-auto relative">
                        <!-- User Filter Widget -->
                        <div class="static md:relative"> <!-- Static on mobile to allow fixed dropdown -->
                            <button type="button" id="filterBtn" class="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 px-3 py-1.5 rounded text-sm font-medium flex items-center gap-2 transition-colors">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                Filter by User 🔽
                            </button>

                            <!-- Dropdown (Responsive: Fixed on Mobile, Absolute on Desktop) -->
                            <div id="filterDropdown" class="hidden fixed inset-x-4 top-24 max-h-[60vh] overflow-y-auto z-50 border border-slate-600 shadow-2xl rounded bg-slate-800 md:absolute md:top-full md:right-0 md:left-auto md:w-64 md:mt-2 md:inset-auto">
                                <!-- Controls -->
                                <div class="flex justify-between p-2 border-b border-slate-700 text-xs sticky top-0 bg-slate-800 z-10">
                                    <button type="button" onclick="toggleAllCheckboxes(true)" class="text-purple-400 hover:text-purple-300 font-bold">Select All</button>
                                    <button type="button" onclick="toggleAllCheckboxes(false)" class="text-slate-400 hover:text-slate-300">Deselect All</button>
                                </div>
                                
                                <!-- Checkboxes -->
                                <div class="p-2 space-y-2">
                                    <?php if (empty($contributors)): ?>
                                        <div class="text-slate-500 text-xs text-center py-4">No contributors found in this category.</div>
                                    <?php else: ?>
                                        <?php foreach ($contributors as $c): ?>
                                            <label class="flex items-center gap-3 p-1.5 hover:bg-slate-700 rounded cursor-pointer group">
                                                <input type="checkbox" name="filter_users[]" value="<?php echo $c['id']; ?>" 
                                                    <?php echo in_array($c['id'], $filter_users) ? 'checked' : ''; ?>
                                                    class="rounded bg-slate-900 border-slate-600 text-purple-600 focus:ring-purple-500">
                                                
                                                <div class="flex items-center gap-2">
                                                    <div class="w-6 h-6 rounded-full bg-slate-600 overflow-hidden flex-shrink-0">
                                                        <?php if (!empty($c['avatar_path']) && file_exists(UPLOAD_DIR . 'avatars/' . $c['avatar_path'])): ?>
                                                            <img src="uploads/avatars/<?php echo htmlspecialchars($c['avatar_path']); ?>" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <div class="w-full h-full flex items-center justify-center text-[8px] font-bold text-white"><?php echo strtoupper(substr($c['username'], 0, 1)); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="text-sm text-slate-300 group-hover:text-white transition-colors"><?php echo htmlspecialchars($c['username']); ?></span>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Apply -->
                                <div class="p-2 border-t border-slate-700 bg-slate-800/50 sticky bottom-0">
                                    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold py-1.5 rounded transition-colors shadow-lg">
                                        Apply Filter
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Sort -->
                        <select name="sort" onchange="this.form.submit()" class="bg-slate-800 text-slate-300 text-sm border border-slate-700 rounded p-1.5 focus:outline-none focus:border-purple-500">
                            <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo ($sort == 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="highest_rated" <?php echo ($sort == 'highest_rated') ? 'selected' : ''; ?>>Highest Rated (Avg)</option>
                            <option value="rating_high" <?php echo ($sort == 'rating_high') ? 'selected' : ''; ?>>External Rating (High)</option>
                            <option value="community_rating" <?php echo ($sort == 'community_rating') ? 'selected' : ''; ?>>Community Rating (High)</option>
                        </select>
                    </div>
                </div>
            </form>

            <script>
                const filterBtn = document.getElementById('filterBtn');
                const filterDropdown = document.getElementById('filterDropdown');
                const filterBackdrop = document.getElementById('filterBackdrop');

                // Toggle Dropdown
                filterBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isHidden = filterDropdown.classList.contains('hidden');
                    if (isHidden) {
                        filterDropdown.classList.remove('hidden');
                        filterBackdrop.classList.remove('hidden');
                    } else {
                        closeFilter();
                    }
                });

                // Close Function
                function closeFilter() {
                    filterDropdown.classList.add('hidden');
                    filterBackdrop.classList.add('hidden');
                }

                // Close on Backdrop Click
                filterBackdrop.addEventListener('click', closeFilter);

                // Close when clicking outside (Desktop fallback)
                document.addEventListener('click', (e) => {
                    if (!filterDropdown.contains(e.target) && !filterBtn.contains(e.target)) {
                        closeFilter();
                    }
                });

                // Select/Deselect All Logic
                function toggleAllCheckboxes(checked) {
                    const checkboxes = filterDropdown.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(cb => cb.checked = checked);
                }
            </script>

            <?php
            // Build Query
            $sql = "SELECT i.*, c.name as category_name, u.username as added_by, u.avatar_path, 
                           AVG(r.score) as avg_community_rating, 
                           COUNT(r.id) as rating_count
                    FROM items i 
                    LEFT JOIN categories c ON i.category_id = c.id 
                    LEFT JOIN users u ON i.added_by_user_id = u.id
                    LEFT JOIN ratings r ON i.id = r.item_id";
            
            $where_clauses = [];
            $params = [];
            
            if ($cat_slug) {
                $where_clauses[] = "c.slug = ?";
                $params[] = $cat_slug;
            }

            if (!empty($filter_users)) {
                $placeholders = implode(',', array_fill(0, count($filter_users), '?'));
                $where_clauses[] = "i.added_by_user_id IN ($placeholders)";
                $params = array_merge($params, $filter_users);
            }

            if (!empty($where_clauses)) {
                $sql .= " WHERE " . implode(" AND ", $where_clauses);
            }
            
            $sql .= " GROUP BY i.id";
            
            // Sorting Logic
            switch ($sort) {
                case 'oldest': $sql .= " ORDER BY i.created_at ASC"; break;
                case 'highest_rated': 
                    // Sort by Total Votes FIRST (Descending), then Average Score
                    $sql .= " ORDER BY rating_count DESC, avg_community_rating DESC"; 
                    break;
                case 'rating_high': $sql .= " ORDER BY i.external_rating DESC"; break;
                case 'community_rating': $sql .= " ORDER BY avg_community_rating DESC"; break;
                default: $sql .= " ORDER BY i.created_at DESC"; break;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll();
            ?>

            <?php if (count($items) === 0): ?>
                <div class="bg-slate-800 p-8 rounded-lg shadow-lg border border-slate-700 text-center py-20">
                    <div class="flex justify-center mb-4">
                        <svg class="h-16 w-16 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-400 mb-2">No Items Found</h2>
                    <p class="text-slate-500 mb-6 max-w-sm mx-auto">This category is currently empty. Be the first to add something awesome!</p>
                    <a href="add_item.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 px-6 rounded transition-colors shadow-lg">Add Item</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                    <?php foreach ($items as $item): ?>
                        <a href="view_item.php?id=<?php echo $item['id']; ?>" class="group block bg-slate-800 rounded-lg overflow-hidden shadow-md border border-slate-700 hover:border-purple-500 hover:shadow-purple-500/10 transition-all h-full flex flex-col">
                            <div class="relative aspect-[2/3] overflow-hidden bg-slate-900">
                                <?php if ($item['local_image_path'] && file_exists(THUMB_DIR . $item['local_image_path'])): ?>
                                    <img src="<?php echo 'uploads/thumbs/' . htmlspecialchars($item['local_image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['title']); ?>"
                                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-slate-600">No Image</div>
                                <?php endif; ?>
                                
                                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent p-3 pt-8 flex justify-between items-end">
                                    <span class="text-[10px] uppercase font-bold tracking-wider bg-purple-600 px-2 py-0.5 rounded text-white"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                    <?php if(isset($item['external_rating']) && $item['external_rating'] > 0): ?>
                                        <span class="flex items-center gap-1 text-[10px] font-bold text-yellow-500 bg-black/80 px-1.5 py-0.5 rounded border border-yellow-500/20">
                                            <span>★</span> <?php echo $item['external_rating']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="p-3 flex-grow flex flex-col justify-between">
                                <div>
                                    <h3 class="font-bold text-white text-sm leading-tight line-clamp-2 mb-1 group-hover:text-purple-400 transition-colors"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <p class="text-xs text-slate-500">Added by <?php echo htmlspecialchars($item['added_by']); ?></p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
