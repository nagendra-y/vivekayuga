<?php
require 'photo_auth.php';
requireAuth();

$config = require 'photo_admin_config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$contentPath = __DIR__ . '/' . $config['content_folder'];
$tagsFile = $contentPath . '/tags.json';

function loadTags() {
    global $tagsFile;
    if (file_exists($tagsFile)) {
        $json = file_get_contents($tagsFile);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }
    return [];
}

function saveTags($data) {
    global $tagsFile;
    file_put_contents($tagsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getPhotoTagsMeta($album, $photo) {
    $album = sanitizePath($album);
    $photo = basename($photo);
    $data = loadTags();
    return $data[$album][$photo] ?? [];
}

function setPhotoTagsMeta($album, $photo, $tags) {
    $album = sanitizePath($album);
    $photo = basename($photo);
    $data = loadTags();
    if (!isset($data[$album])) $data[$album] = [];
    $data[$album][$photo] = array_values(array_filter(array_map('trim', (array)$tags)));
    saveTags($data);
}

function sanitizePath($path) {
    $path = str_replace(['..', "\0"], '', $path);
    $path = preg_replace('/[^a-zA-Z0-9_\-]/', '', $path);
    return $path;
}

function getAlbums() {
    global $contentPath, $config;

    $albums = [];
    $items = scandir($contentPath);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if (strpos($item, '_') === 0) continue;

        $fullPath = $contentPath . '/' . $item;
        if (is_dir($fullPath)) {
            $photoCount = countPhotosInAlbum($item);
            $albums[] = [
                'name' => $item,
                'photoCount' => $photoCount
            ];
        }
    }

    return $albums;
}

function countPhotosInAlbum($album) {
    global $contentPath, $config;

    $albumPath = $contentPath . '/' . sanitizePath($album);
    if (!is_dir($albumPath)) return 0;

    $count = 0;
    $items = scandir($albumPath);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if (strpos($item, '_') === 0) continue;

        $fullPath = $albumPath . '/' . $item;
        if (is_file($fullPath)) {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, $config['allowed_extensions'])) {
                $count++;
            }
        }
    }

    return $count;
}

function getPhotosInAlbum($album) {
    global $contentPath, $config;

    $album = sanitizePath($album);
    $albumPath = $contentPath . '/' . $album;

    if (!is_dir($albumPath)) {
        return ['success' => false, 'error' => 'Album not found'];
    }

    $photos = [];
    $items = scandir($albumPath);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if (strpos($item, '_') === 0) continue;

        $fullPath = $albumPath . '/' . $item;
        if (is_file($fullPath)) {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, $config['allowed_extensions'])) {
                $fileSize = filesize($fullPath);
                $photos[] = [
                    'name' => $item,
                    'size' => $fileSize,
                    'sizeFormatted' => formatBytes($fileSize),
                    'url' => '/nano_photos_provider2/' . $config['content_folder'] . '/' . $album . '/' . rawurlencode($item),
                    'modified' => filemtime($fullPath)
                ];
            }
        }
    }

    usort($photos, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });

    return ['success' => true, 'photos' => $photos];
}

function formatBytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

function createAlbum($name) {
    global $contentPath;

    $name = sanitizePath($name);
    if (empty($name)) {
        return ['success' => false, 'error' => 'Invalid album name'];
    }

    $albumPath = $contentPath . '/' . $name;

    if (file_exists($albumPath)) {
        return ['success' => false, 'error' => 'Album already exists'];
    }

    if (mkdir($albumPath, 0755)) {
        return ['success' => true, 'message' => 'Album created'];
    } else {
        return ['success' => false, 'error' => 'Failed to create album'];
    }
}

function renameAlbum($oldName, $newName) {
    global $contentPath;

    $oldName = sanitizePath($oldName);
    $newName = sanitizePath($newName);

    if (empty($oldName) || empty($newName)) {
        return ['success' => false, 'error' => 'Invalid album name'];
    }

    $oldPath = $contentPath . '/' . $oldName;
    $newPath = $contentPath . '/' . $newName;

    if (!is_dir($oldPath)) {
        return ['success' => false, 'error' => 'Album not found'];
    }

    if (file_exists($newPath)) {
        return ['success' => false, 'error' => 'Target album name already exists'];
    }

    if (rename($oldPath, $newPath)) {
        return ['success' => true, 'message' => 'Album renamed'];
    } else {
        return ['success' => false, 'error' => 'Failed to rename album'];
    }
}

