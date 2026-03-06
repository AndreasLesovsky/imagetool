<?php
require("includes/config.inc.php");
require("includes/common.inc.php");
require("includes/pictures.inc.php"); // enthält Funktion scaleImage

$whitelist = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/avif'];
$msgCrop = "";
$msgScale = "";
$msgwebpConvert = "";
$msgWatermark = "";

// Bild Zuschneiden
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cropImage'])) {
    if (isset($_FILES['cropImage'])) {
        $uploadedImage = $_FILES['cropImage'];
        $cropLeft = intval($_POST['left']);
        $cropTop = intval($_POST['top']);
        $cropRight = intval($_POST['right']);
        $cropBottom = intval($_POST['bottom']);

        if (in_array($uploadedImage['type'], $whitelist)) {
            $tmpName = $uploadedImage['tmp_name'];
            $originalName = basename($uploadedImage['name']);
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $uniqueId = uniqid();
            $dir = "./bilder/$uniqueId";
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $originalPath = "$dir/$originalName";
            move_uploaded_file($tmpName, $originalPath);

            $image = null;
            switch ($uploadedImage['type']) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($originalPath);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($originalPath);
                    break;
                case 'image/webp':
                    $image = imagecreatefromwebp($originalPath);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($originalPath);
                    break;
                case 'image/bmp':
                    $image = imagecreatefrombmp($originalPath);
                    break;
                case 'image/avif':
                    $image = function_exists('imagecreatefromavif') ? imagecreatefromavif($originalPath) : null;
                    break;
            }

            $info = getimagesize($originalPath);
            $w_old = $info[0];
            $h_old = $info[1];

            if ($image) {
                $cropWidth = $w_old - $cropRight - $cropLeft;
                $cropHeight = $h_old - $cropBottom - $cropTop;

                $croppedImage = imagecrop($image, ['x' => $cropLeft, 'y' => $cropTop, 'width' => $cropWidth, 'height' => $cropHeight]);

                if ($croppedImage !== FALSE) {
                    $croppedPath = "$dir/" . pathinfo($originalName, PATHINFO_FILENAME) . "_cropped.png";
                    imagepng($croppedImage, $croppedPath);
                    imagedestroy($croppedImage);

                    $msgCrop .= "<div class='alert alert-success container shadow-sm rounded-3'>
                                    <h3 class='fs-5 fw-semibold mb-2'>Zugeschnittenes Bild:</h3>
                                    <img src='$croppedPath' alt='Zugeschnittenes Bild' class='img-fluid rounded-2 mb-3' style='max-height:400px; max-width:600px;'>
                                    <div>
                                        <a href='$croppedPath' download class='btn btn-primary btn-submit fw-semibold'>
                                            <i class='bi bi-download me-1'></i>Bild herunterladen
                                        </a>
                                    </div>
                                </div>";
                } else {
                    $msgCrop .= "<div class='alert alert-danger container shadow-sm rounded-3'>
                                    <i class='bi bi-exclamation-triangle-fill me-2'></i>
                                    Zuschneiden fehlgeschlagen. Die angegebenen Werte liegen außerhalb der Bildgrenzen.
                                </div>";
                }

                imagedestroy($image);
            }
        } else {
            $msgCrop = "<div class='alert alert-danger container shadow-sm rounded-3'>
                            <i class='bi bi-exclamation-triangle-fill me-2'></i>
                            Ungültiges Dateiformat. Erlaubt sind nur JPEG, PNG, WebP, GIF, BMP und AVIF Dateien.
                        </div>";
        }
    }
}

// Bild Skalieren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['scaleImage'])) {
    if (isset($_FILES['scaleImage'])) {
        $uploadedImage = $_FILES['scaleImage'];
        $uniqueId = uniqid();
        $editType = 'scale';
        $originalName = basename($uploadedImage['name']);
        $dir = "./bilder/$uniqueId/{$originalName}_scaled_sizes";

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmpName = $uploadedImage['tmp_name'];
        $originalPath = "$dir/$originalName";
        move_uploaded_file($tmpName, $originalPath);

        $sizes = [
            intval($_POST['size_1']),
            intval($_POST['size_2']),
            intval($_POST['size_3']),
            intval($_POST['size_4']),
        ];

        foreach ($sizes as $size) {
            if ($size > 0) {
                scaleImage($originalPath, $size);
            }
        }

        $msgScale .= "<div class='alert alert-success container shadow-sm rounded-3'>
                        <i class='bi bi-check-circle-fill me-2'></i>
                        Bild wurde erfolgreich skaliert!<br>
                        <a href='download.php?unique_id=" . urlencode($uniqueId) . "&original_name=" . urlencode($originalName) . "&edit_type=" . urlencode($editType) . "' class='btn btn-primary btn-submit fw-semibold mt-2'>
                            <i class='bi bi-download me-1'></i>Bilder herunterladen
                        </a>
                    </div>";
    }
}

