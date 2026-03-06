// =============================================
// NAV SECTION SWITCHING
// =============================================
document.querySelectorAll('nav .btn[data-target]').forEach(button => {
    button.addEventListener('click', function () {
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        document.getElementById(this.dataset.target).classList.add('active');
        document.querySelectorAll('nav .btn[data-target]').forEach(b => b.classList.remove('btn-primary-active'));
        this.classList.add('btn-primary-active');
        localStorage.setItem('activeSection', this.dataset.target);
    });
});

window.addEventListener('load', () => {
    const saved = localStorage.getItem('activeSection');
    if (saved && document.getElementById(saved)) {
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        document.getElementById(saved).classList.add('active');
        document.querySelectorAll('nav .btn[data-target]').forEach(btn => {
            btn.classList.toggle('btn-primary-active', btn.dataset.target === saved);
        });
    }
});

// =============================================
// DARK MODE
// =============================================
const themeToggler = document.getElementById('theme-toggler');
const sunIcon = document.querySelector('.sun-icon');
const moonIcon = document.querySelector('.moon-icon');
const htmlEl = document.documentElement;

function setTheme(theme) {
    htmlEl.setAttribute('data-bs-theme', theme);
    const isDark = theme === 'dark';
    sunIcon.classList.toggle('visually-hidden', isDark);
    moonIcon.classList.toggle('visually-hidden', !isDark);
    const label = isDark ? 'Heller Modus' : 'Dunkler Modus';
    themeToggler.setAttribute('aria-label', label);
    themeToggler.setAttribute('title', label);
    localStorage.setItem('theme', theme);
}

