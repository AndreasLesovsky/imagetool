<?php
if (isset($_GET['unique_id']) && isset($_GET['original_name']) && isset($_GET['edit_type'])) {
    $uniqueId = basename($_GET['unique_id']);
    $originalName = basename($_GET['original_name']);
    $editType = $_GET['edit_type'];

    // Bestimmen des Verzeichnispfads und des ZIP-Dateinamens je nachdem, ob der User skaliert oder konvertiert hat
    switch ($editType) {
        case 'scale':
            $dir = "./bilder/$uniqueId/{$originalName}_scaled_sizes";
            $zipFileName = "{$originalName}_scaled_sizes.zip";
            break;

        case 'webp':
            $dir = "./bilder/$uniqueId/webp_images";
            $zipFileName = "{$uniqueId}_webp_images.zip";
            break;

        default:
            echo "Ungültiger Bearbeitungstyp!";
            exit;
    }

    if (is_dir($dir)) {
        $zip = new ZipArchive();

        // Temporäre Datei zum Erstellen des ZIP-Archivs
        $tempZipFile = tempnam(sys_get_temp_dir(), 'zip');
        if ($zip->open($tempZipFile, ZipArchive::CREATE) !== TRUE) {
            exit("ZIP-Archiv konnte nicht erstellt werden.");
        }

        // Dateien im Verzeichnis durchlaufen und zum ZIP-Archiv hinzufügen
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();

                if ($editType == 'scale') {
                    $relativePath = substr($filePath, strpos($filePath, "{$originalName}_scaled_sizes"));
                } else {
                    $relativePath = basename($filePath);
                }

                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        // ZIP-Datei zum Download vorbereiten
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($tempZipFile));

        readfile($tempZipFile);

        unlink($tempZipFile);

        exit;
    } else {
        echo "Der angeforderte Ordner existiert nicht.";
    }
} else {
    echo "Unzureichende Parameter.";
}
?>