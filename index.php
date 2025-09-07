<?php
require("includes/config.inc.php");
require("includes/common.inc.php");
require("includes/pictures.inc.php"); // enthält Funktion scaleImage

$whitelist = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
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

                    $msgCrop .= "<div class='alert alert-success container shadow'>
                                    <h3 class='fs-4'>Zugeschnittenes Bild:</h3>
                                    <img src='$croppedPath' alt='Zugeschnittenes Bild' class='img-fluid'><br>
                                    <p class='my-2'>Bild erfolgreich zugeschnitten!</p>
                                    <a href='$croppedPath' download class='btn btn-primary fw-semibold'>
                                        <i class='bi bi-download'></i>
                                        Bild herunterladen
                                    </a>
                                </div>";
                } else {
                    $msgCrop .= "<div class='alert alert-danger container shadow'>
                                    Bild konnte nicht zugeschnitten werden. Bitte geben Sie gültige Werte ein, die innerhalb der zulässigen Grenzen sind.
                                </div>";
                }

                imagedestroy($image);
            }
        } else {
            $msgCrop = "<div class='alert alert-danger container shadow'>
                            Ungültiges Dateiformat. Erlaubt sind nur JPEG, PNG, WebP, GIF und BMP Dateien.
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
                scaleImage($originalPath, $size); // Funktion wird inkludiert
            }
        }

        $msgScale .= "<div class='alert alert-success container shadow'>
                        Bild wurde erfolgreich skaliert!<br>
                        <a href='download.php?unique_id=" . urlencode($uniqueId) . "&original_name=" . urlencode($originalName) . "&edit_type=" . urlencode($editType) . "' class='btn btn-primary fw-semibold mt-2'>
                            <i class='bi bi-download'></i>
                            Bilder herunterladen
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

                    // Überprüfen, ob das PNG eine reduzierte Farbtiefe hat (8-Bit oder weniger)
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
                }

                if ($image) {
                    imagewebp($image, $outputPath, 80); // Qualität 80
                    imagedestroy($image);
                }
            } else {
                $msgwebpConvert .= "<div class='alert alert-danger container shadow'>
                                        Ungültiges Dateiformat. Erlaubt sind nur JPEG, PNG, WebP, GIF und BMP Dateien.
                                    </div>";
            }
        }

        if (count($_FILES['webpConvert']['name']) === 1) {
            $msgwebpConvert .= "<div class='alert alert-success container shadow'>
                                    Bild wurde erfolgreich konvertiert!<br>
                                    <a href='$outputPath' download class='btn btn-primary fw-semibold mt-2'>
                                        <i class='bi bi-download'></i>
                                        Bild herunterladen
                                    </a>
                                </div>";
        }

        if (count($_FILES['webpConvert']['name']) > 1) {
            $msgwebpConvert .= "<div class='alert alert-success container shadow'>
                                    Bilder wurden erfolgreich konvertiert!<br>
                                    <a href='download.php?unique_id=" . urlencode($uniqueId) . "&original_name=" . urlencode($originalName) . "&edit_type=" . urlencode($editType) . "' class='btn btn-primary fw-semibold mt-2'>
                                        <i class='bi bi-download'></i>
                                        Bilder herunterladen
                                    </a>
                                </div>";
        }
    } else {
        $msgwebpConvert .= "<div class='alert alert-danger container shadow'>
                                Bitte wählen Sie mindestens ein Bild aus.
                            </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

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
    <header class="bg-body-secondary shadow py-4">
        <div class="container d-flex flex-column flex-lg-row justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="fs-2 fw-bold lh-1 text-dark text-nowrap rounded-1 bg-secondary px-3 py-2 mb-0 col-12 col-lg-auto">
                <i class="bi bi-images"></i>
                ImageTool
            </h1>

            <nav class="col-12 col-lg-auto">
                <div class="d-flex flex-column flex-lg-row gap-1" role="group" aria-label="Content Switcher">
                    <button type="button" id="theme-toggler" class="btn btn-sm btn-outline-secondary text-body fw-semibold d-inline-flex align-items-center flex-nowrap order-lg-last">
                        <i class="bi bi-sun-fill theme-icon sun-icon me-2"></i>
                        <i class="bi bi-moon-fill theme-icon moon-icon visually-hidden me-2"></i>
                        <span id="theme-text">Dunkler Modus</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold d-inline-flex align-items-center flex-nowrap btn-primary-active" data-target="content1">
                        <i class="bi bi-crop me-2"></i>
                        Zuschneiden
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold d-inline-flex align-items-center flex-nowrap" data-target="content2">
                        <i class="bi bi-arrows-collapse-vertical me-2"></i>
                        Skalieren
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold d-inline-flex align-items-center flex-nowrap text-nowrap" data-target="content3">
                        <i class="bi bi-file-earmark-image me-2"></i>
                        WebP Konvertierung
                    </button>
                </div>
            </nav>
        </div>
    </header>

    <main class="my-5">
        <section id="content1" class="content-section active">
            <h2 class="display-6 fw-semibold container">Zuschneiden</h2>
            <?php echo $msgCrop; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="text-primary-emphasis bg-primary-subtle border border-primary-subtle container shadow p-3 mb-3 image-preview-container">
                    <h3 class="fs-4">Hochgeladen:</h3>
                    <img class="image-preview img-fluid" src="" alt="Image Preview" style="display:none;">
                    <div class="image-name">Keine Datei ausgewählt.</div>
                </div>
                <div class="alert alert-danger container alert-danger-max-file-size visually-hidden">
                    Die Größe des Uploads überschreitet die maximal zulässige Grenze von 50MB.
                </div>
                <div class="alert alert-warning container alert-danger-max-file-uploads visually-hidden">
                    Bitte maximal 20 Dateien auswählen.
                </div>
                <div class="container border bg-body-tertiary shadow p-3">
                    <div class="mb-3">
                        <label for="cropImage" class="form-label">Bild zum Zuschneiden (maximal 50 MB):</label>
                        <div class="input-group">
                            <input type="file" class="form-control file-input" name="cropImage" accept="image/jpeg, image/png, image/webp, image/gif, image/bmp" required>
                            <button type="button" title="Auswahl löschen" class="btn btn-danger clear-button"><i class="bi bi-trash3"></i></button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label mb-0"><i class="bi bi-arrow-bar-right me-2"></i>Von links wegschneiden</label>
                            <input type="number" name="left" class="form-control" placeholder="px" aria-label="Von links wegschneiden">
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label mb-0"><i class="bi bi-arrow-bar-down me-2"></i>Von oben wegschneiden</label>
                            <input type="number" name="top" class="form-control" placeholder="px" aria-label="Von oben wegschneiden">
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label mb-0"><i class="bi bi-arrow-bar-left me-2"></i>Von rechts wegschneiden</label>
                            <input type="number" name="right" class="form-control" placeholder="px" aria-label="Von rechts wegschneiden">
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label mb-0"><i class="bi bi-arrow-bar-up me-2"></i>Von unten wegschneiden</label>
                            <input type="number" name="bottom" class="form-control" placeholder="px" aria-label="Von unten wegschneiden">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-lg btn-primary fw-semibold mt-2">
                        <i class="bi bi-crop me-1"></i> Zuschneiden
                    </button>
                </div>
            </form>
        </section>

        <section id="content2" class="content-section">
            <h2 class="display-6 fw-semibold container">Skalieren</h2>
            <?php echo $msgScale; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="text-primary-emphasis bg-primary-subtle border border-primary-subtle container shadow p-3 mb-3 image-preview-container">
                    <h3 class="fs-4">Hochgeladen:</h3>
                    <img class="image-preview img-fluid" src="" alt="Image Preview" style="display:none;">
                    <div class="image-name">Keine Datei ausgewählt.</div>
                </div>
                <div class="alert alert-danger container alert-danger-max-file-size visually-hidden">
                    Die Größe des Uploads überschreitet die maximal zulässige Grenze von 50MB.
                </div>
                <div class="alert alert-warning container alert-danger-max-file-uploads visually-hidden">
                    Bitte maximal 20 Dateien auswählen.
                </div>
                <div class="container border bg-body-tertiary shadow p-3">
                    <div class="mb-3">
                        <label for="scaleImage" class="form-label">Bild zum Skalieren (maximal 50 MB):</label>
                        <div class="input-group">
                            <input type="file" class="form-control file-input" name="scaleImage" accept="image/jpeg, image/png, image/webp, image/gif, image/bmp" required>
                            <button type="button" title="Auswahl löschen" class="btn btn-danger clear-button"><i class="bi bi-trash3"></i></button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label mb-0"><i class="bi bi-1-circle me-2"></i>Größe 1</label>
                            <input type="number" name="size_1" class="form-control" placeholder="px">
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label mb-0"><i class="bi bi-2-circle me-2"></i>Größe 2</label>
                            <input type="number" name="size_2" class="form-control" placeholder="px">
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label mb-0"><i class="bi bi-3-circle me-2"></i>Größe 3</label>
                            <input type="number" name="size_3" class="form-control" placeholder="px">
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label mb-0"><i class="bi bi-4-circle me-2"></i>Größe 4</label>
                            <input type="number" name="size_4" class="form-control" placeholder="px">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-lg btn-primary fw-semibold mt-2">
                        <i class="bi bi-arrows-collapse-vertical me-1"></i>
                        Skalieren
                    </button>
                </div>
            </form>
        </section>

        <section id="content3" class="content-section">
            <h2 class="display-6 fw-semibold container">WebP Konvertierung</h2>
            <?php echo $msgwebpConvert; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="text-primary-emphasis bg-primary-subtle border border-primary-subtle container shadow p-3 mb-3 image-preview-container">
                    <h3 class="fs-4">Hochgeladen:</h3>
                    <img class="image-preview img-fluid" src="" alt="Image Preview" style="display:none;">
                    <div class="image-name">Keine Datei(en) ausgewählt.</div>
                </div>
                <div class="alert alert-danger container alert-danger-max-file-size visually-hidden">
                    Die Größe des Uploads überschreitet die maximal zulässige Grenze von 50MB.
                </div>
                <div class="alert alert-warning container alert-danger-max-file-uploads visually-hidden">
                    Bitte maximal 20 Dateien auswählen.
                </div>
                <div class="container border bg-body-tertiary shadow p-3">
                    <div class="mb-3">
                        <label for="webpConvert" class="form-label">Bild(er) zum Konvertieren (maximal 20 Dateien, maximal 50 MB):</label>
                        <div class="input-group">
                            <input type="file" class="form-control file-input" name="webpConvert[]" accept="image/jpeg, image/png, image/webp, image/gif, image/bmp" required multiple>
                            <button type="button" title="Auswahl löschen" class="btn btn-danger clear-button"><i class="bi bi-trash3"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-lg btn-primary fw-semibold mt-2">
                        <i class="bi bi-file-earmark-image me-1"></i>
                        In WebP konvertieren
                    </button>
                </div>
            </form>
        </section>
    </main>

    <footer class="text-white py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <p class="mb-0 fw-bold"><i class="bi bi-c-circle text-secondary me-1"></i>2025 <a href="https://andreas-web.dev/" class="text-white">Andreas Lesovsky</a></p>
            <ul class="social-links fs-4 d-flex gap-3 mb-0">
                <li>
                    <a href="https://www.linkedin.com/in/andreas-lesovsky-98a464306/" target="_blank" aria-label="LinkedIn Profil">
                        <i class="bi bi-linkedin text-primary" aria-hidden="true"></i>
                    </a>
                </li>
                <li>
                    <a href="https://github.com/AndreasLesovsky/web-image-optimizer" target="_blank" aria-label="GitHub Profil">
                        <i class="bi bi-github text-primary" aria-hidden="true"></i>
                    </a>
                </li>
            </ul>
        </div>
    </footer>

    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>

</html>