// Bild Konvertierung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['webpConvert'])) {
    if (count($_FILES['webpConvert']['name']) > 0) {
        $uniqueId = uniqid();
        $outputFormat = ($_POST['output_format'] ?? 'webp') === 'jpeg' ? 'jpeg' : 'webp';
        $quality = max(50, min(100, intval($_POST['quality'] ?? 80)));
        $editType = $outputFormat === 'jpeg' ? 'jpeg' : 'webp';
        $ext = $outputFormat === 'jpeg' ? 'jpg' : 'webp';

        $folderName = $outputFormat === 'jpeg' ? 'jpeg_images' : 'webp_images';
        $dir = "./bilder/$uniqueId/$folderName/";
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ($_FILES['webpConvert']['name'] as $key => $originalName) {
            $tempName = $_FILES['webpConvert']['tmp_name'][$key];
            $fileType = mime_content_type($tempName);

            if (in_array($fileType, $whitelist)) {
                $filename = pathinfo($originalName, PATHINFO_FILENAME);
                $outputPath = $dir . $filename . '.' . $ext;

                $image = null;
                if ($fileType == 'image/jpeg') {
                    $image = imagecreatefromjpeg($tempName);
                } elseif ($fileType == 'image/png') {
                    $image = imagecreatefrompng($tempName);
                    if (imagecolorstotal($image) <= 256) {
                        $width = imagesx($image);
                        $height = imagesy($image);
                        $newImage = imagecreatetruecolor($width, $height);
                        imagecopy($newImage, $image, 0, 0, 0, 0, $width, $height);
                        imagedestroy($image);
                        $image = $newImage;
                    }
                } elseif ($fileType == 'image/gif') {
                    $image = imagecreatefromgif($tempName);
                } elseif ($fileType == 'image/webp') {
                    $image = imagecreatefromwebp($tempName);
                } elseif ($fileType == 'image/bmp') {
                    $image = imagecreatefrombmp($tempName);
                } elseif ($fileType == 'image/avif') {
                    $image = function_exists('imagecreatefromavif') ? imagecreatefromavif($tempName) : null;
                }

                if ($image) {
                    if ($outputFormat === 'jpeg') {
                        // JPEG braucht weißen Hintergrund für transparente Bilder
                        $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
                        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                        imagejpeg($bg, $outputPath, $quality);
                        imagedestroy($bg);
                    } else {
                        imagewebp($image, $outputPath, $quality);
                    }
                    imagedestroy($image);
                }
            } else {
                $msgwebpConvert .= "<div class='alert alert-danger shadow-sm rounded-3'>
                                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                                        Ungültiges Dateiformat. Erlaubt sind nur JPEG, PNG, WebP, GIF, BMP und AVIF Dateien.
                                    </div>";
            }
        }

        $formatLabel = strtoupper($ext);
        if (count($_FILES['webpConvert']['name']) === 1) {
            $msgwebpConvert .= "<div class='alert alert-success shadow-sm rounded-3'>
                                    <i class='bi bi-check-circle-fill me-2'></i>
                                    Bild erfolgreich zu {$formatLabel} konvertiert (Qualität: {$quality}%).<br>
                                    <a href='$outputPath' download class='btn btn-primary btn-submit fw-semibold mt-2'>
                                        <i class='bi bi-download me-1'></i>Bild herunterladen
                                    </a>
                                </div>";
        }

        if (count($_FILES['webpConvert']['name']) > 1) {
            $msgwebpConvert .= "<div class='alert alert-success shadow-sm rounded-3'>
                                    <i class='bi bi-check-circle-fill me-2'></i>
                                    Bilder erfolgreich zu {$formatLabel} konvertiert (Qualität: {$quality}%).<br>
                                    <a href='download.php?unique_id=" . urlencode($uniqueId) . "&original_name=" . urlencode($originalName) . "&edit_type=" . urlencode($editType) . "' class='btn btn-primary btn-submit fw-semibold mt-2'>
                                        <i class='bi bi-download me-1'></i>Bilder herunterladen
                                    </a>
                                </div>";
        }
    } else {
        $msgwebpConvert .= "<div class='alert alert-danger container shadow-sm rounded-3'>
                                <i class='bi bi-exclamation-triangle-fill me-2'></i>
                                Es wurde kein Bild ausgewählt.
                            </div>";
    }
}

