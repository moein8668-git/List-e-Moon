<?php

/**
 * Image Handler - Downloads, Resizes, and Saves Images
 */

function process_image($source, $is_url = true) {
    // 1. Get Image Content
    if ($is_url) {
        $ch = curl_init($source);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 sec timeout (slightly generous for download)
        curl_setopt($ch, CURLOPT_USERAGENT, 'List-e-Moon/1.0');
        $valid_content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || empty($valid_content)) {
            return false;
        }
        $image_string = $valid_content;
    } else {
        // Handle local file upload
        if (!file_exists($source)) return false;
        $image_string = file_get_contents($source);
    }

    // 2. Load Image Resources (GD)
    $img = @imagecreatefromstring($image_string);
    if (!$img) return false;

    // 3. Resize Logic (Max Width 300px)
    $original_width = imagesx($img);
    $original_height = imagesy($img);
    $target_width = 300;

    if ($original_width > $target_width) {
        $ratio = $target_width / $original_width;
        $target_height = $original_height * $ratio;

        $new_img = imagecreatetruecolor($target_width, $target_height);
        
        // Preserve transparency for PNGs before converting to JPEG (not strictly needed for JPEG output but good practice)
        imagealphablending($new_img, true);
        
        imagecopyresampled($new_img, $img, 0, 0, 0, 0, $target_width, $target_height, $original_width, $original_height);
        imagedestroy($img);
        $img = $new_img;
    }

    // 4. Generate Unique Filename
    $filename = uniqid('cover_') . '.jpg';
    $output_path = THUMB_DIR . $filename;

    // Ensure directory exists
    if (!is_dir(THUMB_DIR)) {
        mkdir(THUMB_DIR, 0755, true);
    }

    // 5. Save as JPG (80% Quality)
    if (imagejpeg($img, $output_path, 80)) {
        imagedestroy($img);
        return $filename; // Return just the filename
    }

    imagedestroy($img);
    return false;
}
