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
// QUALITY SLIDER
// =============================================
const qualitySlider = document.getElementById('quality-slider');
const qualityValue  = document.getElementById('quality-value');
if (qualitySlider && qualityValue) {
    qualitySlider.addEventListener('input', () => {
        qualityValue.textContent = qualitySlider.value;
    });
}

// =============================================
// WATERMARK SLIDERS
// =============================================
const wmOpacitySlider = document.getElementById('wm-opacity-slider');
const wmOpacityValue  = document.getElementById('wm-opacity-value');
if (wmOpacitySlider && wmOpacityValue) {
    wmOpacitySlider.addEventListener('input', () => {
        wmOpacityValue.textContent = wmOpacitySlider.value;
    });
}

const wmScaleSlider = document.getElementById('wm-scale-slider');
const wmScaleValue  = document.getElementById('wm-scale-value');
if (wmScaleSlider && wmScaleValue) {
    wmScaleSlider.addEventListener('input', () => {
        wmScaleValue.textContent = wmScaleSlider.value;
    });
}

// Watermark position grid: toggle active class on click
document.querySelectorAll('.wm-pos-cell').forEach(cell => {
    cell.addEventListener('click', () => {
        document.querySelectorAll('.wm-pos-cell').forEach(c => c.classList.remove('active'));
        cell.classList.add('active');
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
function handleFiles(files, form) {
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
        const dragHint = previewContainer.querySelector('.drag-hint');
        if (dragHint) dragHint.style.display = files.length > 0 ? 'none' : '';

        let totalSize = 0;
        let valid = true;

        if (files.length > 20) {
            if (errorCount) errorCount.classList.remove('visually-hidden');
            valid = false;
        } else {
            if (errorCount) errorCount.classList.add('visually-hidden');
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
            if (errorSize) errorSize.classList.remove('visually-hidden');
            valid = false;
        } else {
            if (errorSize) errorSize.classList.add('visually-hidden');
        }

        if (files.length > 20 || totalSize > 256 * 1024 * 1024) valid = false;
        if (submitBtn) submitBtn.disabled = !valid;
}

document.querySelectorAll('.file-input').forEach(input => {
    if (input.id === 'cropImageInput') return;
    if (input.id === 'wm_main') return;
    if (input.id === 'bg-file-input') return;
    input.addEventListener('change', function (e) {
        handleFiles(e.target.files, this.closest('form'));
    });
});

// =============================================
// DRAG & DROP — alle Preview-Container
// =============================================
document.querySelectorAll('.image-preview-container').forEach(zone => {
    const form = zone.closest('form');
    if (!form) return;
    const fileInput = form.querySelector('.file-input') || form.querySelector('input[type="file"]');
    if (!fileInput) return;

    zone.addEventListener('dragover', e => {
        e.preventDefault();
        zone.classList.add('drag-over');
    });

    zone.addEventListener('dragleave', () => {
        zone.classList.remove('drag-over');
    });

    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        const files = e.dataTransfer.files;
        if (!files.length) return;
        const dt = new DataTransfer();
        Array.from(files).forEach(f => dt.items.add(f));
        fileInput.files = dt.files;
        fileInput.dispatchEvent(new Event('change', { bubbles: true }));
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
            const dragHint = previewContainer.querySelector('.drag-hint');
            if (dragHint) dragHint.style.display = '';
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
let cropRatio = null; // null = free, { w, h } = locked ratio

function getCropValues() {
    return {
        left:   Math.max(0, parseInt(document.getElementById('crop-left').value)   || 0),
        top:    Math.max(0, parseInt(document.getElementById('crop-top').value)    || 0),
        right:  Math.max(0, parseInt(document.getElementById('crop-right').value)  || 0),
        bottom: Math.max(0, parseInt(document.getElementById('crop-bottom').value) || 0),
    };
}

function setCropValues({ left, top, right, bottom }) {
    document.getElementById('crop-left').value   = left;
    document.getElementById('crop-top').value    = top;
    document.getElementById('crop-right').value  = right;
    document.getElementById('crop-bottom').value = bottom;
}

// Apply ratio constraint: keep top-left fixed, adjust right+bottom
function applyRatioConstraint(left, top, right, bottom, anchor) {
    if (!cropRatio || !cropImg) return { left, top, right, bottom };
    const { w: rW, h: rH } = cropRatio;
    const imgW = cropImg.naturalWidth;
    const imgH = cropImg.naturalHeight;

    // Current crop dimensions
    let cropW = imgW - left - right;
    let cropH = imgH - top  - bottom;

    // Anchor determines which sides are "pinned":
    // 'nw' = top+left fixed → adjust right+bottom
    // 'ne' = top+right fixed → adjust left+bottom
    // 'sw' = bottom+left fixed → adjust right+top
    // 'se' = bottom+right fixed → adjust left+top
    // edge handles: pin the opposite edge, adjust the two non-dragged sides symmetrically

    if (anchor === 'nw' || anchor === 'ne' || anchor === 'sw' || anchor === 'se') {
        // Fit height to width by ratio
        cropH = Math.round(cropW * rH / rW);
        if (anchor === 'nw') { right  = Math.max(0, imgW - left  - cropW); bottom = Math.max(0, imgH - top    - cropH); }
        if (anchor === 'ne') { left   = Math.max(0, imgW - right - cropW); bottom = Math.max(0, imgH - top    - cropH); }
        if (anchor === 'sw') { right  = Math.max(0, imgW - left  - cropW); top    = Math.max(0, imgH - bottom - cropH); }
        if (anchor === 'se') { left   = Math.max(0, imgW - right - cropW); top    = Math.max(0, imgH - bottom - cropH); }
    } else if (anchor === 'left' || anchor === 'right') {
        // Width changed → recalculate height, keep vertical center
        const centerY = top + cropH / 2;
        cropH = Math.round(cropW * rH / rW);
        top    = Math.max(0, Math.round(centerY - cropH / 2));
        bottom = Math.max(0, imgH - top - cropH);
    } else if (anchor === 'top' || anchor === 'bottom') {
        // Height changed → recalculate width, keep horizontal center
        const centerX = left + cropW / 2;
        cropW = Math.round(cropH * rW / rH);
        left  = Math.max(0, Math.round(centerX - cropW / 2));
        right = Math.max(0, imgW - left - cropW);
    }

    // Clamp
    left   = Math.max(0, Math.min(left,   imgW - 1));
    top    = Math.max(0, Math.min(top,    imgH - 1));
    right  = Math.max(0, Math.min(right,  imgW - left  - 1));
    bottom = Math.max(0, Math.min(bottom, imgH - top   - 1));

    return { left, top, right, bottom };
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

    cropCtx.fillStyle = 'rgba(0,0,0,0.55)';
    const lx = left * sx;
    const ty = top * sy;
    const rx = W - right * sx;
    const by = H - bottom * sy;

    if (lx > 0)       cropCtx.fillRect(0, 0, lx, H);
    if (W - rx > 0)   cropCtx.fillRect(rx, 0, W - rx, H);
    if (ty > 0)       cropCtx.fillRect(lx, 0, rx - lx, ty);
    if (H - by > 0)   cropCtx.fillRect(lx, by, rx - lx, H - by);

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
        const side   = h.dataset.side;
        const corner = h.dataset.corner;
        if (side === 'left')   { h.style.left = lx + 'px';   h.style.top  = midY + 'px'; }
        if (side === 'right')  { h.style.left = rx + 'px';   h.style.top  = midY + 'px'; }
        if (side === 'top')    { h.style.top  = ty + 'px';   h.style.left = midX + 'px'; }
        if (side === 'bottom') { h.style.top  = by + 'px';   h.style.left = midX + 'px'; }
        if (corner === 'nw')   { h.style.left = lx + 'px';   h.style.top  = ty   + 'px'; }
        if (corner === 'ne')   { h.style.left = rx + 'px';   h.style.top  = ty   + 'px'; }
        if (corner === 'sw')   { h.style.left = lx + 'px';   h.style.top  = by   + 'px'; }
        if (corner === 'se')   { h.style.left = rx + 'px';   h.style.top  = by   + 'px'; }
    });
}

function initCropHandles() {
    // ---- Edge handles ----
    cropHandleWrapper.querySelectorAll('.crop-handle[data-side]').forEach(handle => {
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

            let { left, top, right, bottom } = getCropValues();
            if (side === 'left')   { newVal = Math.max(0, Math.min(newVal, cropImg.naturalWidth  - right  - 1)); left   = newVal; }
            if (side === 'right')  { newVal = Math.max(0, Math.min(newVal, cropImg.naturalWidth  - left   - 1)); right  = newVal; }
            if (side === 'top')    { newVal = Math.max(0, Math.min(newVal, cropImg.naturalHeight - bottom - 1)); top    = newVal; }
            if (side === 'bottom') { newVal = Math.max(0, Math.min(newVal, cropImg.naturalHeight - top    - 1)); bottom = newVal; }

            const clamped = applyRatioConstraint(left, top, right, bottom, side);
            setCropValues(clamped);
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

    // ---- Corner handles ----
    cropHandleWrapper.querySelectorAll('.crop-handle[data-corner]').forEach(handle => {
        const corner = handle.dataset.corner; // 'nw' | 'ne' | 'sw' | 'se'
        let startX, startY, startLeft, startTop, startRight, startBottom;

        const onStart = (e) => {
            e.preventDefault();
            const pt = e.touches ? e.touches[0] : e;
            startX = pt.clientX;
            startY = pt.clientY;
            ({ left: startLeft, top: startTop, right: startRight, bottom: startBottom } = getCropValues());
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
            const rect = cropCanvas.getBoundingClientRect();
            const imgPerCssX = cropImg.naturalWidth  / rect.width;
            const imgPerCssY = cropImg.naturalHeight / rect.height;

            const dx = (pt.clientX - startX) * imgPerCssX;
            const dy = (pt.clientY - startY) * imgPerCssY;

            // Each corner moves two sides
            let left   = startLeft;
            let top    = startTop;
            let right  = startRight;
            let bottom = startBottom;

            if (corner === 'nw') { left  = Math.round(startLeft  + dx); top    = Math.round(startTop    + dy); }
            if (corner === 'ne') { right = Math.round(startRight - dx); top    = Math.round(startTop    + dy); }
            if (corner === 'sw') { left  = Math.round(startLeft  + dx); bottom = Math.round(startBottom - dy); }
            if (corner === 'se') { right = Math.round(startRight - dx); bottom = Math.round(startBottom - dy); }

            // Clamp before ratio
            left   = Math.max(0, Math.min(left,   cropImg.naturalWidth  - right  - 1));
            top    = Math.max(0, Math.min(top,     cropImg.naturalHeight - bottom - 1));
            right  = Math.max(0, Math.min(right,   cropImg.naturalWidth  - left   - 1));
            bottom = Math.max(0, Math.min(bottom,  cropImg.naturalHeight - top    - 1));

            const clamped = applyRatioConstraint(left, top, right, bottom, corner);
            setCropValues(clamped);
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

    // ---- Canvas move drag (drag crop area itself) ----
    {
        let moveStartX, moveStartY, moveStartLeft, moveStartTop, moveStartRight, moveStartBottom;
        let isMoving = false;

        const onMoveStart = (e) => {
            if (!cropImg) return;
            // Only start if click is inside the crop area (not on handles)
            if (e.target !== cropCanvas) return;
            const pt = e.touches ? e.touches[0] : e;
            const rect = cropCanvas.getBoundingClientRect();
            const imgPerCssX = cropImg.naturalWidth  / rect.width;
            const imgPerCssY = cropImg.naturalHeight / rect.height;
            const clickX = (pt.clientX - rect.left) * imgPerCssX;
            const clickY = (pt.clientY - rect.top)  * imgPerCssY;

            const { left, top, right, bottom } = getCropValues();
            const cropRight  = cropImg.naturalWidth  - right;
            const cropBottom = cropImg.naturalHeight - bottom;

            // Only start move if click is inside crop area
            if (clickX < left || clickX > cropRight || clickY < top || clickY > cropBottom) return;

            e.preventDefault();
            isMoving = true;
            moveStartX = pt.clientX;
            moveStartY = pt.clientY;
            moveStartLeft   = left;
            moveStartTop    = top;
            moveStartRight  = right;
            moveStartBottom = bottom;
            cropCanvas.style.cursor = 'grabbing';

            document.addEventListener('mousemove', onMoveMove);
            document.addEventListener('mouseup',   onMoveEnd);
            document.addEventListener('touchmove', onMoveMove, { passive: false });
            document.addEventListener('touchend',  onMoveEnd);
        };

        const onMoveMove = (e) => {
            if (!isMoving || !cropImg) return;
            if (e.cancelable) e.preventDefault();
            const pt = e.touches ? e.touches[0] : e;
            const rect = cropCanvas.getBoundingClientRect();
            const imgPerCssX = cropImg.naturalWidth  / rect.width;
            const imgPerCssY = cropImg.naturalHeight / rect.height;

            const dx = Math.round((pt.clientX - moveStartX) * imgPerCssX);
            const dy = Math.round((pt.clientY - moveStartY) * imgPerCssY);

            const cropW = cropImg.naturalWidth  - moveStartLeft - moveStartRight;
            const cropH = cropImg.naturalHeight - moveStartTop  - moveStartBottom;

            let newLeft = moveStartLeft + dx;
            let newTop  = moveStartTop  + dy;

            // Clamp so crop area stays within image
            newLeft = Math.max(0, Math.min(newLeft, cropImg.naturalWidth  - cropW));
            newTop  = Math.max(0, Math.min(newTop,  cropImg.naturalHeight - cropH));

            setCropValues({
                left:   newLeft,
                top:    newTop,
                right:  cropImg.naturalWidth  - newLeft - cropW,
                bottom: cropImg.naturalHeight - newTop  - cropH,
            });
            drawCropCanvas();
            updateHandlePositions();
        };

        const onMoveEnd = () => {
            isMoving = false;
            cropCanvas.style.cursor = 'crosshair';
            document.removeEventListener('mousemove', onMoveMove);
            document.removeEventListener('mouseup',   onMoveEnd);
            document.removeEventListener('touchmove', onMoveMove);
            document.removeEventListener('touchend',  onMoveEnd);
        };

        cropCanvas.addEventListener('mousedown',  onMoveStart);
        cropCanvas.addEventListener('touchstart', onMoveStart, { passive: false });

        // Cursor hint: grab inside crop area, crosshair outside
        cropCanvas.addEventListener('mousemove', (e) => {
            if (!cropImg || isMoving) return;
            const rect = cropCanvas.getBoundingClientRect();
            const imgPerCssX = cropImg.naturalWidth  / rect.width;
            const imgPerCssY = cropImg.naturalHeight / rect.height;
            const mx = (e.clientX - rect.left) * imgPerCssX;
            const my = (e.clientY - rect.top)  * imgPerCssY;
            const { left, top, right, bottom } = getCropValues();
            const inside = mx >= left && mx <= cropImg.naturalWidth - right
                        && my >= top  && my <= cropImg.naturalHeight - bottom;
            cropCanvas.style.cursor = inside ? 'grab' : 'crosshair';
        });
    }
}

// ---- Ratio button logic ----
document.querySelectorAll('.btn-ratio').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.btn-ratio').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const val = btn.dataset.ratio;
        if (!val) {
            cropRatio = null;
        } else {
            const [w, h] = val.split(':').map(Number);
            cropRatio = { w, h };
            // If image is loaded, immediately apply ratio from current crop state
            if (cropImg) {
                const { left, top, right, bottom } = getCropValues();
                const clamped = applyRatioConstraint(left, top, right, bottom, 'nw');
                setCropValues(clamped);
                drawCropCanvas();
                updateHandlePositions();
            }
        }
    });
});

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

                const dragHintCrop = document.querySelector('#content1 .drag-hint');
                if (dragHintCrop) dragHintCrop.style.display = 'none';

                const nameEl = document.querySelector('#content1 .image-name');
                if (nameEl) nameEl.textContent = `${file.name} (${img.naturalWidth}×${img.naturalHeight}px, ${formatSize(file.size)})`;

                drawCropCanvas();
                requestAnimationFrame(() => updateHandlePositions());
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    // Live update on pixel field input (apply ratio if locked)
    ['crop-left', 'crop-top', 'crop-right', 'crop-bottom'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', () => {
            if (cropRatio) {
                const side = id.replace('crop-', ''); // 'left' | 'top' | 'right' | 'bottom'
                const { left, top, right, bottom } = getCropValues();
                const clamped = applyRatioConstraint(left, top, right, bottom, side);
                setCropValues(clamped);
            }
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
            const dragHintCropClr = document.querySelector('#content1 .drag-hint');
            if (dragHintCropClr) dragHintCropClr.style.display = '';
            // Reset ratio selection to "Frei"
            cropRatio = null;
            document.querySelectorAll('.btn-ratio').forEach(b => b.classList.remove('active'));
            const freeBtn = document.querySelector('.btn-ratio[data-ratio=""]');
            if (freeBtn) freeBtn.classList.add('active');
        });
    }

    // Reposition handles on window resize
    window.addEventListener('resize', () => {
        if (cropImg) requestAnimationFrame(() => updateHandlePositions());
    });
}

// =============================================
// WATERMARK LIVE PREVIEW
// =============================================
const wmMainInput    = document.getElementById('wm_main');
const wmLogoInput    = document.getElementById('wm_logo');
const wmCanvas       = document.getElementById('wm-preview-canvas');
const wmMainName     = document.getElementById('wm-main-name');
const wmOpacityEl    = document.getElementById('wm-opacity-slider');
const wmScaleEl      = document.getElementById('wm-scale-slider');
const wmMarginEl     = document.getElementById('wm_margin');
const wmClearBtn     = wmMainInput ? wmMainInput.closest('form').querySelector('.clear-button') : null;
const wmSubmitBtn    = wmMainInput ? wmMainInput.closest('form').querySelector('[type="submit"]') : null;
const wmDragHint     = document.querySelector('#wm-main-preview-container .drag-hint');

let wmMainImg = null;
let wmLogoImg = null;

function drawWmPreview() {
    if (!wmCanvas || !wmMainImg) return;
    const mW = wmMainImg.naturalWidth;
    const mH = wmMainImg.naturalHeight;

    // Scale canvas to fit container (max 800px wide)
    const maxW = wmCanvas.parentElement.clientWidth - 32 || 800;
    const scale = Math.min(maxW / mW, 1);
    wmCanvas.width  = Math.round(mW * scale);
    wmCanvas.height = Math.round(mH * scale);

    const ctx = wmCanvas.getContext('2d');
    ctx.clearRect(0, 0, wmCanvas.width, wmCanvas.height);
    ctx.drawImage(wmMainImg, 0, 0, wmCanvas.width, wmCanvas.height);

    if (!wmLogoImg) return;

    const wmScalePct = parseInt(wmScaleEl?.value ?? 25) / 100;
    const opacity    = parseInt(wmOpacityEl?.value ?? 80) / 100;
    const margin     = parseInt(wmMarginEl?.value ?? 20) * scale;

    const logoW = Math.round(wmCanvas.width * wmScalePct);
    const ratio  = wmLogoImg.naturalHeight / wmLogoImg.naturalWidth;
    const logoH  = Math.round(logoW * ratio);

    const posVal = document.querySelector('input[name="wm_position"]:checked')?.value ?? 'bottom-right';
    const [posV, posH] = posVal.split('-');

    let x, y;
    switch (posH) {
        case 'left':   x = margin; break;
        case 'right':  x = wmCanvas.width  - logoW - margin; break;
        default:       x = (wmCanvas.width  - logoW) / 2;
    }
    switch (posV) {
        case 'top':    y = margin; break;
        case 'bottom': y = wmCanvas.height - logoH - margin; break;
        default:       y = (wmCanvas.height - logoH) / 2;
    }

    ctx.globalAlpha = opacity;
    ctx.drawImage(wmLogoImg, x, y, logoW, logoH);
    ctx.globalAlpha = 1;
}

function loadWmImage(file, callback) {
    const reader = new FileReader();
    reader.onload = e => {
        const img = new Image();
        img.onload = () => callback(img);
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

if (wmMainInput) {
    wmMainInput.addEventListener('change', function () {
        const files = this.files;
        if (!files || files.length === 0) return;

        const form = this.closest('form');
        const errorCount = form.querySelector('.alert-danger-max-file-uploads');
        const errorSize  = form.querySelector('.alert-danger-max-file-size');

        // Validate count
        if (files.length > 20) {
            if (errorCount) errorCount.classList.remove('visually-hidden');
            if (wmSubmitBtn) wmSubmitBtn.disabled = true;
            return;
        }
        if (errorCount) errorCount.classList.add('visually-hidden');

        // Validate total size
        let totalSize = 0;
        Array.from(files).forEach(f => totalSize += f.size);
        if (totalSize > 256 * 1024 * 1024) {
            if (errorSize) errorSize.classList.remove('visually-hidden');
            if (wmSubmitBtn) wmSubmitBtn.disabled = true;
            return;
        }
        if (errorSize) errorSize.classList.add('visually-hidden');

        if (wmClearBtn) wmClearBtn.disabled = false;
        if (wmDragHint) wmDragHint.style.display = 'none';

        // Always load first file for canvas preview
        const firstFile = files[0];
        loadWmImage(firstFile, img => {
            wmMainImg = img;
            if (wmCanvas) wmCanvas.style.display = 'block';
            if (wmSubmitBtn && wmLogoInput?.files?.length) wmSubmitBtn.disabled = false;
            drawWmPreview();
        });

        // Build name display
        if (wmMainName) {
            wmMainName.innerHTML = '';
            if (files.length === 1) {
                const firstFile = files[0];
                const reader = new FileReader();
                reader.onload = ev => {
                    const img = new Image();
                    img.onload = () => {
                        wmMainName.textContent = `${firstFile.name} (${img.naturalWidth}×${img.naturalHeight}px, ${formatSize(firstFile.size)})`;
                    };
                    img.src = ev.target.result;
                };
                reader.readAsDataURL(firstFile);
            } else {
                const sizePara = document.createElement('p');
                sizePara.className = 'total-size-paragraph mb-1 fw-semibold';
                sizePara.textContent = `Gesamtgröße: ${formatSize(totalSize)}`;
                const ol = document.createElement('ol');
                wmMainName.appendChild(sizePara);
                wmMainName.appendChild(ol);
                Array.from(files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = ev => {
                        const img = new Image();
                        img.onload = () => {
                            const li = document.createElement('li');
                            li.textContent = `${file.name} (${img.naturalWidth}×${img.naturalHeight}px, ${formatSize(file.size)})`;
                            ol.appendChild(li);
                        };
                        img.src = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                });
            }
        }
    });
}

if (wmLogoInput) {
    wmLogoInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) { wmLogoImg = null; drawWmPreview(); return; }
        loadWmImage(file, img => {
            wmLogoImg = img;
            if (wmSubmitBtn && wmMainImg) wmSubmitBtn.disabled = false;
            drawWmPreview();
        });
    });
}

// Redraw on any setting change
[wmOpacityEl, wmScaleEl, wmMarginEl].forEach(el => {
    if (el) el.addEventListener('input', drawWmPreview);
});

document.querySelectorAll('input[name="wm_position"]').forEach(radio => {
    radio.addEventListener('change', drawWmPreview);
});

// Clear button
if (wmClearBtn) {
    wmClearBtn.addEventListener('click', () => {
        wmMainImg = null;
        wmMainInput.value = '';
        if (wmCanvas) { wmCanvas.style.display = 'none'; wmCanvas.getContext('2d').clearRect(0, 0, wmCanvas.width, wmCanvas.height); }
        if (wmMainName) { wmMainName.innerHTML = ''; wmMainName.textContent = 'Keine Datei ausgewählt.'; }
        if (wmDragHint) wmDragHint.style.display = '';
        if (wmSubmitBtn) wmSubmitBtn.disabled = true;
        wmClearBtn.disabled = true;
        const form = wmMainInput.closest('form');
        form.querySelectorAll('.alert-danger-max-file-size, .alert-danger-max-file-uploads').forEach(el => el.classList.add('visually-hidden'));
    });
}