// Wasserzeichen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['wm_main']) && isset($_FILES['wm_logo'])) {
    $logoFile = $_FILES['wm_logo'];

    if (!in_array(mime_content_type($logoFile['tmp_name']), $whitelist)) {
        $msgWatermark = "<div class='alert alert-danger shadow-sm rounded-3'>
                            <i class='bi bi-exclamation-triangle-fill me-2'></i>
                            Ungültiges Dateiformat. Erlaubt sind JPEG, PNG, WebP, GIF, BMP und AVIF.
                        </div>";
    } else {
        $position  = $_POST['wm_position'] ?? 'bottom-right';
        $opacity   = max(0, min(100, intval($_POST['wm_opacity'] ?? 80)));
        $margin    = max(0, intval($_POST['wm_margin'] ?? 20));
        $wmScale   = max(5, min(100, intval($_POST['wm_scale'] ?? 25)));

        function loadImageFromFile(string $path, string $mime) {
            switch ($mime) {
                case 'image/jpeg': return imagecreatefromjpeg($path);
                case 'image/png':  return imagecreatefrompng($path);
                case 'image/webp': return imagecreatefromwebp($path);
                case 'image/gif':  return imagecreatefromgif($path);
                case 'image/bmp':  return imagecreatefrombmp($path);
                case 'image/avif': return function_exists('imagecreatefromavif') ? imagecreatefromavif($path) : null;
                default: return null;
            }
        }

        $logoMime  = mime_content_type($logoFile['tmp_name']);
        $logoSrc   = loadImageFromFile($logoFile['tmp_name'], $logoMime);

        if (!$logoSrc) {
            $msgWatermark = "<div class='alert alert-danger shadow-sm rounded-3'>
                                <i class='bi bi-exclamation-triangle-fill me-2'></i>
                                Wasserzeichen-Bild konnte nicht geladen werden.
                            </div>";
        } else {
            $uniqueId  = uniqid();
            $outDir    = "./bilder/$uniqueId/watermarked/";
            mkdir($outDir, 0755, true);

            $logoW_src = imagesx($logoSrc);
            $logoH_src = imagesy($logoSrc);
            $logoRatio = $logoW_src > 0 ? $logoH_src / $logoW_src : 1;

            $mainFiles   = $_FILES['wm_main'];
            $fileCount   = count($mainFiles['name']);
            $errorFiles  = [];
            $outputPaths = [];

            for ($i = 0; $i < $fileCount; $i++) {
                $mainMime = mime_content_type($mainFiles['tmp_name'][$i]);
                if (!in_array($mainMime, $whitelist)) {
                    $errorFiles[] = htmlspecialchars($mainFiles['name'][$i]);
                    continue;
                }

                $main = loadImageFromFile($mainFiles['tmp_name'][$i], $mainMime);
                if (!$main) { $errorFiles[] = htmlspecialchars($mainFiles['name'][$i]); continue; }

                $mW = imagesx($main); $mH = imagesy($main);

                // Wasserzeichen für dieses Bild skalieren
                $targetW = intval($mW * $wmScale / 100);
                $targetH = intval($targetW * $logoRatio);
                $logoScaled = imagecreatetruecolor($targetW, $targetH);
                imagealphablending($logoScaled, false);
                imagesavealpha($logoScaled, true);
                $transparent = imagecolorallocatealpha($logoScaled, 0, 0, 0, 127);
                imagefilledrectangle($logoScaled, 0, 0, $targetW, $targetH, $transparent);
                imagecopyresampled($logoScaled, $logoSrc, 0, 0, 0, 0, $targetW, $targetH, $logoW_src, $logoH_src);

                // Position berechnen
                [$posV, $posH] = explode('-', $position) + ['center', 'center'];
                $x = match($posH) {
                    'left'   => $margin,
                    'right'  => $mW - $targetW - $margin,
                    default  => intval(($mW - $targetW) / 2),
                };
                $y = match($posV) {
                    'top'    => $margin,
                    'bottom' => $mH - $targetH - $margin,
                    default  => intval(($mH - $targetH) / 2),
                };

                imagecopymerge($main, $logoScaled, $x, $y, 0, 0, $targetW, $targetH, $opacity);
                imagedestroy($logoScaled);

                $origName   = pathinfo(basename($mainFiles['name'][$i]), PATHINFO_FILENAME);
                $outputPath = $outDir . $origName . '_watermarked.png';
                imagepng($main, $outputPath);
                imagedestroy($main);
                $outputPaths[] = $outputPath;
            }

            imagedestroy($logoSrc);

            if (count($outputPaths) === 0) {
                $msgWatermark = "<div class='alert alert-danger shadow-sm rounded-3'>
                                    <i class='bi bi-exclamation-triangle-fill me-2'></i>
                                    Kein Bild konnte verarbeitet werden.
                                </div>";
            } elseif (count($outputPaths) === 1) {
                $msgWatermark = "<div class='alert alert-success shadow-sm rounded-3'>
                                    <i class='bi bi-check-circle-fill me-2'></i>
                                    Wasserzeichen erfolgreich hinzugefügt.<br>
                                    <a href='{$outputPaths[0]}' download class='btn btn-primary btn-submit fw-semibold mt-2'>
                                        <i class='bi bi-download me-1'></i>Bild herunterladen
                                    </a>
                                </div>";
            } else {
                $msgWatermark = "<div class='alert alert-success shadow-sm rounded-3'>
                                    <i class='bi bi-check-circle-fill me-2'></i>
                                    " . count($outputPaths) . " Bilder mit Wasserzeichen versehen.<br>
                                    <a href='download.php?unique_id=" . urlencode($uniqueId) . "&edit_type=watermark' class='btn btn-primary btn-submit fw-semibold mt-2'>
                                        <i class='bi bi-download me-1'></i>Bilder herunterladen
                                    </a>
                                </div>";
            }

            if (!empty($errorFiles)) {
                $msgWatermark .= "<div class='alert alert-warning shadow-sm rounded-3 mt-2'>
                                    <i class='bi bi-exclamation-triangle-fill me-2'></i>
                                    Folgende Dateien konnten nicht verarbeitet werden: " . implode(', ', $errorFiles) . "
                                  </div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ImageTool</title>
    <link rel="stylesheet" href="css/custom-bootstrap.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="icon" type="image/svg" href="assets/icon.svg">
</head>

<body>

    <!-- Loading Spinner Overlay -->
    <div id="spinner-overlay" class="spinner-overlay d-none" aria-live="polite" aria-label="Wird verarbeitet">
        <div class="spinner-box">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Wird verarbeitet...</span>
            </div>
            <p class="mt-3 fw-semibold fs-5">Wird verarbeitet…</p>
        </div>
    </div>

    <header class="header-glass shadow-sm py-3">
        <div class="container d-flex justify-content-between align-items-center gap-1 flex-wrap">
            <h1 class="brand-gradient fs-2 fw-bold lh-1 text-nowrap mb-0">
                <i class="bi bi-images me-1"></i>ImageTool
            </h1>

            <nav aria-label="Werkzeug-Navigation">
                <div class="btn-group nav-tools" role="group">
                    <button type="button"
                        class="btn btn-sm btn-outline-primary fw-semibold btn-primary-active"
                        data-target="content1">
                        <i class="bi bi-crop" aria-hidden="true"></i>
                        <span class="nav-label">Zuschneiden</span>
                    </button>
                    <button type="button"
                        class="btn btn-sm btn-outline-primary fw-semibold"
                        data-target="content2">
                        <i class="bi bi-arrows-collapse-vertical" aria-hidden="true"></i>
                        <span class="nav-label">Skalieren</span>
                    </button>
                    <button type="button"
                        class="btn btn-sm btn-outline-primary fw-semibold"
                        data-target="content3">
                        <i class="bi bi-file-earmark-image" aria-hidden="true"></i>
                        <span class="nav-label">Konvertieren</span>
                    </button>
                    <button type="button"
                        class="btn btn-sm btn-outline-primary fw-semibold"
                        data-target="content4">
                        <i class="bi bi-scissors" aria-hidden="true"></i>
                        <span class="nav-label">Hintergrund</span>
                    </button>
                    <button type="button"
                        class="btn btn-sm btn-outline-primary fw-semibold"
                        data-target="content6">
                        <i class="bi bi-badge-tm" aria-hidden="true"></i>
                        <span class="nav-label">Wasserzeichen</span>
                    </button>
                    <button type="button"
                        class="btn btn-sm btn-outline-primary fw-semibold"
                        data-target="content5">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <span class="nav-label">Über</span>
                    </button>
                </div>
            </nav>

            <button type="button" id="theme-toggler"
                class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center"
                aria-label="Dunkler Modus" title="Dunkler Modus"
                style="width:38px; height:38px; padding:0;">
                <i class="bi bi-sun-fill theme-icon sun-icon" aria-hidden="true"></i>
                <i class="bi bi-moon-fill theme-icon moon-icon visually-hidden" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    <main class="my-3 my-md-5">

        <!-- ============================================================
             SECTION 1: ZUSCHNEIDEN
             ============================================================ -->
        <section id="content1" class="content-section active">
            <div class="container">
            <h2 class="display-6 fw-semibold mb-4">Zuschneiden</h2>
            <?php echo $msgCrop; ?>

            <form method="post" enctype="multipart/form-data" class="server-form">

                <!-- Preview / Canvas -->
                <div class="mb-3">
                    <div class="p-3 rounded-3 border bg-body-secondary image-preview-container">
                        <p class="fw-semibold text-secondary small text-uppercase mb-2 tracking-wide">
                            <i class="bi bi-image me-1"></i>Vorschau
                        </p>

                        <!-- Canvas + Drag Handles (hidden until image loaded) -->
                        <div class="crop-handle-wrapper" style="display:none;">
                            <canvas id="crop-canvas-full" aria-label="Crop-Vorschau"></canvas>
                            <!-- Kanten-Handles -->
                            <div class="crop-handle crop-handle-left"   data-side="left"   title="Links ziehen"></div>
                            <div class="crop-handle crop-handle-top"    data-side="top"    title="Oben ziehen"></div>
                            <div class="crop-handle crop-handle-right"  data-side="right"  title="Rechts ziehen"></div>
                            <div class="crop-handle crop-handle-bottom" data-side="bottom" title="Unten ziehen"></div>
                            <!-- Ecken-Handles -->
                            <div class="crop-handle crop-handle-nw" data-corner="nw" title="Ecke ziehen"></div>
                            <div class="crop-handle crop-handle-ne" data-corner="ne" title="Ecke ziehen"></div>
                            <div class="crop-handle crop-handle-sw" data-corner="sw" title="Ecke ziehen"></div>
                            <div class="crop-handle crop-handle-se" data-corner="se" title="Ecke ziehen"></div>
                        </div>

                        <div class="image-name text-muted">Keine Datei ausgewählt.</div>
                        <p id="crop-dimensions" class="text-muted small mb-0 mt-1"></p>
                        <p class="text-muted small mt-2 mb-0 drag-hint"><i class="bi bi-upload me-1"></i>Datei auswählen oder hierher ziehen</p>
                    </div>
                </div>

                <!-- Alerts -->
                <div class="alert alert-danger shadow-sm visually-hidden alert-danger-max-file-size rounded-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Die Größe des Uploads überschreitet die maximal zulässige Grenze von 256 MB.
                </div>

                <!-- Form Fields -->
                <div class="form-card card shadow-sm p-4">
                    <div class="mb-4">
                        <label for="cropImageInput" class="form-label">
                            <i class="bi bi-upload me-1"></i>Bild zum Zuschneiden
                            <span class="text-muted fw-normal">(max. 256 MB)</span>
                        </label>
                        <div class="input-group">
                            <input type="file" class="form-control file-input" id="cropImageInput" name="cropImage"
                                accept="image/jpeg, image/png, image/webp, image/gif, image/bmp, image/avif" required>
                            <button type="button" id="crop-clear-btn" title="Auswahl löschen"
                                class="btn btn-outline-danger" disabled>
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                            </button>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="bi bi-info-circle me-1"></i>
                            Zuschneidebereiche per Pixeleingabe festlegen oder direkt im Vorschaubild verschieben.
                        </small>
                    </div>

                    <!-- Aspect Ratio Lock -->
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-aspect-ratio me-1"></i>Seitenverhältnis</label>
                        <div class="crop-ratio-bar">
                            <button type="button" class="btn-ratio active" data-ratio="">Frei</button>
                            <button type="button" class="btn-ratio" data-ratio="1:1">1:1</button>
                            <button type="button" class="btn-ratio" data-ratio="4:3">4:3</button>
                            <button type="button" class="btn-ratio" data-ratio="3:4">3:4</button>
                            <button type="button" class="btn-ratio" data-ratio="16:9">16:9</button>
                            <button type="button" class="btn-ratio" data-ratio="9:16">9:16</button>
                            <button type="button" class="btn-ratio" data-ratio="3:2">3:2</button>
                            <button type="button" class="btn-ratio" data-ratio="2:3">2:3</button>
                            <button type="button" class="btn-ratio" data-ratio="2:1">2:1</button>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <label for="crop-left" class="form-label">
                                <i class="bi bi-arrow-bar-right me-1"></i>Von links
                            </label>
                            <div class="input-group">
                                <input type="number" id="crop-left" name="left" min="0"
                                    class="form-control" placeholder="0" aria-label="Von links wegschneiden">
                                <span class="input-group-text text-muted">px</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="crop-top" class="form-label">
                                <i class="bi bi-arrow-bar-down me-1"></i>Von oben
                            </label>
                            <div class="input-group">
                                <input type="number" id="crop-top" name="top" min="0"
                                    class="form-control" placeholder="0" aria-label="Von oben wegschneiden">
                                <span class="input-group-text text-muted">px</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="crop-right" class="form-label">
                                <i class="bi bi-arrow-bar-left me-1"></i>Von rechts
                            </label>
                            <div class="input-group">
                                <input type="number" id="crop-right" name="right" min="0"
                                    class="form-control" placeholder="0" aria-label="Von rechts wegschneiden">
                                <span class="input-group-text text-muted">px</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="crop-bottom" class="form-label">
                                <i class="bi bi-arrow-bar-up me-1"></i>Von unten
                            </label>
                            <div class="input-group">
                                <input type="number" id="crop-bottom" name="bottom" min="0"
                                    class="form-control" placeholder="0" aria-label="Von unten wegschneiden">
                                <span class="input-group-text text-muted">px</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-lg btn-primary btn-submit fw-semibold" disabled>
                        <i class="bi bi-crop me-2" aria-hidden="true"></i>Zuschneiden
                    </button>
                </div>
            </form>
            </div>
        </section>

        <!-- ============================================================
             SECTION 2: SKALIEREN
             ============================================================ -->
        <section id="content2" class="content-section">
            <div class="container">
            <h2 class="display-6 fw-semibold mb-4">Skalieren</h2>
            <?php echo $msgScale; ?>

            <form method="post" enctype="multipart/form-data" class="server-form">

                <div class="mb-3">
                    <div class="p-3 rounded-3 border bg-body-secondary image-preview-container">
                        <p class="fw-semibold text-secondary small text-uppercase mb-2">
                            <i class="bi bi-image me-1"></i>Vorschau
                        </p>
                        <img class="image-preview img-fluid rounded-2" src="" alt="Bildvorschau"
                            style="display:none; max-height:400px;">
                        <div class="image-name text-muted">Keine Datei ausgewählt.</div>
                        <p class="text-muted small mt-2 mb-0 drag-hint"><i class="bi bi-upload me-1"></i>Datei auswählen oder hierher ziehen</p>
                    </div>
                </div>

                <div class="alert alert-danger shadow-sm visually-hidden alert-danger-max-file-size rounded-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Die Größe des Uploads überschreitet die maximal zulässige Grenze von 256 MB.
                </div>

                <div class="form-card card shadow-sm p-4">
                    <div class="mb-4">
                        <label for="scaleImage" class="form-label">
                            <i class="bi bi-upload me-1"></i>Bild zum Skalieren
                            <span class="text-muted fw-normal">(max. 256 MB)</span>
                        </label>
                        <div class="input-group">
                            <input type="file" class="form-control file-input" id="scaleImage" name="scaleImage"
                                accept="image/jpeg, image/png, image/webp, image/gif, image/bmp, image/avif" required>
                            <button type="button" title="Auswahl löschen" class="btn btn-outline-danger clear-button" disabled>
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <p class="form-label mb-2">
                        <i class="bi bi-rulers me-1"></i>Zielbreiten
                        <span class="text-muted fw-normal small">(Seitenverhältnis wird beibehalten)</span>
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-1-circle"></i></span>
                                <input type="number" name="size_1" class="form-control" placeholder="z.B. 1920" min="1">
                                <span class="input-group-text text-muted">px</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-2-circle"></i></span>
                                <input type="number" name="size_2" class="form-control" placeholder="z.B. 1280" min="1">
                                <span class="input-group-text text-muted">px</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-3-circle"></i></span>
                                <input type="number" name="size_3" class="form-control" placeholder="z.B. 800" min="1">
                                <span class="input-group-text text-muted">px</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-4-circle"></i></span>
                                <input type="number" name="size_4" class="form-control" placeholder="z.B. 400" min="1">
                                <span class="input-group-text text-muted">px</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-lg btn-primary btn-submit fw-semibold" disabled>
                        <i class="bi bi-arrows-collapse-vertical me-2" aria-hidden="true"></i>Skalieren
                    </button>
                </div>
            </form>
            </div>
        </section>

        <!-- ============================================================
             SECTION 3: WEBP KONVERTIERUNG
             ============================================================ -->
        <section id="content3" class="content-section">
            <div class="container">
            <h2 class="display-6 fw-semibold mb-4">Konvertieren</h2>
            <?php echo $msgwebpConvert; ?>

            <form method="POST" enctype="multipart/form-data" class="server-form">

                <div class="mb-3">
                    <div class="p-3 rounded-3 border bg-body-secondary image-preview-container">
                        <p class="fw-semibold text-secondary small text-uppercase mb-2">
                            <i class="bi bi-images me-1"></i>Vorschau
                        </p>
                        <img class="image-preview img-fluid rounded-2" src="" alt="Bildvorschau"
                            style="display:none; max-height:400px;">
                        <div class="image-name text-muted">Keine Datei(en) ausgewählt.</div>
                        <p class="text-muted small mt-2 mb-0 drag-hint"><i class="bi bi-upload me-1"></i>Dateien auswählen oder hierher ziehen</p>
                    </div>
                </div>

                <div class="alert alert-danger shadow-sm visually-hidden alert-danger-max-file-size rounded-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Die Größe des Uploads überschreitet die maximal zulässige Grenze von 256 MB.
                </div>
                <div class="alert alert-warning shadow-sm visually-hidden alert-danger-max-file-uploads rounded-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Bitte maximal 20 Dateien auswählen.
                </div>

                <div class="form-card card shadow-sm p-4">
                    <div class="mb-4">
                        <label for="webpConvert" class="form-label">
                            <i class="bi bi-upload me-1"></i>Bild(er) konvertieren
                            <span class="text-muted fw-normal">(max. 20 Dateien, max. 256 MB)</span>
                        </label>
                        <div class="input-group">
                            <input type="file" class="form-control file-input" id="webpConvert"
                                name="webpConvert[]"
                                accept="image/jpeg, image/png, image/webp, image/gif, image/bmp, image/avif"
                                required multiple>
                            <button type="button" title="Auswahl löschen" class="btn btn-outline-danger clear-button" disabled>
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                            </button>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="bi bi-info-circle me-1"></i>
                            Mehrere Bilder werden als ZIP-Datei gebündelt.
                        </small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="bi bi-file-earmark me-1"></i>Ausgabeformat</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="output_format" id="fmt-webp" value="webp" checked>
                                <label class="form-check-label" for="fmt-webp">WebP</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="output_format" id="fmt-jpeg" value="jpeg">
                                <label class="form-check-label" for="fmt-jpeg">JPEG</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="quality-slider" class="form-label">
                            <i class="bi bi-sliders me-1"></i>Ausgabequalität:
                            <span id="quality-value" class="fw-semibold">80</span> %
                        </label>
                        <input type="range" class="form-range" id="quality-slider" name="quality"
                            min="50" max="100" step="5" value="80">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">50 % (kleinere Datei)</small>
                            <small class="text-muted">100 % (beste Qualität)</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-lg btn-primary btn-submit fw-semibold" disabled>
                        <i class="bi bi-file-earmark-image me-2" aria-hidden="true"></i>Konvertieren
                    </button>
                </div>
            </form>
            </div>
        </section>

        <!-- ============================================================
             SECTION 4: HINTERGRUND ENTFERNEN (client-side AI)
             ============================================================ -->
        <section id="content4" class="content-section">
            <div class="container">
            <h2 class="display-6 fw-semibold mb-4">Hintergrund entfernen</h2>

            <form>
                <!-- Image Preview -->
                <div class="mb-3">
                    <div class="p-3 rounded-3 border bg-body-secondary image-preview-container">
                        <p class="fw-semibold text-secondary small text-uppercase mb-2">
                            <i class="bi bi-image me-1"></i>Vorschau
                        </p>
                        <img id="bg-preview-img" class="image-preview" style="display:none; max-width:100%; border-radius:0.375rem;" alt="Vorschau">
                        <span id="bg-file-name" class="image-name text-muted">Keine Datei ausgewählt.</span>
                        <p class="text-muted small mt-2 mb-0 drag-hint"><i class="bi bi-upload me-1"></i>Datei auswählen oder hierher ziehen</p>
                    </div>
                </div>

                <div class="form-card card shadow-sm p-4">

                    <!-- Info Badge -->
                    <p class="text-muted small mb-4">
                        <i class="bi bi-cpu me-1 text-primary"></i>
                        KI läuft direkt im Browser &mdash; kein Upload, kein Server, kein API-Key.
                        Modell: <code>briaai/RMBG-1.4</code>
                    </p>

                    <!-- File Input -->
                    <div class="mb-4">
                        <label for="bg-file-input" class="form-label">
                            <i class="bi bi-upload me-1"></i>Bild auswählen
                        </label>
                        <div class="input-group">
                            <input type="file" class="form-control file-input" id="bg-file-input"
                                accept="image/jpeg, image/png, image/webp, image/gif, image/bmp, image/avif">
                            <button type="button" id="bg-clear-btn" title="Auswahl löschen"
                                class="btn btn-outline-danger" disabled>
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Model Download Progress (first time only) -->
                    <div id="bg-progress-container" class="d-none mb-4">
                        <p class="fw-semibold mb-1">
                            <i class="bi bi-cloud-download me-1"></i>
                            Modell wird heruntergeladen…
                            (<span id="bg-progress-percent">0</span> %)
                        </p>
                        <div class="progress" style="height: 10px;">
                            <div id="bg-progress-bar"
                                class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                role="progressbar" style="width: 0%"
                                aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Einmalig ~168 MB — wird danach im Browser zwischengespeichert.
                        </small>
                    </div>

                    <!-- Processing Spinner -->
                    <div id="bg-processing" class="d-none text-center py-4 mb-4">
                        <div class="spinner-border text-primary mb-2" style="width:2.5rem;height:2.5rem;"
                            role="status" aria-label="KI verarbeitet das Bild">
                        </div>
                        <p class="fw-semibold mb-0">KI verarbeitet das Bild…</p>
                        <small class="text-muted">Kann je nach Bildgröße einige Sekunden dauern.</small>
                    </div>

                    <!-- Action Button -->
                    <button id="bg-remove-btn" disabled
                        class="btn btn-lg btn-primary btn-submit fw-semibold mb-4">
                        <i class="bi bi-scissors me-2" aria-hidden="true"></i>Hintergrund entfernen
                    </button>

                    <!-- Result: Before / After -->
                    <div id="bg-result" class="d-none">
                        <hr class="my-3">
                        <h3 class="fs-5 fw-semibold mb-3">Ergebnis</h3>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <p class="text-muted small fw-semibold text-uppercase mb-2">Original</p>
                                <img id="bg-original-img" class="img-fluid rounded-2 shadow-sm w-100"
                                    alt="Originalbild" style="max-height:400px; object-fit:contain;">
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted small fw-semibold text-uppercase mb-2">
                                    Ohne Hintergrund
                                </p>
                                <canvas id="bg-result-canvas" class="rounded-2 shadow-sm"
                                    style="max-height:400px; max-width:100%; object-fit:contain;"
                                    aria-label="Ergebnis: Bild ohne Hintergrund"></canvas>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button id="bg-download-btn" class="btn btn-primary btn-submit fw-semibold">
                                <i class="bi bi-download me-2" aria-hidden="true"></i>PNG herunterladen
                            </button>
                        </div>
                    </div>

                </div>
            </form>
            </div>
        </section>

        <!-- ============================================================
             SECTION 6: WASSERZEICHEN
             ============================================================ -->
        <section id="content6" class="content-section">
            <div class="container">
            <h2 class="display-6 fw-semibold mb-4">Wasserzeichen</h2>
            <?php echo $msgWatermark; ?>

            <form method="post" enctype="multipart/form-data" class="server-form">

                <!-- Live-Vorschau Canvas -->
                <div class="mb-3">
                    <div class="p-3 rounded-3 border bg-body-secondary image-preview-container" id="wm-main-preview-container">
                        <p class="fw-semibold text-secondary small text-uppercase mb-2">
                            <i class="bi bi-image me-1"></i>Vorschau
                        </p>
                        <canvas id="wm-preview-canvas" style="display:none; max-width:100%; border-radius:0.375rem;"></canvas>
                        <div class="image-name text-muted" id="wm-main-name">Keine Datei ausgewählt.</div>
                        <p class="text-muted small mt-2 mb-0 drag-hint"><i class="bi bi-upload me-1"></i>Datei auswählen oder hierher ziehen</p>
                    </div>
                </div>

                <div class="alert alert-danger shadow-sm visually-hidden alert-danger-max-file-size rounded-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Die Größe des Uploads überschreitet die maximal zulässige Grenze von 256 MB.
                </div>
                <div class="alert alert-warning shadow-sm visually-hidden alert-danger-max-file-uploads rounded-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Bitte maximal 20 Dateien auswählen.
                </div>

                <div class="form-card card shadow-sm p-4">

                    <!-- Hauptbild(er) -->
                    <div class="mb-4">
                        <label for="wm_main" class="form-label">
                            <i class="bi bi-upload me-1"></i>Hauptbild(er)
                            <span class="text-muted fw-normal">(max. 20 Dateien, max. 256 MB)</span>
                        </label>
                        <div class="input-group">
                            <input type="file" class="form-control file-input" id="wm_main" name="wm_main[]"
                                accept="image/jpeg, image/png, image/webp, image/gif, image/bmp, image/avif"
                                required multiple>
                            <button type="button" title="Auswahl löschen" class="btn btn-outline-danger clear-button" disabled>
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                            </button>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="bi bi-info-circle me-1"></i>
                            Mehrere Bilder werden alle mit demselben Wasserzeichen versehen. Die Vorschau zeigt das erste Bild.
                        </small>
                    </div>

                    <!-- Wasserzeichen-Bild -->
                    <div class="mb-4">
                        <label for="wm_logo" class="form-label">
                            <i class="bi bi-award me-1"></i>Wasserzeichen-Bild
                            <span class="text-muted fw-normal small">(PNG mit Transparenz empfohlen)</span>
                        </label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="wm_logo" name="wm_logo"
                                accept="image/jpeg, image/png, image/webp, image/gif, image/bmp, image/avif" required>
                        </div>
                    </div>

                    <!-- Position 3×3 Grid -->
                    <div class="mb-4">
                        <label class="form-label"><i class="bi bi-grid-3x3 me-1"></i>Position</label>
                        <div class="wm-position-grid">
                            <?php
                            $positions = [
                                'top-left'     => '↖', 'top-center'    => '↑', 'top-right'    => '↗',
                                'center-left'  => '←', 'center-center' => '·', 'center-right' => '→',
                                'bottom-left'  => '↙', 'bottom-center' => '↓', 'bottom-right' => '↘',
                            ];
                            foreach ($positions as $val => $label): ?>
                                <label class="wm-pos-cell <?= $val === 'bottom-right' ? 'active' : '' ?>">
                                    <input type="radio" name="wm_position" value="<?= $val ?>"
                                        <?= $val === 'bottom-right' ? 'checked' : '' ?>>
                                    <span><?= $label ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Größe des Wasserzeichens -->
                    <div class="mb-4">
                        <label for="wm-scale-slider" class="form-label">
                            <i class="bi bi-arrows-angle-expand me-1"></i>Größe des Wasserzeichens:
                            <span id="wm-scale-value" class="fw-semibold">25</span> % der Bildbreite
                        </label>
                        <input type="range" class="form-range" id="wm-scale-slider" name="wm_scale"
                            min="5" max="80" step="5" value="25">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">5 % (klein)</small>
                            <small class="text-muted">80 % (groß)</small>
                        </div>
                    </div>

                    <!-- Deckkraft -->
                    <div class="mb-4">
                        <label for="wm-opacity-slider" class="form-label">
                            <i class="bi bi-circle-half me-1"></i>Deckkraft:
                            <span id="wm-opacity-value" class="fw-semibold">80</span> %
                        </label>
                        <input type="range" class="form-range" id="wm-opacity-slider" name="wm_opacity"
                            min="10" max="100" step="5" value="80">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">10 % (transparent)</small>
                            <small class="text-muted">100 % (deckend)</small>
                        </div>
                    </div>

                    <!-- Randabstand -->
                    <div class="mb-4">
                        <label for="wm_margin" class="form-label">
                            <i class="bi bi-border-outer me-1"></i>Randabstand
                        </label>
                        <div class="input-group" style="max-width:200px;">
                            <input type="number" class="form-control" id="wm_margin" name="wm_margin"
                                min="0" max="500" value="20" placeholder="20">
                            <span class="input-group-text text-muted">px</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-lg btn-primary btn-submit fw-semibold" disabled>
                        <i class="bi bi-badge-tm me-2" aria-hidden="true"></i>Wasserzeichen hinzufügen
                    </button>
                </div>
            </form>
            </div>
        </section>

        <!-- ==========================================
             ÜBER / ABOUT
             ========================================== -->
        <section id="content5" class="content-section">
            <div class="container py-4">
                <div class="form-card card shadow-sm p-4 mb-4">
                    <h2 class="fs-4 fw-bold mb-1">Was ist ImageTool?</h2>
                    <p class="text-muted mb-4">Ein schlankes Werkzeug zur Bildbearbeitung direkt im Browser. Ohne Account, ohne Abo, ohne Tracking.</p>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <i class="bi bi-crop fs-3 text-primary flex-shrink-0"></i>
                                <div>
                                    <h3 class="fs-6 fw-semibold mb-1">Zuschneiden</h3>
                                    <p class="text-muted small mb-0">Pixelgenaues Zuschneiden per Zahleneingabe oder interaktiv im Vorschaubild. 4 Kanten- und 4 Ecken-Handles, Aspect Ratio Lock mit 8 Voreinstellungen (1:1, 4:3, 16:9 u.a.), Crop-Bereich per Drag verschieben.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <i class="bi bi-arrows-collapse-vertical fs-3 text-primary flex-shrink-0"></i>
                                <div>
                                    <h3 class="fs-6 fw-semibold mb-1">Skalieren</h3>
                                    <p class="text-muted small mb-0">Bis zu vier skalierte Versionen in einem Schritt, ideal für responsive Bildsets.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <i class="bi bi-file-earmark-image fs-3 text-primary flex-shrink-0"></i>
                                <div>
                                    <h3 class="fs-6 fw-semibold mb-1">Konvertieren</h3>
                                    <p class="text-muted small mb-0">Mehrere Bilder gleichzeitig in WebP oder JPEG konvertieren, mit einstellbarer Qualität und ZIP-Download.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <i class="bi bi-scissors fs-3 text-primary flex-shrink-0"></i>
                                <div>
                                    <h3 class="fs-6 fw-semibold mb-1">Hintergrund entfernen</h3>
                                    <p class="text-muted small mb-0">KI-gestützte Hintergrundentfernung vollständig im Browser. Das Bild wird nicht an einen Server übertragen. Das Modell wird einmalig geladen und danach im Browser gespeichert.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <i class="bi bi-badge-tm fs-3 text-primary flex-shrink-0"></i>
                                <div>
                                    <h3 class="fs-6 fw-semibold mb-1">Wasserzeichen</h3>
                                    <p class="text-muted small mb-0">Wasserzeichen-Bild auf bis zu 20 Hauptbilder gleichzeitig anwenden, mit wählbarer Position (3x3-Grid), Größe, Deckkraft und Randabstand. Live-Vorschau im Browser, ZIP-Download bei Mehrfach-Upload.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-card card shadow-sm p-4 mb-4">
                    <h2 class="fs-4 fw-bold mb-3">Unterstützte Formate</h2>
                    <p class="text-muted mb-2">Folgende Bildformate werden als Eingabe akzeptiert:</p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach (['JPEG', 'PNG', 'WebP', 'GIF', 'BMP', 'AVIF'] as $fmt): ?>
                            <span class="badge text-bg-secondary fs-6 fw-normal"><?= $fmt ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-card card shadow-sm p-4">
                    <h2 class="fs-4 fw-bold mb-3">Datenschutz & Datenspeicherung</h2>
                    <div class="d-flex gap-3 align-items-start">
                        <i class="bi bi-shield-check fs-3 text-success flex-shrink-0"></i>
                        <div>
                            <p class="mb-2">Hochgeladene Bilder werden ausschließlich zur Verarbeitung verwendet und <strong>automatisch nach einer Stunde</strong> vom Server gelöscht.</p>
                            <p class="mb-2">Die <strong>Hintergrundentfernung</strong> läuft vollständig im Browser. Das Bild wird nicht an einen Server übertragen.</p>
                            <p class="mb-0 text-muted small">Es werden keine personenbezogenen Daten gespeichert oder weitergegeben.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer class="py-3">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
            <p class="mb-0 fw-semibold">
                <i class="bi bi-c-circle me-1"></i><?php echo date('Y'); ?>
                <a href="https://andreas-web.dev/" class="text-body text-decoration-none ms-1">Andreas Lesovsky</a>
            </p>
            <ul class="social-links fs-4 d-flex gap-3 mb-0">
                <li>
                    <a href="https://www.linkedin.com/in/andreas-lesovsky-98a464306/" target="_blank"
                        rel="noopener" aria-label="LinkedIn Profil">
                        <i class="bi bi-linkedin" aria-hidden="true"></i>
                    </a>
                </li>
                <li>
                    <a href="https://github.com/AndreasLesovsky/imagetool" target="_blank"
                        rel="noopener" aria-label="GitHub Profil">
                        <i class="bi bi-github" aria-hidden="true"></i>
                    </a>
                </li>
            </ul>
        </div>
    </footer>

    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script type="module" src="js/bg-removal.js"></script>
</body>

</html>