function deleteAlbum($name) {
    global $contentPath;

    $name = sanitizePath($name);
    $albumPath = $contentPath . '/' . $name;

    if (!is_dir($albumPath)) {
        return ['success' => false, 'error' => 'Album not found'];
    }

    if (!deleteDirectory($albumPath)) {
        return ['success' => false, 'error' => 'Failed to delete album'];
    }

    return ['success' => true, 'message' => 'Album deleted'];
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) return false;

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }

    return rmdir($dir);
}

function uploadPhotos($album, $files, $tags = '') {
    global $contentPath, $config;

    $album = sanitizePath($album);
    $albumPath = $contentPath . '/' . $album;

    if (!is_dir($albumPath)) {
        return ['success' => false, 'error' => 'Album not found'];
    }

    $maxSize = $config['max_file_size_mb'] * 1024 * 1024;
    $uploaded = [];
    $errors = [];

    $generatedFilename = '';
    if (!empty($tags)) {
        $tagParts = array_map('trim', explode(',', $tags));
        $sanitizedTags = array_map(function($tag) {
            return preg_replace('/[^a-zA-Z0-9\s]/', '', $tag);
        }, $tagParts);
        $generatedFilename = implode('_', $sanitizedTags);
        $generatedFilename = str_replace(' ', '_', $generatedFilename);

        $maxLength = $config['max_filename_length'] ?? 200;
        if (strlen($generatedFilename) > $maxLength) {
            $generatedFilename = substr($generatedFilename, 0, $maxLength);
        }
    }

    foreach ($files['name'] as $index => $fileName) {
        $tmpName = $files['tmp_name'][$index];
        $fileSize = $files['size'][$index];
        $error = $files['error'][$index];

        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = "$fileName: Upload error";
            continue;
        }

        if ($fileSize > $maxSize) {
            $errors[] = "$fileName: File too large";
            continue;
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, $config['allowed_extensions'])) {
            $errors[] = "$fileName: Invalid file type";
            continue;
        }

    if (!empty($generatedFilename)) {
            $safeName = $generatedFilename . '.' . $ext;
        } else {
            $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $fileName);
        }

        $targetPath = $albumPath . '/' . $safeName;

        $counter = 1;
        while (file_exists($targetPath)) {
            $nameWithoutExt = pathinfo($safeName, PATHINFO_FILENAME);
            if (!empty($generatedFilename)) {
                $safeName = $generatedFilename . '_' . $counter . '.' . $ext;
            } else {
                $safeName = $nameWithoutExt . '_' . $counter . '.' . $ext;
            }
            $targetPath = $albumPath . '/' . $safeName;
            $counter++;
        }

        if (move_uploaded_file($tmpName, $targetPath)) {
            $uploaded[] = $safeName;
            // Persist tags metadata (original tags string with commas)
            if (!empty($tagParts)) {
                setPhotoTagsMeta($album, $safeName, $tagParts);
            }
        } else {
            $errors[] = "$fileName: Failed to save";
        }
    }

    return [
        'success' => count($uploaded) > 0,
        'uploaded' => $uploaded,
        'errors' => $errors,
        'message' => count($uploaded) . ' files uploaded' . (count($errors) > 0 ? ', ' . count($errors) . ' failed' : '')
    ];
}

function deletePhoto($album, $photo) {
    global $contentPath;

    $album = sanitizePath($album);
    $photo = basename($photo);
    $photoPath = $contentPath . '/' . $album . '/' . $photo;

    if (!file_exists($photoPath)) {
        return ['success' => false, 'error' => 'Photo not found'];
    }

    if (unlink($photoPath)) {
        return ['success' => true, 'message' => 'Photo deleted'];
    } else {
        return ['success' => false, 'error' => 'Failed to delete photo'];
    }
}

