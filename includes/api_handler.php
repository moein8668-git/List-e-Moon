<?php
/**
 * API Handler - Fetches data with strict timeouts
 */

require_once __DIR__ . '/../config/config.php';

function fetch_api_data($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // Connection timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);        // Total execution timeout
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'List-e-Moon/1.0');
    // SSL Fix (Crucial for shared hosting sometimes)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || !$response) {
        return null; // Fail gracefully
    }
    return json_decode($response, true);
}

function search_online($query, $category_slug) {
    $query = urlencode($query);
    $results = [];

    switch ($category_slug) {
        case 'movies':
        case 'animated-movies':
            if (defined('TMDB_API_KEY') && TMDB_API_KEY) {
                $url = "https://api.themoviedb.org/3/search/movie?api_key=" . TMDB_API_KEY . "&query=" . $query . "&include_adult=false";
                $data = fetch_api_data($url);
                if ($data && isset($data['results'])) {
                    foreach ($data['results'] as $item) {
                        $results[] = [
                            'remote_id' => $item['id'],
                            'title' => $item['title'],
                            'year' => isset($item['release_date']) ? substr($item['release_date'], 0, 4) : '',
                            'image' => isset($item['poster_path']) ? "https://image.tmdb.org/t/p/w500" . $item['poster_path'] : null,
                            'description' => $item['overview'] ?? '',
                            'rating' => $item['vote_average'] ?? 0, // TMDB is 0-10
                            'source' => 'TMDB'
                        ];
                    }
                }
            }
            break;

        case 'series':
        case 'animated-series':
            if (defined('TMDB_API_KEY') && TMDB_API_KEY) {
                $url = "https://api.themoviedb.org/3/search/tv?api_key=" . TMDB_API_KEY . "&query=" . $query . "&include_adult=false";
                $data = fetch_api_data($url);
                if ($data && isset($data['results'])) {
                    foreach ($data['results'] as $item) {
                        $results[] = [
                            'remote_id' => $item['id'],
                            'title' => $item['name'],
                            'year' => isset($item['first_air_date']) ? substr($item['first_air_date'], 0, 4) : '',
                            'image' => isset($item['poster_path']) ? "https://image.tmdb.org/t/p/w500" . $item['poster_path'] : null,
                            'description' => $item['overview'] ?? '',
                            'rating' => $item['vote_average'] ?? 0, // TMDB is 0-10
                            'source' => 'TMDB'
                        ];
                    }
                }
            }
            break;

        case 'games':
            if (defined('RAWG_API_KEY') && RAWG_API_KEY) {
                $url = "https://api.rawg.io/api/games?key=" . RAWG_API_KEY . "&search=" . $query . "&page_size=5";
                $data = fetch_api_data($url);
                if ($data && isset($data['results'])) {
                    foreach ($data['results'] as $item) {
                        // NORMALIZE RATING: RAWG is 0-5, we want 0-10
                        $raw_rating = $item['rating'] ?? 0;
                        $normalized_rating = $raw_rating * 2;

                        $results[] = [
                            'remote_id' => $item['id'],
                            'title' => $item['name'],
                            'year' => isset($item['released']) ? substr($item['released'], 0, 4) : '',
                            'image' => $item['background_image'] ?? null,
                            'description' => '', 
                            'rating' => $normalized_rating, 
                            'source' => 'RAWG'
                        ];
                    }
                }
            }
            break;

        case 'books':
            // Open Library API
            $url = "https://openlibrary.org/search.json?q=" . $query . "&limit=5";
            $data = fetch_api_data($url);
            
            if ($data && isset($data['docs'])) {
                foreach ($data['docs'] as $item) {
                    $cover_id = $item['cover_i'] ?? null;
                    $image = $cover_id ? "https://covers.openlibrary.org/b/id/{$cover_id}-L.jpg" : null;
                    
                    $results[] = [
                        'remote_id' => $item['key'] ?? uniqid(),
                        'title' => $item['title'],
                        'year' => isset($item['first_publish_year']) ? $item['first_publish_year'] : '',
                        'image' => $image,
                        'description' => '', // OpenLibrary search doesn't return description easily
                        'rating' => isset($item['ratings_average']) ? round($item['ratings_average'] * 2, 1) : 0, // Normalize 5 to 10 if exists
                        'source' => 'OpenLibrary'
                    ];
                }
            }
            break;

        case 'podcasts':
            // iTunes Search API
            $url = "https://itunes.apple.com/search?term=" . $query . "&media=podcast&limit=5";
            $data = fetch_api_data($url);
            
            if ($data && isset($data['results'])) {
                foreach ($data['results'] as $item) {
                    $results[] = [
                        'remote_id' => $item['collectionId'] ?? uniqid(),
                        'title' => $item['collectionName'],
                        'year' => isset($item['releaseDate']) ? substr($item['releaseDate'], 0, 4) : '',
                        'image' => $item['artworkUrl600'] ?? $item['artworkUrl100'],
                        'description' => '', 
                        'rating' => 0, // iTunes doesn't return rating in search
                        'source' => 'iTunes'
                    ];
                }
            }
            break;
    }

    return $results;
}