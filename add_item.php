<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login();

// Fetch Categories
global $pdo;
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Item - List-e-Moon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col">
    <nav class="bg-slate-800 border-b border-slate-700 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold text-purple-400">Add New Item</h1>
            <a href="dashboard.php" class="text-slate-300 hover:text-white transition-colors">Back</a>
        </div>
    </nav>
    
    <div class="container mx-auto p-6 max-w-2xl">
        <!-- Tabs -->
        <div class="flex space-x-4 mb-6">
            <button id="btn-search-mode" class="px-4 py-2 rounded bg-purple-600 text-white font-bold transition-colors">Search (Auto)</button>
            <button id="btn-manual-mode" class="px-4 py-2 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition-colors">Manual Entry</button>
        </div>

        <!-- Search Mode -->
        <div id="search-section" class="mb-8">
            <div class="flex flex-col md:flex-row gap-4 mb-4">
                <select id="search-category" class="w-full md:w-auto bg-slate-800 border border-slate-700 text-white rounded p-3 focus:outline-none focus:border-purple-500 appearance-none">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['slug']; ?>" data-id="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="flex-grow flex gap-2">
                     <input type="text" id="search-query" placeholder="Enter title (e.g. Inception)" class="w-full bg-slate-800 border border-slate-700 text-white rounded p-3 focus:outline-none focus:border-purple-500">
                </div>
                <button onclick="performSearch()" class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded font-bold transition-colors shadow-lg">Search</button>
            </div>
            
            <div id="search-loading" class="hidden text-center text-slate-400 py-4">
                <svg class="animate-spin h-8 w-8 text-purple-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <p class="mt-2 text-sm">Searching APIs...</p>
            </div>
            
            <div id="search-error" class="hidden bg-red-500/10 border border-red-500/50 text-red-200 p-3 rounded mb-4 text-sm">
                Network issue or no results. Switched to Manual Mode.
            </div>

            <div id="search-results" class="space-y-3"></div>
        </div>

        <!-- Manual/Final Form -->
        <div id="manual-section" class="hidden bg-slate-800 p-6 rounded-lg border border-slate-700">
            <h2 class="text-xl font-bold mb-4 text-purple-400">Item Details</h2>
            <form action="actions.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="add_item">
                <input type="hidden" name="remote_id" id="input-remote-id">
                <input type="hidden" name="remote_image_url" id="input-remote-image">
                <input type="hidden" name="external_rating" id="input-external-rating">

                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Category</label>
                    <select name="category_id" id="input-category" class="w-full bg-slate-900 border border-slate-700 rounded p-2 focus:outline-none focus:border-purple-500">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Title</label>
                    <input type="text" name="title" id="input-title" required class="w-full bg-slate-900 border border-slate-700 rounded p-2 focus:outline-none focus:border-purple-500">
                </div>

                <!-- Rating Display (Read Only) -->
                <div id="rating-display" class="hidden text-sm text-yellow-500 font-bold mb-2">
                    External Rating: <span id="rating-value"></span>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Description</label>
                    <textarea name="description" id="input-description" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded p-2 focus:outline-none focus:border-purple-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Cover Image</label>
                    <div id="image-preview-container" class="mb-2 hidden">
                        <img id="image-preview" src="" alt="Preview" class="w-24 h-auto rounded border border-slate-600">
                        <p class="text-xs text-green-400 mt-1">Image selected from API</p>
                    </div>
                    <input type="file" name="cover_image" accept="image/*" class="text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-600 file:text-white hover:file:bg-purple-700">
                    <p class="text-xs text-slate-500 mt-1">Upload to override API image (optional).</p>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded transition-colors">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const searchSection = document.getElementById('search-section');
        const manualSection = document.getElementById('manual-section');
        const btnSearch = document.getElementById('btn-search-mode');
        const btnManual = document.getElementById('btn-manual-mode');
        const loading = document.getElementById('search-loading');
        const errorMsg = document.getElementById('search-error');
        const resultsContainer = document.getElementById('search-results');

        // Mode Switching
        function showManual() {
            manualSection.classList.remove('hidden');
            searchSection.classList.add('hidden');
            btnManual.classList.add('bg-purple-600', 'text-white');
            btnManual.classList.remove('bg-slate-700', 'text-slate-300');
            btnSearch.classList.remove('bg-purple-600', 'text-white');
            btnSearch.classList.add('bg-slate-700', 'text-slate-300');
        }

        function showSearch() {
            manualSection.classList.add('hidden');
            searchSection.classList.remove('hidden');
            btnSearch.classList.add('bg-purple-600', 'text-white');
            btnSearch.classList.remove('bg-slate-700', 'text-slate-300');
            btnManual.classList.remove('bg-purple-600', 'text-white');
            btnManual.classList.add('bg-slate-700', 'text-slate-300');
        }

        btnManual.addEventListener('click', showManual);
        btnSearch.addEventListener('click', showSearch);

        // API Search
        async function performSearch() {
            const query = document.getElementById('search-query').value;
            const catSlug = document.getElementById('search-category').value;
            
            if (!query) return;

            loading.classList.remove('hidden');
            errorMsg.classList.add('hidden');
            resultsContainer.innerHTML = '';

            try {
                const response = await fetch(`api_search.php?q=${encodeURIComponent(query)}&cat=${catSlug}`);
                const data = await response.json();

                loading.classList.add('hidden');

                if (data.error || !data.results || data.results.length === 0) {
                     // Fail gracefully to manual
                     errorMsg.classList.remove('hidden');
                     errorMsg.innerText = data.error === 'API Timeout' ? 
                        "API Interaction Timed Out (>3s). Using Manual Mode." : 
                        "No results found. Please enter manually.";
                     setTimeout(showManual, 1500); // Wait a bit so user sees error
                     return;
                }

                data.results.forEach(item => {
                    const div = document.createElement('div');
                    // Mobile: Flex Column (Card), Desktop: Flex Row (List). Items Stretch to fill width.
                    div.className = 'bg-slate-800 rounded-lg p-4 flex flex-col md:flex-row gap-4 hover:bg-slate-750 border border-slate-700 shadow-md transition-all items-stretch md:items-center overflow-hidden';
                    
                    // Card Content
                    div.innerHTML = `
                        <!-- Image: Full Width on Mobile, Fixed on Desktop -->
                        <div class="w-full md:w-auto flex-shrink-0">
                            <img src="${item.image || 'assets/img/no-cover.png'}" class="w-full h-auto max-h-96 md:w-16 md:h-24 object-cover rounded shadow-lg md:shadow-none bg-slate-900 mx-auto">
                        </div>
                        
                        <!-- Content: Center text on Mobile, Left on Desktop -->
                        <div class="flex-grow text-center md:text-left w-full min-w-0">
                            <h3 class="font-bold text-white text-lg md:text-base leading-tight break-words">${item.title} <span class="text-slate-400 font-normal block md:inline text-sm">(${item.year})</span></h3>
                            <p class="text-sm text-slate-400 mt-2 line-clamp-3 md:line-clamp-2 leading-relaxed break-words">${item.description ? item.description.substring(0, 150) + '...' : 'No description available'}</p>
                            ${item.rating ? `<span class="inline-flex items-center mt-2 text-xs font-bold text-yellow-500 bg-yellow-500/10 px-2 py-1 rounded border border-yellow-500/20">★ ${item.rating} (${item.source})</span>` : ''}
                        </div>

                        <!-- Button: Full Width on Mobile, Fixed on Desktop -->
                        <div class="w-full md:w-auto mt-2 md:mt-0 flex-shrink-0">
                            <button class="w-full md:w-auto bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 md:py-2 md:px-4 rounded transition-colors shadow-lg md:shadow-none text-sm uppercase tracking-wide">
                                Add
                            </button>
                        </div>
                    `;
                    div.onclick = (e) => {
                        selectItem(item, catSlug);
                    };
                    resultsContainer.appendChild(div);
                });

            } catch (e) {
                loading.classList.add('hidden');
                errorMsg.classList.remove('hidden');
                setTimeout(showManual, 1500);
            }
        }

        function selectItem(item, catSlug) {
            // Populate form
            document.getElementById('input-title').value = item.title;
            document.getElementById('input-description').value = item.description;
            document.getElementById('input-remote-id').value = item.remote_id;
            document.getElementById('input-remote-image').value = item.image;
            
            // Set Rating
            const ratingInput = document.getElementById('input-external-rating');
            const ratingDisplay = document.getElementById('rating-display');
            const ratingValue = document.getElementById('rating-value');
            
            if (item.rating) {
                ratingInput.value = item.rating;
                ratingValue.innerText = item.rating;
                ratingDisplay.classList.remove('hidden');
            } else {
                ratingInput.value = '';
                ratingDisplay.classList.add('hidden');
            }

            // Set Category in Select
            const catSelect = document.getElementById('input-category');
            // Find option with this slug (we have to map slug back to ID, done via data-id logic would be better but select value is ID)
            // Let's loop options and match logic or use data attribute I added
            const options = catSelect.options;
            // Get the ID for the current slug from the search select
            const searchSelect = document.getElementById('search-category');
            const selectedOption = searchSelect.options[searchSelect.selectedIndex];
            const catId = selectedOption.getAttribute('data-id');
            catSelect.value = catId; 

            // Show Image Preview
            if (item.image) {
                const preview = document.getElementById('image-preview');
                preview.src = item.image;
                document.getElementById('image-preview-container').classList.remove('hidden');
            }

            showManual();
        }
    </script>
</body>
</html>