const storedTheme = localStorage.getItem('theme');
if (storedTheme) {
    setTheme(storedTheme);
} else {
    setTheme(window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
}

themeToggler.addEventListener('click', () => {
    setTheme(htmlEl.getAttribute('data-bs-theme') === 'light' ? 'dark' : 'light');
});

// =============================================
// LOADING SPINNER (server form submits)
// =============================================
document.querySelectorAll('form.server-form').forEach(form => {
    form.addEventListener('submit', () => {
        document.getElementById('spinner-overlay').classList.remove('d-none');
        const btn = form.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;
    });
});

// =============================================
// SHARED HELPER
// =============================================
const formatSize = (bytes) => {
    if (bytes >= 1024 * 1024) return (bytes / 1024 / 1024).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return bytes + ' Bytes';
};

// =============================================
// FILE PREVIEW — scale + webp sections
// =============================================
document.querySelectorAll('.file-input').forEach(input => {
    input.addEventListener('change', function (e) {
        const files = e.target.files;
        const form = this.closest('form');
        const previewContainer = form.querySelector('.image-preview-container');
        const preview = previewContainer.querySelector('.image-preview');
        const fileNameContainer = previewContainer.querySelector('.image-name');
        const errorSize = form.querySelector('.alert-danger-max-file-size');
        const errorCount = form.querySelector('.alert-danger-max-file-uploads');
        const submitBtn = form.querySelector('[type="submit"]');
        const clearBtn = form.querySelector('.clear-button');
        if (clearBtn) clearBtn.disabled = files.length === 0;

        fileNameContainer.textContent = '';
        fileNameContainer.innerHTML = '';

        let totalSize = 0;
        let valid = true;

        if (files.length > 20) {
            errorCount.classList.remove('visually-hidden');
            valid = false;
        } else {
            errorCount.classList.add('visually-hidden');
        }

        if (files.length > 1) {
            preview.style.display = 'none';
            const ol = document.createElement('ol');
            const sizePara = document.createElement('p');
            sizePara.className = 'total-size-paragraph mb-1 fw-semibold';
            fileNameContainer.appendChild(sizePara);
            fileNameContainer.appendChild(ol);

            Array.from(files).forEach(file => {
                totalSize += file.size;
                const li = document.createElement('li');
                const reader = new FileReader();
                reader.onload = ev => {
                    const img = new Image();
                    img.onload = () => {
                        li.textContent = `${file.name} (${img.width}×${img.height}px, ${formatSize(file.size)})`;
                        ol.appendChild(li);
                        sizePara.textContent = `Gesamtgröße: ${formatSize(totalSize)}`;
                    };
                    img.src = ev.target.result;
                };
                reader.readAsDataURL(file);
            });
        } else if (files.length === 1) {
            const file = files[0];
            totalSize = file.size;
            const reader = new FileReader();
            reader.onload = ev => {
                preview.src = ev.target.result;
                preview.style.display = 'block';
                const img = new Image();
                img.onload = () => {
                    fileNameContainer.textContent = `${file.name} (${img.width}×${img.height}px, ${formatSize(file.size)})`;
                };
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            fileNameContainer.textContent = 'Keine Datei ausgewählt.';
            preview.style.display = 'none';
        }

        if (totalSize > 256 * 1024 * 1024) {
            errorSize.classList.remove('visually-hidden');
            valid = false;
        } else {
            errorSize.classList.add('visually-hidden');
        }

        if (files.length > 20 || totalSize > 256 * 1024 * 1024) valid = false;
        submitBtn.disabled = !valid;
    });
});

// =============================================
// CLEAR BUTTONS — scale + webp sections
// =============================================
document.querySelectorAll('.clear-button').forEach(btn => {
    btn.addEventListener('click', function () {
        const form = this.closest('form');
        const fileInput = form.querySelector('.file-input');
        if (fileInput) fileInput.value = '';

        const previewContainer = form.querySelector('.image-preview-container');
        if (previewContainer) {
            const preview = previewContainer.querySelector('.image-preview');
            if (preview) preview.style.display = 'none';
            const nameEl = previewContainer.querySelector('.image-name');
            if (nameEl) nameEl.textContent = 'Keine Datei ausgewählt.';
        }

        form.querySelectorAll('.alert-danger-max-file-size, .alert-danger-max-file-uploads').forEach(el => {
            el.classList.add('visually-hidden');
        });

        this.disabled = true;
    });
});

// =============================================
// CROP SECTION — CANVAS PREVIEW + DRAG HANDLES
// =============================================
const cropFileInput     = document.getElementById('cropImageInput');
const cropCanvas        = document.getElementById('crop-canvas-full');
const cropCtx           = cropCanvas ? cropCanvas.getContext('2d') : null;
const cropHandleWrapper = document.querySelector('.crop-handle-wrapper');
const cropDimText       = document.getElementById('crop-dimensions');
const cropClearBtn      = document.getElementById('crop-clear-btn');

let cropImg = null;

function getCropValues() {
    return {
        left:   Math.max(0, parseInt(document.getElementById('crop-left').value)   || 0),
        top:    Math.max(0, parseInt(document.getElementById('crop-top').value)    || 0),
        right:  Math.max(0, parseInt(document.getElementById('crop-right').value)  || 0),
        bottom: Math.max(0, parseInt(document.getElementById('crop-bottom').value) || 0),
    };
}

function drawCropCanvas() {
    if (!cropImg || !cropCtx) return;
    const { left, top, right, bottom } = getCropValues();
    const W = cropCanvas.width;
    const H = cropCanvas.height;
    const sx = W / cropImg.naturalWidth;
    const sy = H / cropImg.naturalHeight;

    cropCtx.clearRect(0, 0, W, H);
    cropCtx.drawImage(cropImg, 0, 0, W, H);

    // Darken cropped-away areas
    cropCtx.fillStyle = 'rgba(0,0,0,0.55)';
    const lx = left * sx;
    const ty = top * sy;
    const rx = W - right * sx;
    const by = H - bottom * sy;

    if (lx > 0)       cropCtx.fillRect(0, 0, lx, H);
    if (W - rx > 0)   cropCtx.fillRect(rx, 0, W - rx, H);
    if (ty > 0)       cropCtx.fillRect(lx, 0, rx - lx, ty);
    if (H - by > 0)   cropCtx.fillRect(lx, by, rx - lx, H - by);

    // White border around keep area
    cropCtx.strokeStyle = 'rgba(255,255,255,0.9)';
    cropCtx.lineWidth = 2;
    cropCtx.strokeRect(lx + 1, ty + 1, rx - lx - 2, by - ty - 2);

    const cropW = Math.max(1, cropImg.naturalWidth - left - right);
    const cropH = Math.max(1, cropImg.naturalHeight - top - bottom);
    if (cropDimText) cropDimText.textContent = `Ergebnis: ${cropW} × ${cropH} px`;
}

function updateHandlePositions() {
    if (!cropImg || !cropCanvas || !cropHandleWrapper) return;
    const { left, top, right, bottom } = getCropValues();
    const canvasRect  = cropCanvas.getBoundingClientRect();
    const wrapperRect = cropHandleWrapper.getBoundingClientRect();
    if (canvasRect.width === 0) return;

    // Canvas offset within wrapper (due to margin:auto centering)
    const offsetX = canvasRect.left - wrapperRect.left;
    const offsetY = canvasRect.top  - wrapperRect.top;

    const cssPerImgX = canvasRect.width  / cropImg.naturalWidth;
    const cssPerImgY = canvasRect.height / cropImg.naturalHeight;

    const lx = offsetX + left  * cssPerImgX;
    const rx = offsetX + (cropImg.naturalWidth  - right)  * cssPerImgX;
    const ty = offsetY + top   * cssPerImgY;
    const by = offsetY + (cropImg.naturalHeight - bottom) * cssPerImgY;

    const midX = (lx + rx) / 2;
    const midY = (ty + by) / 2;

    cropHandleWrapper.querySelectorAll('.crop-handle').forEach(h => {
        const side = h.dataset.side;
        if (side === 'left')   { h.style.left = lx + 'px'; h.style.top = midY + 'px'; }
        if (side === 'right')  { h.style.left = rx + 'px'; h.style.top = midY + 'px'; }
        if (side === 'top')    { h.style.top  = ty + 'px'; h.style.left = midX + 'px'; }
        if (side === 'bottom') { h.style.top  = by + 'px'; h.style.left = midX + 'px'; }
    });
}

function initCropHandles() {
    cropHandleWrapper.querySelectorAll('.crop-handle').forEach(handle => {
        const side = handle.dataset.side;
        let startClient, startValue;

        const onStart = (e) => {
            e.preventDefault();
            const pt = e.touches ? e.touches[0] : e;
            startClient = (side === 'left' || side === 'right') ? pt.clientX : pt.clientY;
            startValue = parseInt(document.getElementById(`crop-${side}`).value) || 0;
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onEnd);
            document.addEventListener('touchmove', onMove, { passive: false });
            document.addEventListener('touchend', onEnd);
            handle.style.opacity = '1';
        };

        const onMove = (e) => {
            if (e.cancelable) e.preventDefault();
            if (!cropImg) return;
            const pt = e.touches ? e.touches[0] : e;
            const clientVal = (side === 'left' || side === 'right') ? pt.clientX : pt.clientY;
            const rect = cropCanvas.getBoundingClientRect();
            const isHoriz = (side === 'left' || side === 'right');
            const imgPerCss = isHoriz
                ? (cropImg.naturalWidth / rect.width)
                : (cropImg.naturalHeight / rect.height);

            let delta = (clientVal - startClient) * imgPerCss;
            if (side === 'right' || side === 'bottom') delta = -delta;
            let newVal = Math.round(startValue + delta);

            const { left, top, right, bottom } = getCropValues();
            if (side === 'left')   newVal = Math.max(0, Math.min(newVal, cropImg.naturalWidth  - right  - 1));
            if (side === 'right')  newVal = Math.max(0, Math.min(newVal, cropImg.naturalWidth  - left   - 1));
            if (side === 'top')    newVal = Math.max(0, Math.min(newVal, cropImg.naturalHeight - bottom - 1));
            if (side === 'bottom') newVal = Math.max(0, Math.min(newVal, cropImg.naturalHeight - top    - 1));

            document.getElementById(`crop-${side}`).value = newVal;
            drawCropCanvas();
            updateHandlePositions();
        };

        const onEnd = () => {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onEnd);
            document.removeEventListener('touchmove', onMove);
            document.removeEventListener('touchend', onEnd);
            handle.style.opacity = '';
        };

        handle.addEventListener('mousedown', onStart);
        handle.addEventListener('touchstart', onStart, { passive: false });
    });
}

