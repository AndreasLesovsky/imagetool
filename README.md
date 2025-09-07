# ImageTool
![License](https://img.shields.io/badge/license-MIT-green)

ImageTool ist eine Webanwendung mit **PHP/GD**, die **Bilder-Upload**, **Zuschneiden**, **Skalierung in mehrere Zielgrößen** und **Konvertierung ins WebP-Format** bietet. Bearbeitete Dateien werden für eine Stunde auf dem Server gespeichert und durch einen **Cronjob** automatisch gelöscht. Mehrere Bilder können als **ZIP-Datei** gebündelt heruntergeladen werden. Die Anwendung verfügt über eine **responsive UI** mit Upload-Vorschau, Dark/Light Mode und grundlegender Barrierefreiheit.

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
* Bootstrap 5  
* JavaScript (ES6)  

---

## Features

### Bildbearbeitung

* Upload mit Vorschau (inkl. Dateiname und Abmessungen)  
* Zuschneiden an allen vier Seiten  
* Skalierung in mehrere Zielgrößen  
* Konvertierung ins WebP-Format  

### Speicherung & Export

* Temporäre Speicherung (1h), automatische Löschung per Cronjob  
* Mehrere Bilder gebündelt als ZIP-Download  

### UI & UX

* Responsive UI mit Bootstrap 5  
* Dark/Light Mode (persistiert via localStorage)  
* Semantisches HTML, hohe Kontraste, ARIA-Labels  

---

## Installation

1. Repository klonen:
   ```bash
   git clone https://github.com/AndreasLesovsky/imagetool.git
   ```

2. PHP-Abhängigkeiten installieren:
   ```bash
   composer install
   ```

3. Node.js-Abhängigkeiten installieren:
   ```bash
   npm install
   ```

4. Die **GD-Bibliothek** muss in der php.ini auf dem Webserver aktiviert sein.

---

## Demo

Live-Demo: [https://imagetool.andreas-web.dev](https://imagetool.andreas-web.dev)

---

## Lizenz

Dieses Projekt steht unter der **MIT Lizenz**.  
Siehe [LICENSE.md](LICENSE.md) für Details.