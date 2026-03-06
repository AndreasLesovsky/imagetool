<?php
require("includes/config.inc.php");
require("includes/common.inc.php");
require("includes/pictures.inc.php"); // enthält Funktion scaleImage

$whitelist = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/avif'];
$msgCrop = "";
$msgScale = "";
$msgwebpConvert = "";

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
                                    Bild konnte nicht zugeschnitten werden. Bitte gib gültige Werte ein, die innerhalb der zulässigen Grenzen liegen.
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

// WebP Konvertierung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['webpConvert'])) {
    if (count($_FILES['webpConvert']['name']) > 0) {
        $uniqueId = uniqid();
        $editType = 'webp';

        $dir = "./bilder/$uniqueId/webp_images/";
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ($_FILES['webpConvert']['name'] as $key => $originalName) {
            $tempName = $_FILES['webpConvert']['tmp_name'][$key];
            $fileType = mime_content_type($tempName);

            if (in_array($fileType, $whitelist)) {
                $filename = pathinfo($originalName, PATHINFO_FILENAME);
                $outputPath = $dir . $filename . '.webp';

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
                    imagewebp($image, $outputPath, 80);
                    imagedestroy($image);
                }
            } else {
                $msgwebpConvert .= "<div class='alert alert-danger container shadow-sm rounded-3'>
                                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                                        Ungültiges Dateiformat. Erlaubt sind nur JPEG, PNG, WebP, GIF, BMP und AVIF Dateien.
                                    </div>";
            }
        }

        if (count($_FILES['webpConvert']['name']) === 1) {
            $msgwebpConvert .= "<div class='alert alert-success container shadow-sm rounded-3'>
                                    <i class='bi bi-check-circle-fill me-2'></i>
                                    Bild wurde erfolgreich konvertiert!<br>
                                    <a href='$outputPath' download class='btn btn-primary btn-submit fw-semibold mt-2'>
                                        <i class='bi bi-download me-1'></i>Bild herunterladen
                                    </a>
                                </div>";
        }

        if (count($_FILES['webpConvert']['name']) > 1) {
            $msgwebpConvert .= "<div class='alert alert-success container shadow-sm rounded-3'>
                                    <i class='bi bi-check-circle-fill me-2'></i>
                                    Bilder wurden erfolgreich konvertiert!<br>
                                    <a href='download.php?unique_id=" . urlencode($uniqueId) . "&original_name=" . urlencode($originalName) . "&edit_type=" . urlencode($editType) . "' class='btn btn-primary btn-submit fw-semibold mt-2'>
                                        <i class='bi bi-download me-1'></i>Bilder herunterladen
                                    </a>
                                </div>";
        }
    } else {
        $msgwebpConvert .= "<div class='alert alert-danger container shadow-sm rounded-3'>
                                <i class='bi bi-exclamation-triangle-fill me-2'></i>
                                Bitte wähle mindestens ein Bild aus.
                            </div>";
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
                        <span class="nav-label">WebP</span>
                    </button>
                    <button type="button"
                        class="btn btn-sm btn-outline-primary fw-semibold"
                        data-target="content4">
                        <i class="bi bi-scissors" aria-hidden="true"></i>
                        <span class="nav-label">Hintergrund</span>
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
                    <div class="p-3 rounded-3 border bg-body-secondary">
                        <p class="fw-semibold text-secondary small text-uppercase mb-2 tracking-wide">
                            <i class="bi bi-image me-1"></i>Vorschau
                        </p>

                        <!-- Canvas + Drag Handles (hidden until image loaded) -->
                        <div class="crop-handle-wrapper" style="display:none;">
                            <canvas id="crop-canvas-full" aria-label="Crop-Vorschau"></canvas>
                            <div class="crop-handle crop-handle-left"   data-side="left"   title="Links ziehen"></div>
                            <div class="crop-handle crop-handle-top"    data-side="top"    title="Oben ziehen"></div>
                            <div class="crop-handle crop-handle-right"  data-side="right"  title="Rechts ziehen"></div>
                            <div class="crop-handle crop-handle-bottom" data-side="bottom" title="Unten ziehen"></div>
                        </div>

                        <div class="image-name text-muted">Keine Datei ausgewählt.</div>
                        <p id="crop-dimensions" class="text-muted small mb-0 mt-1"></p>
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
                            <input type="file" class="form-control" id="cropImageInput" name="cropImage"
                                accept="image/jpeg, image/png, image/webp, image/gif, image/bmp, image/avif" required>
                            <button type="button" id="crop-clear-btn" title="Auswahl löschen"
                                class="btn btn-outline-danger" disabled>
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                            </button>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="bi bi-info-circle me-1"></i>
                            Du kannst die Handles im Vorschaubild ziehen oder Pixelwerte direkt eingeben.
                        </small>
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

                    <button type="submit" class="btn btn-lg btn-primary btn-submit fw-semibold">
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

                    <button type="submit" class="btn btn-lg btn-primary btn-submit fw-semibold">
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
            <h2 class="display-6 fw-semibold mb-4">WebP Konvertierung</h2>
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
                            Ausgabequalität: 80 % WebP. Mehrere Bilder werden als ZIP-Datei gebündelt.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-lg btn-primary btn-submit fw-semibold">
                        <i class="bi bi-file-earmark-image me-2" aria-hidden="true"></i>In WebP konvertieren
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
                <div class="form-card card shadow-sm p-4">

                    <!-- Info Badge -->
                    <p class="text-muted small mb-4">
                        <i class="bi bi-cpu me-1 text-primary"></i>
                        KI läuft direkt im Browser &mdash; kein Upload, kein Server, kein API-Key.
                        Modell: <code>briaai/RMBG-1.4</code>
                    </p>

                    <!-- Image Preview -->
                    <div class="p-3 rounded-3 border bg-body-secondary image-preview-container mb-3">
                        <img id="bg-preview-img" class="image-preview" style="display:none; max-width:100%; border-radius:0.375rem;" alt="Vorschau">
                        <span id="bg-file-name" class="image-name text-muted small">Keine Datei ausgewählt.</span>
                    </div>

                    <!-- File Input -->
                    <div class="mb-4">
                        <label for="bg-file-input" class="form-label">
                            <i class="bi bi-upload me-1"></i>Bild auswählen
                        </label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="bg-file-input"
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
                                    <p class="text-muted small mb-0">Schneide Bilder pixelgenau zu, per Zahleneingabe oder durch Ziehen der Handles direkt im Vorschaubild.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <i class="bi bi-arrows-collapse-vertical fs-3 text-primary flex-shrink-0"></i>
                                <div>
                                    <h3 class="fs-6 fw-semibold mb-1">Skalieren</h3>
                                    <p class="text-muted small mb-0">Erstelle bis zu vier skalierte Versionen eines Bildes in einem Schritt, ideal für responsive Bildsets.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <i class="bi bi-file-earmark-image fs-3 text-primary flex-shrink-0"></i>
                                <div>
                                    <h3 class="fs-6 fw-semibold mb-1">WebP-Konvertierung</h3>
                                    <p class="text-muted small mb-0">Konvertiere mehrere Bilder gleichzeitig ins WebP-Format und lade sie als ZIP-Archiv herunter.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <i class="bi bi-scissors fs-3 text-primary flex-shrink-0"></i>
                                <div>
                                    <h3 class="fs-6 fw-semibold mb-1">Hintergrund entfernen</h3>
                                    <p class="text-muted small mb-0">KI-gestützte Hintergrundentfernung vollständig im Browser. Kein Upload auf externe Server, kein API-Key nötig. Das Modell wird einmalig geladen und danach im Browser gecacht.</p>
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
                            <p class="mb-2">Die <strong>Hintergrundentfernung</strong> findet vollständig in deinem Browser statt. Dein Bild verlässt dabei deinen Computer nicht.</p>
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