if (cropFileInput && cropCanvas && cropHandleWrapper) {
    initCropHandles();

    cropFileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        const errorSize = document.querySelector('#content1 .alert-danger-max-file-size');
        const submitBtn = document.querySelector('#content1 [type="submit"]');

        if (file.size > 256 * 1024 * 1024) {
            errorSize.classList.remove('visually-hidden');
            submitBtn.disabled = true;
            return;
        }
        errorSize.classList.add('visually-hidden');
        submitBtn.disabled = false;
        if (cropClearBtn) cropClearBtn.disabled = false;

        const reader = new FileReader();
        reader.onload = function (e) {
            const img = new Image();
            img.onload = function () {
                cropImg = img;
                const maxW = cropHandleWrapper.clientWidth || 800;
                const scale = Math.min(maxW / img.naturalWidth, 1);
                cropCanvas.width  = Math.round(img.naturalWidth  * scale);
                cropCanvas.height = Math.round(img.naturalHeight * scale);

                cropHandleWrapper.style.display = 'block';

                const nameEl = document.querySelector('#content1 .image-name');
                if (nameEl) nameEl.textContent = `${file.name} (${img.naturalWidth}×${img.naturalHeight}px, ${formatSize(file.size)})`;

                drawCropCanvas();
                requestAnimationFrame(() => updateHandlePositions());
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    // Live update on pixel field input
    ['crop-left', 'crop-top', 'crop-right', 'crop-bottom'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', () => {
            drawCropCanvas();
            updateHandlePositions();
        });
    });

    // Clear button for crop section
    if (cropClearBtn) {
        cropClearBtn.addEventListener('click', () => {
            cropFileInput.value = '';
            cropImg = null;
            if (cropCtx) cropCtx.clearRect(0, 0, cropCanvas.width, cropCanvas.height);
            cropHandleWrapper.style.display = 'none';
            ['crop-left', 'crop-top', 'crop-right', 'crop-bottom'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            const nameEl = document.querySelector('#content1 .image-name');
            if (nameEl) nameEl.textContent = 'Keine Datei ausgewählt.';
            if (cropDimText) cropDimText.textContent = '';
            const errorSize = document.querySelector('#content1 .alert-danger-max-file-size');
            if (errorSize) errorSize.classList.add('visually-hidden');
            const submitBtn = document.querySelector('#content1 [type="submit"]');
            if (submitBtn) submitBtn.disabled = false;
            cropClearBtn.disabled = true;
        });
    }

    // Reposition handles on window resize
    window.addEventListener('resize', () => {
        if (cropImg) requestAnimationFrame(() => updateHandlePositions());
    });
}
