// Event Listener für Nav Buttons
document.querySelectorAll('nav .btn-outline-primary').forEach(button => {
    button.addEventListener('click', function () {
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });

        const targetId = this.getAttribute('data-target');
        document.getElementById(targetId).classList.add('active');

        document.querySelectorAll('nav .btn').forEach(btn => {
            btn.classList.remove('btn-primary-active');
        });

        this.classList.add('btn-primary-active');

        localStorage.setItem('activeSection', targetId);
    });
});

window.addEventListener('load', () => {
    const savedSection = localStorage.getItem('activeSection');
    if (savedSection) {
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });

        const targetSection = document.getElementById(savedSection);
        if (targetSection) {
            targetSection.classList.add('active');
        }

        document.querySelectorAll('nav .btn-outline-primary').forEach(btn => {
            btn.classList.remove('btn-primary-active');
            if (btn.getAttribute('data-target') === savedSection) {
                btn.classList.add('btn-primary-active');
            }
        });
    }
});

// Darkmode
const themeToggler = document.getElementById('theme-toggler');
const sunIcon = document.querySelector('.sun-icon');
const moonIcon = document.querySelector('.moon-icon');
const themeText = document.getElementById('theme-text');
const htmlElement = document.documentElement;

function setTheme(theme) {
    // Setze das data-bs-theme Attribut
    htmlElement.setAttribute('data-bs-theme', theme);

    // Icons und Text aktualisieren
    if (theme === 'dark') {
        sunIcon.classList.add('visually-hidden');
        moonIcon.classList.remove('visually-hidden');
        themeText.textContent = 'Heller Modus';
    } else {
        sunIcon.classList.remove('visually-hidden');
        moonIcon.classList.add('visually-hidden');
        themeText.textContent = 'Dunkler Modus';
    }

    localStorage.setItem('theme', theme);
}

// Überprüfen, ob der Benutzer eine bevorzugte Einstellung hat
const storedTheme = localStorage.getItem('theme');
if (storedTheme) {
    setTheme(storedTheme);
} else {
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (systemPrefersDark) {
        setTheme('dark');
    } else {
        setTheme('light');
    }
}

// Event Listener für den Button zum Umschalten des Darkmode
themeToggler.addEventListener('click', () => {
    const currentTheme = htmlElement.getAttribute('data-bs-theme');

    if (currentTheme === 'light') {
        setTheme('dark');
    } else {
        setTheme('light');
    }
});

// Handler für die Bildvorschau aller File Inputs
document.querySelectorAll(".file-input").forEach(input => {
    input.addEventListener("change", function (event) {
        const files = event.target.files;
        const form = this.closest("form");
        const previewContainer = form.querySelector(".image-preview-container");
        const preview = previewContainer.querySelector(".image-preview");
        const fileNameContainer = previewContainer.querySelector(".image-name");

        // Container für die Fehlermeldung
        const errorContainerFileSize = form.querySelector(".alert-danger-max-file-size");
        const errorContainerMaxFileUploads = form.querySelector(".alert-danger-max-file-uploads");
        const submitButton = form.querySelector('button[type="submit"]');

        fileNameContainer.textContent = '';
        fileNameContainer.innerHTML = '';

        let totalSize = 0;

        // Funktion zum Formatieren der Dateigröße in MB
        const formatFileSize = (size) => {
            if (size >= 1024 * 1024) {
                return (size / (1024 * 1024)).toFixed(2) + ' MB';
            } else if (size >= 1024) {
                return (size / 1024).toFixed(2) + ' KB';
            } else {
                return size + ' Bytes';
            }
        };

        // Überprüfe, ob mehr als 20 Dateien ausgewählt wurden
        if (files.length > 20) {
            errorContainerMaxFileUploads.classList.remove("visually-hidden");
            submitButton.disabled = true;
        } else {
            errorContainerMaxFileUploads.classList.add("visually-hidden");
            submitButton.disabled = false;
        }

        // Wenn mehr als ein Bild hochgeladen wird
        if (files.length > 1) {
            preview.style.display = "none";

            const filesList = document.createElement('ol');
            fileNameContainer.appendChild(filesList);


            const sizeParagraph = document.createElement('p');
            sizeParagraph.classList.add('total-size-paragraph');
            fileNameContainer.insertBefore(sizeParagraph, filesList);

            Array.from(files).forEach(file => {
                totalSize += file.size;

                const listItem = document.createElement('li');
                const fileNameText = file.name;
                const fileSizeText = formatFileSize(file.size);
                const reader = new FileReader();

                reader.onload = function (e) {
                    const img = new Image();
                    img.onload = function () {
                        const width = img.width;
                        const height = img.height;
                        listItem.textContent = `${fileNameText} (${width}x${height}px, ${fileSizeText})`;
                        filesList.appendChild(listItem);

                        sizeParagraph.textContent = `Gesamtgröße: ${formatFileSize(totalSize)}`;
                    };
                    img.src = e.target.result;
                };

                reader.readAsDataURL(file);
            });
        } else if (files.length === 1) {
            const file = files[0];
            totalSize += file.size;

            const fileNameText = file.name;
            const fileSizeText = formatFileSize(file.size);
            const reader = new FileReader();

            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = "block";

                const img = new Image();
                img.onload = function () {
                    const width = img.width;
                    const height = img.height;
                    fileNameContainer.textContent = `${fileNameText} (${width}x${height}px, ${fileSizeText})`;
                };
                img.src = e.target.result;
            };

            reader.readAsDataURL(file);
        } else {
            fileNameContainer.textContent = "Keine Datei ausgewählt";
            preview.style.display = "none";
        }

        // Überprüfe die Gesamtgröße der Dateien
        if (totalSize > 50 * 1024 * 1024) {  // 50MB
            errorContainerFileSize.classList.remove("visually-hidden");
            submitButton.disabled = true;
        } else {
            errorContainerFileSize.classList.add("visually-hidden");
            submitButton.disabled = false;
        }

        // Wenn eine der Bedingungen für maximale Dateigröße oder Anzahl überschritten ist, verhindere das Absenden des Formulars
        if (totalSize > 50 * 1024 * 1024 || files.length > 20) {
            event.preventDefault();
        }
    });
});

// Handler für alle Clear-Buttons
document.querySelectorAll(".clear-button").forEach(button => {
    button.addEventListener("click", function () {
        const form = this.closest("form");

        const fileInput = form.querySelector(".file-input");
        fileInput.value = "";

        const previewContainer = form.querySelector(".image-preview-container");
        if (previewContainer) {
            previewContainer.querySelector(".image-preview").style.display = "none";
            previewContainer.querySelector(".image-name").textContent = "Keine Datei ausgewählt";
        }

        // Fehlernachrichten ausblenden
        const errorContainerFileSize = form.querySelector(".alert-danger-max-file-size");
        const errorContainerMaxFileUploads = form.querySelector(".alert-danger-max-file-uploads");
        if (errorContainerFileSize) {
            errorContainerFileSize.classList.add("visually-hidden");
        }
        if (errorContainerMaxFileUploads) {
            errorContainerMaxFileUploads.classList.add("visually-hidden");
        }
    });
});
