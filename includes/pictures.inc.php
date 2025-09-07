<?php
function scaleImage(string $path, int $new_size): string {
    $file_info = pathinfo($path);
    $filename = $file_info["basename"];
    $new_dir = $file_info["dirname"] . "/" . $new_size . "x0/";

    if (!file_exists($new_dir)) {
        $ok = mkdir($new_dir, 0755, true);
    } else {
        $ok = true;
    }

    if ($ok) {
        // Bildinformationen auslesen
        $info = getimagesize($path);
        $old_width = $info[0];
        $old_height = $info[1];
        $aspect_ratio = $old_width / $old_height;
        $old_type = $info["mime"];

        // Neue Größe berechnen
        if ($aspect_ratio > 1) {
            // Querformat
            $new_width = $new_size;
            $new_height = intval($new_width / $aspect_ratio);
        } else {
            // Hochformat oder quadratisch
            $new_height = $new_size;
            $new_width = intval($new_height * $aspect_ratio);
        }

        switch($old_type) {
            case "image/jpeg":
                $old_resource = imagecreatefromjpeg($path);
                $new_resource = imagecreatetruecolor($new_width, $new_height);
                $new_resource = imagescale($old_resource, $new_width, $new_height);
                imagejpeg($new_resource, $new_dir . $filename);
                break;
            case "image/gif":
                $old_resource = imagecreatefromgif($path);
                $new_resource = imagecreate($new_width, $new_height);
                $new_resource = imagescale($old_resource, $new_width, $new_height);
                imagegif($new_resource, $new_dir . $filename);
                break;
            case "image/png":
                $old_resource = imagecreatefrompng($path);
                $new_resource = imagecreatetruecolor($new_width, $new_height);
                $new_resource = imagescale($old_resource, $new_width, $new_height);
                imagepng($new_resource, $new_dir . $filename);
                break;
            case "image/webp":
                $old_resource = imagecreatefromwebp($path);
                $new_resource = imagecreatetruecolor($new_width, $new_height);
                $new_resource = imagescale($old_resource, $new_width, $new_height);
                imagewebp($new_resource, $new_dir . $filename);
                break;
            case "image/avif":
                $old_resource = imagecreatefromavif($path);
                $new_resource = imagecreatetruecolor($new_width, $new_height);
                $new_resource = imagescale($old_resource, $new_width, $new_height);
                imageavif($new_resource, $new_dir . $filename);
                break;
        }
    }

    return $new_dir;
}
?>