function renamePhoto($album, $oldName, $newName) {
    global $contentPath, $config;

    $album = sanitizePath($album);
    $oldName = basename($oldName);
    $newName = basename($newName);

    $ext = strtolower(pathinfo($newName, PATHINFO_EXTENSION));
    if (!in_array($ext, $config['allowed_extensions'])) {
        return ['success' => false, 'error' => 'Invalid file extension'];
    }

    $oldPath = $contentPath . '/' . $album . '/' . $oldName;
    $newPath = $contentPath . '/' . $album . '/' . $newName;

    if (!file_exists($oldPath)) {
        return ['success' => false, 'error' => 'Photo not found'];
    }

    if (file_exists($newPath)) {
        return ['success' => false, 'error' => 'A photo with that name already exists'];
    }

    if (rename($oldPath, $newPath)) {
        // Move tags metadata to new filename if present
        $data = loadTags();
        if (isset($data[$album][$oldName])) {
            $data[$album][$newName] = $data[$album][$oldName];
            unset($data[$album][$oldName]);
            saveTags($data);
        }
        return ['success' => true, 'message' => 'Photo renamed'];
    } else {
        return ['success' => false, 'error' => 'Failed to rename photo'];
    }
}

function movePhoto($album, $photo, $targetAlbum) {
    global $contentPath;

    $album = sanitizePath($album);
    $targetAlbum = sanitizePath($targetAlbum);
    $photo = basename($photo);

    $sourcePath = $contentPath . '/' . $album . '/' . $photo;
    $targetPath = $contentPath . '/' . $targetAlbum . '/' . $photo;

    if (!file_exists($sourcePath)) {
        return ['success' => false, 'error' => 'Photo not found'];
    }

    if (!is_dir($contentPath . '/' . $targetAlbum)) {
        return ['success' => false, 'error' => 'Target album not found'];
    }

    if (file_exists($targetPath)) {
        return ['success' => false, 'error' => 'A photo with that name already exists in target album'];
    }

    if (rename($sourcePath, $targetPath)) {
        // Move metadata across albums
        $data = loadTags();
        if (isset($data[$album][$photo])) {
            if (!isset($data[$targetAlbum])) $data[$targetAlbum] = [];
            $data[$targetAlbum][$photo] = $data[$album][$photo];
            unset($data[$album][$photo]);
            saveTags($data);
        }
        return ['success' => true, 'message' => 'Photo moved'];
    } else {
        return ['success' => false, 'error' => 'Failed to move photo'];
    }
}

switch ($action) {
    case 'getAlbums':
        echo json_encode(['success' => true, 'albums' => getAlbums()]);
        break;

    case 'getPhotos':
        $album = $_GET['album'] ?? '';
        echo json_encode(getPhotosInAlbum($album));
        break;

    case 'createAlbum':
        $name = $_POST['name'] ?? '';
        echo json_encode(createAlbum($name));
        break;

    case 'renameAlbum':
        $oldName = $_POST['oldName'] ?? '';
        $newName = $_POST['newName'] ?? '';
        echo json_encode(renameAlbum($oldName, $newName));
        break;

    case 'deleteAlbum':
        $name = $_POST['name'] ?? '';
        echo json_encode(deleteAlbum($name));
        break;

    case 'uploadPhotos':
        $album = $_POST['album'] ?? '';
        $tags = $_POST['tags'] ?? '';
        if (empty($_FILES['photos'])) {
            echo json_encode(['success' => false, 'error' => 'No files uploaded']);
        } else {
            echo json_encode(uploadPhotos($album, $_FILES['photos'], $tags));
        }
        break;

    case 'deletePhoto':
        $album = $_POST['album'] ?? '';
        $photo = $_POST['photo'] ?? '';
        echo json_encode(deletePhoto($album, $photo));
        break;

    case 'renamePhoto':
        $album = $_POST['album'] ?? '';
        $oldName = $_POST['oldName'] ?? '';
        $newName = $_POST['newName'] ?? '';
        echo json_encode(renamePhoto($album, $oldName, $newName));
        break;

    case 'movePhoto':
        $album = $_POST['album'] ?? '';
        $photo = $_POST['photo'] ?? '';
        $targetAlbum = $_POST['targetAlbum'] ?? '';
        echo json_encode(movePhoto($album, $photo, $targetAlbum));
        break;

    case 'getPhotoTags':
        $album = $_GET['album'] ?? '';
        $photo = $_GET['photo'] ?? '';
        echo json_encode(['success' => true, 'tags' => getPhotoTagsMeta($album, $photo)]);
        break;

    case 'setPhotoTags':
        $album = $_POST['album'] ?? '';
        $photo = $_POST['photo'] ?? '';
        $tags = $_POST['tags'] ?? '';
        $tagParts = is_array($tags) ? $tags : array_map('trim', explode(',', $tags));
        setPhotoTagsMeta($album, $photo, $tagParts);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
