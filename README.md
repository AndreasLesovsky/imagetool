# ImageTool
![License](https://img.shields.io/badge/license-MIT-green)

ImageTool ist eine Webanwendung für clientseitige und serverseitige Bildbearbeitung direkt im Browser. Sie bietet **Zuschneiden**, **Skalierung**, **Konvertierung**, **Wasserzeichen** und **KI-gestützte Hintergrundentfernung** — ohne externe APIs oder Accountpflicht.

---

## Inhaltsverzeichnis

* [Technologien](#technologien)
* [Features](#features)
* [Installation & Setup](#installation--setup)
* [Demo](#demo)
* [Lizenz](#lizenz)

---

## Technologien

* PHP 8 mit GD-Bibliothek
* HTML5 / CSS3 / SCSS
* Bootstrap 5.3 (custom compiled)
* JavaScript (ES6, Vanilla)
* [Transformers.js](https://github.com/huggingface/transformers.js) — `@huggingface/transformers@3` via CDN
* KI-Modell: [briaai/RMBG-1.4](https://huggingface.co/briaai/RMBG-1.4) (Apache 2.0)

---

## Features

### Bildbearbeitung (serverseitig, PHP/GD)

* **Zuschneiden** — interaktive Canvas-Vorschau mit 4 Kanten- und 4 Ecken-Handles, Aspect Ratio Lock (8 Voreinstellungen: 1:1, 4:3, 3:4, 16:9, 9:16, 3:2, 2:3, 2:1), Drag-to-Move des Crop-Bereichs, Pixeleingabe
* **Skalieren** — bis zu 4 Zielgrößen gleichzeitig
* **Konvertieren** — WebP oder JPEG, einstellbare Qualität (50–100 %), Mehrfach-Upload, ZIP-Download
* **Wasserzeichen** — bis zu 20 Hauptbilder gleichzeitig, Wasserzeichen-Bild, Position (3x3-Grid), Größe, Deckkraft, Randabstand, Live-Vorschau im Browser, ZIP-Download bei Mehrfach-Upload
* Unterstützte Formate: JPEG, PNG, WebP, GIF, BMP, AVIF

### Hintergrundentfernung (clientseitig, KI)

* Läuft vollständig im Browser — das Bild wird nicht an einen Server übertragen
* Modell: `briaai/RMBG-1.4` (~168 MB, nach erstem Download im Browser gespeichert)
* Ergebnis als PNG mit transparentem Hintergrund downloadbar
* Vorher/Nachher-Anzeige

### Speicherung & Export

* Serverseitig temporäre Speicherung (1h), automatische Löschung per Cronjob
* Mehrere Bilder gebündelt als ZIP-Download

### UI & UX

* Glassmorphism-Header, animierte Gradient-Buttons
* Dark/Light Mode (persistiert via localStorage)
* Responsive Layout mit Bootstrap 5
* Drag & Drop in alle Upload-Bereiche
* Upload-Vorschau mit Dateiname, Abmessungen und Dateigröße
* Semantisches HTML, ARIA-Labels

---

## Installation & Setup

1. Repository klonen:
   ```bash
   git clone https://github.com/AndreasLesovsky/imagetool.git
   ```

2. Node.js-Abhängigkeiten installieren und SCSS kompilieren:
   ```bash
   npm install
   npm run sass-build
   ```

3. Die **GD-Bibliothek** muss in der `php.ini` aktiviert sein (`extension=gd`).

4. Webserver auf das Projektverzeichnis zeigen lassen (z.B. XAMPP, Apache).

> Kein Composer nötig — das Projekt hat keine PHP-Abhängigkeiten.

---

## Demo

Live-Demo: [https://imagetool.andreas-web.dev](https://imagetool.andreas-web.dev)

---

## Lizenz

Dieses Projekt steht unter der **MIT Lizenz**.
Siehe [LICENSE.md](LICENSE.md) für Details.
