/**
 * Background Removal using briaai/RMBG-1.4 via Transformers.js
 * Fully client-side — no API key, no server request.
 * Model is ~168 MB and cached in the browser after first download.
 * License: Apache 2.0 (commercially safe)
 */

import { AutoModel, AutoProcessor, RawImage, env }
    from 'https://cdn.jsdelivr.net/npm/@huggingface/transformers@3/dist/transformers.min.js';

env.allowLocalModels = false;

// Check if the RMBG-1.4 ONNX model is already cached in the browser
async function isModelCached() {
    if (typeof caches === 'undefined') return false;
    try {
        const cache = await caches.open('transformers-cache');
        const keys = await cache.keys();
        return keys.some(r => r.url.includes('RMBG-1.4') && r.url.includes('model.onnx'));
    } catch {
        return false;
    }
}

/**
 * Remove background from an image file.
 * @param {File} imageFile - Input image file
 * @param {Function|null} onProgress - Callback: (percent: number) => void; -1 = done loading
 * @returns {HTMLCanvasElement} Canvas with transparent background
 */
async function removeBackground(imageFile, onProgress = null) {
    const cached = await isModelCached();

    const model = await AutoModel.from_pretrained('briaai/RMBG-1.4', {
        dtype: 'fp32',
        progress_callback: (!cached && onProgress)
            ? (event) => {
                if (event.status === 'progress' && event.progress != null)
                    onProgress(Math.round(event.progress));
                if (event.status === 'done')
                    onProgress(-1);
            }
            : undefined,
    });

    const processor = await AutoProcessor.from_pretrained('briaai/RMBG-1.4');

    const imgUrl = URL.createObjectURL(imageFile);
    const image = await RawImage.read(imgUrl);
    URL.revokeObjectURL(imgUrl);

    const { pixel_values } = await processor(image);
    const { output } = await model({ input: pixel_values });

    const mask = await RawImage.fromTensor(output[0].mul(255).to('uint8'))
        .resize(image.width, image.height);

    const canvas = document.createElement('canvas');
    canvas.width = image.width;
    canvas.height = image.height;
    const ctx = canvas.getContext('2d');

    const img = new Image();
    const imgObjectUrl = URL.createObjectURL(imageFile);
    await new Promise(resolve => { img.onload = resolve; img.src = imgObjectUrl; });
    ctx.drawImage(img, 0, 0);
    URL.revokeObjectURL(imgObjectUrl);

    const pixelData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    for (let i = 0; i < mask.data.length; i++) {
        pixelData.data[i * 4 + 3] = mask.data[i];
    }
    ctx.putImageData(pixelData, 0, 0);

    return canvas;
}

// =============================================
// UI CONTROLLER for BG Removal Section
// =============================================
function formatSize(bytes) {
    if (bytes >= 1024 * 1024) return (bytes / 1024 / 1024).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return bytes + ' Bytes';
}

const bgFileInput    = document.getElementById('bg-file-input');
const bgRemoveBtn    = document.getElementById('bg-remove-btn');
const bgProgress     = document.getElementById('bg-progress-container');
const bgProgressBar  = document.getElementById('bg-progress-bar');
const bgProgressPct  = document.getElementById('bg-progress-percent');
const bgProcessing   = document.getElementById('bg-processing');
const bgResult       = document.getElementById('bg-result');
const bgOriginalImg  = document.getElementById('bg-original-img');
const bgResultCanvas = document.getElementById('bg-result-canvas');
const bgDownloadBtn  = document.getElementById('bg-download-btn');
const bgFileName     = document.getElementById('bg-file-name');
const bgPreviewImg   = document.getElementById('bg-preview-img');
const bgClearBtn     = document.getElementById('bg-clear-btn');

if (!bgFileInput) {
    console.warn('bg-removal.js: #bg-file-input not found.');
} else {
    bgFileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) {
            bgRemoveBtn.disabled = true;
            return;
        }
        bgRemoveBtn.disabled = false;
        if (bgClearBtn) bgClearBtn.disabled = false;
        bgResult.classList.add('d-none');

        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            URL.revokeObjectURL(url);
            if (bgPreviewImg) {
                bgPreviewImg.src = img.src;
                bgPreviewImg.style.display = 'block';
            }
            if (bgFileName) bgFileName.textContent = `${file.name} (${img.naturalWidth}×${img.naturalHeight}px, ${formatSize(file.size)})`;
            bgOriginalImg.src = img.src;
        };
        img.src = url;
    });

    bgRemoveBtn.addEventListener('click', async () => {
        const file = bgFileInput.files[0];
        if (!file) return;

        bgRemoveBtn.disabled = true;
        bgResult.classList.add('d-none');
        bgProgress.classList.add('d-none');
        bgProcessing.classList.add('d-none');

        const cached = await isModelCached();

        if (!cached) {
            bgProgress.classList.remove('d-none');
            bgProgressBar.style.width = '0%';
            bgProgressPct.textContent = '0';
        } else {
            bgProcessing.classList.remove('d-none');
        }

        try {
            const resultCanvas = await removeBackground(file, (pct) => {
                if (pct === -1) {
                    bgProgress.classList.add('d-none');
                    bgProcessing.classList.remove('d-none');
                } else {
                    bgProgressBar.style.width = pct + '%';
                    bgProgressPct.textContent = pct;
                }
            });

            bgProgress.classList.add('d-none');
            bgProcessing.classList.add('d-none');

            // Display result
            const resultCtx = bgResultCanvas.getContext('2d');
            bgResultCanvas.width  = resultCanvas.width;
            bgResultCanvas.height = resultCanvas.height;
            resultCtx.clearRect(0, 0, bgResultCanvas.width, bgResultCanvas.height);
            resultCtx.drawImage(resultCanvas, 0, 0);

            bgResult.classList.remove('d-none');

        } catch (err) {
            console.error('BG Removal failed:', err);
            bgProgress.classList.add('d-none');
            bgProcessing.classList.add('d-none');
            alert('Fehler beim Verarbeiten des Bildes. Bitte versuche es erneut.');
        } finally {
            bgRemoveBtn.disabled = false;
        }
    });

    if (bgDownloadBtn) {
        bgDownloadBtn.addEventListener('click', () => {
            bgResultCanvas.toBlob(blob => {
                if (!blob) return;
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                const baseName = bgFileInput.files[0]?.name?.replace(/\.\w+$/, '') || 'ergebnis';
                a.href = url;
                a.download = `${baseName}_ohne_hintergrund.png`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }, 'image/png');
        });
    }

    if (bgClearBtn) {
        bgClearBtn.addEventListener('click', () => {
            bgFileInput.value = '';
            bgRemoveBtn.disabled = true;
            bgResult.classList.add('d-none');
            bgProgress.classList.add('d-none');
            bgProcessing.classList.add('d-none');
            bgOriginalImg.src = '';
            if (bgPreviewImg) { bgPreviewImg.src = ''; bgPreviewImg.style.display = 'none'; }
            if (bgFileName) bgFileName.textContent = 'Keine Datei ausgewählt.';
            const resultCtx = bgResultCanvas.getContext('2d');
            resultCtx.clearRect(0, 0, bgResultCanvas.width, bgResultCanvas.height);
            bgClearBtn.disabled = true;
        });
    }
}
