<?php
/**
 * Enhanced nanoPhotosProvider2 add-on for nanogallery2 with SEO support
 * 
 * Modified to serve both JSON for nanogallery2 AND SEO-friendly HTML for search engines
 */

require './nano_photos_provider2.json.class.php';

// Check if this is a request from nanogallery2 (AJAX) or a direct browser visit
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

$acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && 
               strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

$hasJsonParam = isset($_GET['json']) || isset($_POST['json']);

// Serve HTML for SEO when accessed directly by browsers/crawlers
if (!$isAjaxRequest && !$acceptsJson && !$hasJsonParam) {
    serveHtmlForSEO();
    exit;
}

// Original nanogallery2 JSON logic
define('ENVIRONMENT', 'production');
switch (ENVIRONMENT) {
    case 'development':
        error_reporting(-1);
        ini_set('display_errors', 1);
        $t = new galleryJSON();
        break;
        
    case 'production':
        ini_set('display_errors', 0);
        
        if (version_compare(PHP_VERSION, '5.3', '>=')) {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
        }
        
        function myErrorHandler($code, $message, $file, $line) {
            header("HTTP/1.1 200 OK");
            header('Content-Type: application/json; charset=utf-8');
            $response = array('nano_status' => 'error', 'nano_message' => $message . '<br>  ('.basename($file).'/'.$line.')');
            $output = json_encode($response);
            echo $output;
            exit;
        }
        
        register_shutdown_function( function(){
            $last_error = error_get_last();
            if ($last_error['type'] === E_ERROR) {
                myErrorHandler(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
            }
        });
        $t = new galleryJSON();
        break;
    default:
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'The application environment is not set correctly.';
        exit(1);
}

/**
 * Serve SEO-friendly HTML version
 */
function serveHtmlForSEO() {
    // Get the same data that would be sent to nanogallery2
    $galleryData = getGalleryData();
    
    $album = $_GET['album'] ?? '';
    $albumTitle = !empty($album) ? ucwords(str_replace('-', ' ', $album)) : 'Photo Gallery';
    
    $images = [];
    if (isset($galleryData['nano_photos_provider2']['items'])) {
        $images = $galleryData['nano_photos_provider2']['items'];
    }
    
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($albumTitle); ?> - Photo Gallery</title>
        <meta name="description" content="<?php echo htmlspecialchars($albumTitle . ' photo gallery with ' . count($images) . ' images'); ?>">
        
        <!-- Structured Data for SEO -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "ImageGallery",
            "name": "<?php echo htmlspecialchars($albumTitle); ?>",
            "description": "Photo gallery featuring <?php echo count($images); ?> images",
            <?php if (!empty($images)): ?>
            "image": [
                <?php foreach($images as $index => $image): ?>
                {
                    "@type": "ImageObject",
                    "contentUrl": "<?php echo htmlspecialchars(getFullUrl($image['src'])); ?>",
                    "thumbnailUrl": "<?php echo htmlspecialchars(getFullUrl($image['srct'] ?? $image['src'])); ?>",
                    "name": "<?php echo htmlspecialchars($image['title'] ?? 'Image ' . ($index + 1)); ?>",
                    "description": "<?php echo htmlspecialchars($image['description'] ?? $image['title'] ?? 'Gallery image'); ?>"
                    <?php if (isset($image['width'])): ?>,"width": <?php echo intval($image['width']); ?><?php endif; ?>
                    <?php if (isset($image['height'])): ?>,"height": <?php echo intval($image['height']); ?><?php endif; ?>
                }<?php if($index < count($images) - 1) echo ','; ?>
                <?php endforeach; ?>
            ]
            <?php endif; ?>
        }
        </script>
        
        <!-- Open Graph for social media -->
        <meta property="og:title" content="<?php echo htmlspecialchars($albumTitle); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars($albumTitle . ' photo gallery'); ?>">
        <meta property="og:type" content="website">
        <?php if (!empty($images)): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars(getFullUrl($images[0]['src'])); ?>">
        <?php endif; ?>
        
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            h1 { color: #333; text-align: center; margin-bottom: 30px; }
            .gallery { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; max-width: 1200px; margin: 0 auto; }
            .image-item { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .image-item img { width: 100%; height: 200px; object-fit: cover; }
            .image-info { padding: 15px; }
            .image-title { font-weight: bold; margin-bottom: 5px; color: #333; }
            .image-description { color: #666; font-size: 14px; }
            .back-link { display: inline-block; margin-bottom: 20px; color: #007cba; text-decoration: none; }
            .back-link:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <h1><?php echo htmlspecialchars($albumTitle); ?></h1>
        
        <?php if (!empty($images)): ?>
        <div class="gallery">
            <?php foreach($images as $image): ?>
            <div class="image-item">
                <img src="<?php echo htmlspecialchars($image['src']); ?>" 
                     alt="<?php echo htmlspecialchars($image['title'] ?? 'Gallery image'); ?>"
                     loading="lazy"
                     <?php if (isset($image['width'])): ?>width="<?php echo intval($image['width']); ?>"<?php endif; ?>
                     <?php if (isset($image['height'])): ?>height="<?php echo intval($image['height']); ?>"<?php endif; ?>>
                
                <?php if (!empty($image['title']) || !empty($image['description'])): ?>
                <div class="image-info">
                    <?php if (!empty($image['title'])): ?>
                    <div class="image-title"><?php echo htmlspecialchars($image['title']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($image['description'])): ?>
                    <div class="image-description"><?php echo htmlspecialchars($image['description']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="text-align: center; color: #666;">No images found in this gallery.</p>
        <?php endif; ?>
        
        <script>
        // Optional: Add enhanced gallery functionality
        // You could redirect to your main gallery page or enhance this further
        </script>
    </body>
    </html>
    <?php
}

/**
 * Get gallery data using the existing nanogallery2 logic
 */
function getGalleryData() {
    ob_start();
    
    // Create a new instance to get the data
    $gallery = new galleryJSON();
    
    $output = ob_get_clean();
    
    // If output is JSON, decode it
    if (!empty($output)) {
        $data = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
    }
    
    return [];
}

/**
 * Convert relative URLs to absolute URLs
 */
function getFullUrl($path) {
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path; // Already absolute
    }
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;
    
    // Handle relative paths
    if (strpos($path, '/') === 0) {
        return $baseUrl . $path;
    } else {
        $currentDir = dirname($_SERVER['REQUEST_URI']);
        return $baseUrl . $currentDir . '/' . $path;
    }
}
?>
