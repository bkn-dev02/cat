<?php
// Pastikan hanya dimuat sekali
if (!defined('OCR_SCANNER_LOADED')) {
    define('OCR_SCANNER_LOADED', true);
?>

    <!-- MathJax Configuration -->
    <script>
        // Set config only if not already set by the page
        if (typeof MathJax === 'undefined') {
            window.MathJax = {
                tex: {
                    inlineMath: [['\\(', '\\)'], ['$', '$']],
                    displayMath: [['$$', '$$'], ['\\[', '\\]']],
                    processEscapes: true,
                    processEnvironments: true,
                    packages: { '[+]': ['ams'] }
                },
                options: {
                    skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre'],
                    processHtmlClass: 'mathjax-process'
                }
            };
        }
        // Do NOT add another <script> tag here — navbar.php handles loading if needed
    </script>

    <!-- Dependencies -->
    <link rel="stylesheet" href="<?= asset_url('vendor/cropperjs/1.6.1/dist/cropper.min.css') ?>">
    <script src="<?= asset_url('vendor/cropperjs/1.6.1/dist/cropper.min.js') ?>"></script>
    <script src="<?= asset_url('vendor/tesseract.js/5.1.1/dist/tesseract.min.js') ?>"></script>

    <style>
        .ocr-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            z-index: 10050;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .ocr-modal-overlay.active {
            display: flex;
        }

        .ocr-modal-box {
            background: #fff;
            border-radius: 1rem;
            width: 100%;
            max-width: 760px;
            max-height: 92vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
        }

        .ocr-modal-header {
            background: #2563eb;
            color: #fff;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-shrink: 0;
        }

        .ocr-modal-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .ocr-subtitle {
            margin: 0.3rem 0 0;
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .ocr-close-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ocr-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .ocr-modal-body {
            padding: 1.5rem;
            overflow-y: auto;
        }

        .ocr-source-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .ocr-source-btn {
            border: 2px dashed #e5e7eb;
            background: #f8fafc;
            border-radius: 0.9rem;
            padding: 1.75rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
            font-family: inherit;
        }

        .ocr-source-btn:hover {
            border-color: #2563eb;
            background: #eef2ff;
        }

        .ocr-source-btn i {
            font-size: 1.75rem;
            color: #2563eb;
        }

        .ocr-source-btn span {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.9rem;
        }

        .ocr-source-btn small {
            color: #64748b;
            font-size: 0.75rem;
        }

        .ocr-queue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            color: #374151;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .ocr-link-btn {
            background: none;
            border: none;
            color: #2563eb;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            margin-left: 0.5rem;
            font-family: inherit;
        }

        .ocr-link-btn:hover {
            text-decoration: underline;
        }

        .ocr-queue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(84px, 1fr));
            gap: 0.6rem;
            margin-bottom: 1.25rem;
        }

        .ocr-queue-thumb {
            position: relative;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 2px solid #e5e7eb;
            aspect-ratio: 3/4;
            cursor: pointer;
            transition: all 0.2s;
        }

        .ocr-queue-thumb.selected {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .ocr-queue-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            opacity: 0.7;
        }

        .ocr-queue-thumb.selected img {
            opacity: 1;
        }

        .ocr-thumb-check {
            position: absolute;
            top: 5px;
            left: 5px;
            width: 24px;
            height: 24px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
        }

        .ocr-thumb-check input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #2563eb;
        }

        .ocr-thumb-num {
            position: absolute;
            bottom: 5px;
            left: 5px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .ocr-thumb-cropped {
            position: absolute;
            top: 5px;
            right: 35px;
            width: 24px;
            height: 24px;
            background: rgba(22, 163, 74, 0.9);
            color: #fff;
            border-radius: 50%;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        .ocr-thumb-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 24px;
            height: 24px;
            background: rgba(220, 38, 38, 0.9);
            border: none;
            color: #fff;
            border-radius: 50%;
            font-size: 0.7rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        .ocr-thumb-remove:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .ocr-queue-add {
            border: 2px dashed #e5e7eb;
            border-radius: 0.5rem;
            aspect-ratio: 3/4;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #94a3b8;
            font-size: 1.5rem;
        }

        .ocr-queue-add:hover {
            border-color: #2563eb;
            color: #2563eb;
        }

        .ocr-batch-settings {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }

        .ocr-field {
            flex: 1;
            min-width: 160px;
        }

        .ocr-field label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .ocr-field select,
        .ocr-field input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.55rem 0.7rem;
            font-size: 0.875rem;
            font-family: inherit;
            box-sizing: border-box;
        }

        .ocr-field select:focus,
        .ocr-field input:focus {
            outline: none;
            border-color: #2563eb;
        }

        .ocr-hint {
            font-size: 0.8rem;
            color: #64748b;
            margin: 0 0 1rem;
        }

        .ocr-action-row {
            display: flex;
            gap: 0.6rem;
            margin-top: 1rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .ocr-btn {
            padding: 0.65rem 1.2rem;
            border-radius: 0.6rem;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.15s;
            font-family: inherit;
        }

        .ocr-btn-primary {
            background: #16a34a;
            color: #fff;
        }

        .ocr-btn-primary:hover {
            filter: brightness(0.92);
        }

        .ocr-btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .ocr-btn-secondary:hover {
            background: #e2e8f0;
        }

        .ocr-btn-block {
            width: 100%;
            justify-content: center;
        }

        .ocr-loading {
            text-align: center;
            padding: 3rem 1rem;
            color: #64748b;
        }

        .ocr-loading i {
            font-size: 2rem;
            color: #2563eb;
        }

        .ocr-loading p {
            margin: 0.75rem 0;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .ocr-progress-track {
            height: 6px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            max-width: 280px;
            margin: 0 auto;
        }

        .ocr-progress-fill {
            height: 100%;
            width: 0%;
            background: #2563eb;
            transition: width 0.2s;
        }

        .ocr-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .ocr-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.55rem 0.7rem;
            font-size: 0.875rem;
            font-family: inherit;
            box-sizing: border-box;
        }

        .ocr-input:focus {
            outline: none;
            border-color: #2563eb;
        }

        .ocr-textarea {
            resize: vertical;
            margin-bottom: 0.9rem;
        }

        .ocr-card {
            border: 1.5px solid #e5e7eb;
            border-radius: 0.9rem;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .ocr-card[data-status="saved"] {
            border-color: #16a34a;
            background: #f0fdf4;
        }

        .ocr-card[data-status="save-error"] {
            border-color: #dc2626;
            background: #fef2f2;
        }

        .ocr-card-head {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .ocr-card-thumb {
            width: 44px;
            height: 44px;
            object-fit: cover;
            border-radius: 0.4rem;
            border: 1px solid #e5e7eb;
            flex-shrink: 0;
        }

        .ocr-card-headtext {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .ocr-card-index {
            font-weight: 700;
            font-size: 0.85rem;
            color: #1e293b;
        }

        .ocr-card-status {
            font-size: 0.75rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            margin-top: 2px;
        }

        .ocr-card-remove {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.4rem;
        }

        .ocr-card-remove:hover {
            color: #dc2626;
        }

        .ocr-card-body {
            padding: 1rem;
        }

        .ocr-options-grid {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 0.9rem;
        }

        .ocr-opt-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .ocr-opt-letter {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #eef2ff;
            color: #2563eb;
            font-weight: 700;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ocr-opt-row .ocr-input {
            flex: 1;
        }

        .ocr-radio {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.75rem;
            color: #16a34a;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            flex-shrink: 0;
        }

        .ocr-radio input {
            accent-color: #16a34a;
            cursor: pointer;
        }

        .ocr-options-toolbar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 0.5rem;
        }

        .ocr-scan-options-btn {
            padding: 0.4rem 0.8rem;
            font-size: 0.75rem;
        }

        .ocr-card-footer {
            display: flex;
            align-items: center;
            gap: 1rem;
            justify-content: space-between;
        }

        .ocr-card-warn {
            color: #d97706;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .ocr-sticky-footer {
            position: sticky;
            bottom: -1.5rem;
            background: #fff;
            padding: 1rem 0 0.25rem;
            margin-top: 0;
            border-top: 1px solid #e5e7eb;
        }

        .ocr-math-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
        }

        .ocr-math-preview-content {
            margin-top: 5px;
            font-size: 14px;
        }

        .ocr-image-upload {
            border: 2px dashed #dee2e6;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            margin: 15px 0;
        }

        .ocr-image-upload:hover {
            border-color: #2563eb;
            background: #f8f9fa;
        }

        .ocr-image-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 5px;
        }

        /* CROP MODAL STYLES */
        .ocr-crop-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.9);
            z-index: 10060;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .ocr-crop-overlay.active {
            display: flex;
        }

        .ocr-crop-box {
            background: #fff;
            border-radius: 1rem;
            width: 100%;
            max-width: 900px;
            max-height: 95vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
        }

        .ocr-crop-header {
            background: #2563eb;
            color: #fff;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-shrink: 0;
        }

        .ocr-crop-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .ocr-crop-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }

        .ocr-crop-image-wrap {
            max-height: 60vh;
            overflow: hidden;
            border-radius: 0.5rem;
            background: #f1f5f9;
        }

        .ocr-crop-image-wrap img {
            max-width: 100%;
            display: block;
        }

        .ocr-crop-footer {
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 0.6rem;
            justify-content: flex-end;
            flex-shrink: 0;
        }

        .cropper-view-box {
            outline: 2px solid #2563eb !important;
            outline-color: rgba(37, 99, 235, 0.75) !important;
        }

        .cropper-face {
            background-color: rgba(37, 99, 235, 0.1) !important;
        }

        .cropper-line,
        .cropper-point {
            background-color: #2563eb !important;
        }

        @media (max-width: 560px) {
            .ocr-source-grid {
                grid-template-columns: 1fr;
            }

            .ocr-batch-settings {
                flex-direction: column;
            }

            .ocr-queue-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

    <!-- CROP MODAL -->
    <div id="ocrCropModal" class="ocr-crop-overlay">
        <div class="ocr-crop-box">
            <div class="ocr-crop-header">
                <div>
                    <h3><i class="fas fa-crop-alt"></i> <span id="ocrCropTitle">Pilih Area yang Mau Dibaca</span></h3>
                    <p class="ocr-subtitle">Geser dan atur kotak seleksi pada bagian yang ingin diproses</p>
                </div>
                <button type="button" class="ocr-close-btn" onclick="OcrScanner.cancelCrop()"><i class="fas fa-times"></i></button>
            </div>
            <div class="ocr-crop-body">
                <div class="ocr-crop-image-wrap">
                    <img id="ocrCropImage" src="" alt="Crop area">
                </div>
            </div>
            <div class="ocr-crop-footer">
                <button type="button" class="ocr-btn ocr-btn-secondary" onclick="OcrScanner.cancelCrop()"><i class="fas fa-times"></i> Batal</button>
                <button type="button" class="ocr-btn ocr-btn-primary" onclick="OcrScanner.confirmCrop()"><i class="fas fa-check"></i> Gunakan Area Ini</button>
            </div>
        </div>
    </div>

    <!-- MAIN OCR MODAL -->
    <div id="ocrScannerModal" class="ocr-modal-overlay">
        <div class="ocr-modal-box">
            <div class="ocr-modal-header">
                <div>
                    <h3><i class="fas fa-camera-retro"></i> Scan Soal ke Bank Soal</h3>
                    <p class="ocr-subtitle">Tiap gambar dihitung 1 soal. Centang gambar yang mau diproses.</p>
                </div>
                <button type="button" class="ocr-close-btn" onclick="OcrScanner.close()"><i class="fas fa-times"></i></button>
            </div>
            <div class="ocr-modal-body">
                <!-- STEP 0: Source -->
                <div id="ocrStepSource" class="ocr-step">
                    <div class="ocr-source-grid">
                        <button type="button" class="ocr-source-btn" onclick="document.getElementById('ocrCameraInput').click()">
                            <i class="fas fa-camera"></i>
                            <span>Ambil dari Kamera</span>
                            <small>Bisa jepret berkali-kali</small>
                        </button>
                        <button type="button" class="ocr-source-btn" onclick="document.getElementById('ocrGalleryInput').click()">
                            <i class="fas fa-images"></i>
                            <span>Pilih dari Galeri</span>
                            <small>Bisa pilih banyak gambar sekaligus</small>
                        </button>
                    </div>

                    <input type="file" id="ocrCameraInput" accept="image/*" capture="environment" hidden>
                    <input type="file" id="ocrGalleryInput" accept="image/*" multiple hidden>

                    <div id="ocrQueueWrap" style="display:none;">
                        <div class="ocr-queue-header">
                            <span><i class="fas fa-layer-group"></i> <strong id="ocrQueueCount">0</strong> gambar total | <strong id="ocrSelectedCount">0</strong> dipilih</span>
                            <div>
                                <button type="button" class="ocr-link-btn" onclick="OcrScanner.selectAllImages(true)">Pilih Semua</button>
                                <button type="button" class="ocr-link-btn" onclick="OcrScanner.selectAllImages(false)">Batal Semua</button>
                                <button type="button" class="ocr-link-btn" onclick="OcrScanner.clearQueue()">Kosongkan</button>
                            </div>
                        </div>
                        <div id="ocrQueueGrid" class="ocr-queue-grid"></div>
                        <div class="ocr-batch-settings">
                            <div class="ocr-field">
                                <label>Mata Pelajaran</label>
                                <select id="ocrSubjectSelect">
                                    <option value="">Memuat daftar mapel...</option>
                                </select>
                            </div>
                            <div class="ocr-field" style="max-width:140px;">
                                <label>Poin per Soal</label>
                                <input type="number" id="ocrDefaultPoints" value="1" min="1" step="1">
                            </div>
                        </div>
                        <div class="ocr-action-row">
                            <button type="button" class="ocr-btn ocr-btn-primary ocr-btn-block" onclick="OcrScanner.startProcessing()">
                                <i class="fas fa-magic"></i> Proses Gambar Terpilih
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STEP 1: Loading -->
                <div id="ocrLoading" class="ocr-loading" style="display:none;">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p id="ocrLoadingText">Membaca gambar 1 dari 1...</p>
                    <div class="ocr-progress-track">
                        <div id="ocrProgressBar" class="ocr-progress-fill"></div>
                    </div>
                </div>

                <!-- STEP 2: Review -->
                <div id="ocrStepReview" class="ocr-step" style="display:none;">
                    <p class="ocr-hint"><i class="fas fa-info-circle"></i> Cek/koreksi teks tiap soal, lalu <strong>pilih jawaban yang benar</strong> untuk masing-masing sebelum disimpan.</p>
                    <div id="ocrReviewList"></div>
                    <div class="ocr-action-row ocr-sticky-footer">
                        <button type="button" class="ocr-btn ocr-btn-secondary" onclick="OcrScanner.backToSource()"><i class="fas fa-arrow-left"></i> Kembali</button>
                        <button type="button" class="ocr-btn ocr-btn-primary" onclick="OcrScanner.saveAll()"><i class="fas fa-save"></i> Simpan Semua ke Bank Soal (<span id="ocrSaveCount">0</span>)</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="file" id="ocrOptionScanInput" accept="image/*" hidden>
    <input type="file" id="ocrShapeImageInput" accept="image/*" hidden>

    <!-- TEMPLATE KARTU REVIEW -->
    <template id="ocrCardTemplate">
        <div class="ocr-card" data-status="pending">
            <div class="ocr-card-head">
                <img class="ocr-card-thumb" src="" alt="">
                <div class="ocr-card-headtext">
                    <span class="ocr-card-index">Soal #<span class="ocr-idx-num"></span></span>
                    <span class="ocr-card-status"><i class="fas fa-circle ocr-status-icon"></i><span class="ocr-status-text">Siap ditinjau</span></span>
                </div>
                <button type="button" class="ocr-card-remove" title="Hapus soal ini"><i class="fas fa-trash"></i></button>
            </div>
            <div class="ocr-card-body">
                <label class="ocr-label">Teks Soal</label>
                <textarea class="ocr-input ocr-textarea ocr-field-question" rows="4" placeholder="Teks soal... Gunakan \(...\) untuk rumus inline, $$...$$ untuk rumus display"></textarea>
                <div class="ocr-math-preview" style="display:none; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Preview:</strong>
                    <div class="ocr-math-preview-content"></div>
                </div>
                <div class="ocr-image-upload">
                    <label class="ocr-label">Gambar Bangun/Tabel (opsional) - Klik untuk upload dengan crop</label>
                    <input type="file" class="ocr-field-image" accept="image/*" style="display: none;">
                    <button type="button" class="ocr-btn ocr-btn-secondary ocr-upload-image-btn" style="width: 100%;">
                        <i class="fas fa-image"></i> Upload & Crop Gambar
                    </button>
                    <div class="ocr-image-preview" style="margin-top: 10px; display: none;">
                        <img src="" alt="Preview" style="max-width: 100%; max-height: 200px; border: 1px solid #ddd; border-radius: 5px;">
                        <button type="button" class="ocr-btn ocr-btn-secondary" style="margin-top: 5px; font-size: 0.75rem;" onclick="OcrScanner.recropShapeImage(this)">
                            <i class="fas fa-crop-alt"></i> Crop Ulang
                        </button>
                        <button type="button" class="ocr-remove-image-btn" style="margin-top: 5px; background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                            <i class="fas fa-times"></i> Hapus Gambar
                        </button>
                    </div>
                </div>
                <div class="ocr-options-toolbar">
                    <button type="button" class="ocr-btn ocr-btn-secondary ocr-scan-options-btn"><i class="fas fa-camera"></i> Scan Opsi dari Foto</button>
                </div>
                <div class="ocr-options-grid">
                    <div class="ocr-opt-row"><span class="ocr-opt-letter">A</span><input type="text" class="ocr-input ocr-field-a" placeholder="Opsi A (support LaTeX)"><label class="ocr-radio"><input type="radio" name="" class="ocr-correct-radio" value="A"><span>Benar</span></label></div>
                    <div class="ocr-opt-row"><span class="ocr-opt-letter">B</span><input type="text" class="ocr-input ocr-field-b" placeholder="Opsi B (support LaTeX)"><label class="ocr-radio"><input type="radio" name="" class="ocr-correct-radio" value="B"><span>Benar</span></label></div>
                    <div class="ocr-opt-row"><span class="ocr-opt-letter">C</span><input type="text" class="ocr-input ocr-field-c" placeholder="Opsi C (support LaTeX)"><label class="ocr-radio"><input type="radio" name="" class="ocr-correct-radio" value="C"><span>Benar</span></label></div>
                    <div class="ocr-opt-row"><span class="ocr-opt-letter">D</span><input type="text" class="ocr-input ocr-field-d" placeholder="Opsi D (support LaTeX)"><label class="ocr-radio"><input type="radio" name="" class="ocr-correct-radio" value="D"><span>Benar</span></label></div>
                    <div class="ocr-opt-row"><span class="ocr-opt-letter">E</span><input type="text" class="ocr-input ocr-field-e" placeholder="Opsi E (opsional)"><label class="ocr-radio"><input type="radio" name="" class="ocr-correct-radio" value="E"><span>Benar</span></label></div>
                </div>
                <div class="ocr-card-footer">
                    <div class="ocr-field" style="max-width:120px;"><label>Poin</label><input type="number" class="ocr-input ocr-field-points" value="1" min="1" step="1"></div>
                    <div class="ocr-card-warn" style="display:none;"><i class="fas fa-exclamation-triangle"></i> Pilih jawaban benar dulu</div>
                </div>
            </div>
        </div>
    </template>

    <script>
        // ANTI-DUPLIKASI: Hanya deklarasi sekali
        if (typeof window.OcrScanner === 'undefined') {

            window.OcrScanner = (function() {
                // Deteksi Base URL yang benar
                let baseUrl = '';
                if (window.APP_URL) {
                    baseUrl = window.APP_URL.replace(/\/$/, '');
                } else {
                    const meta = document.querySelector('meta[name="app-url"]');
                    if (meta) {
                        baseUrl = meta.content.replace(/\/$/, '');
                    } else {
                        const path = window.location.pathname;
                        const parts = path.split('/').filter(p => p);
                        const basePath = parts.length > 0 ? '/' + parts[0] : '';
                        baseUrl = window.location.origin + basePath;
                    }
                }
                console.log('OCR Scanner Base URL:', baseUrl);

                const assetBaseUrl = (window.ASSET_BASE_URL || `${baseUrl}/assets`).replace(/\/$/, '');
                const tesseractOptions = () => ({
                    workerPath: `${assetBaseUrl}/vendor/tesseract.js/5.1.1/dist/worker.min.js`,
                    corePath: `${assetBaseUrl}/vendor/tesseract.js/5.1.1/core`,
                    langPath: `${assetBaseUrl}/vendor/tesseract.js/5.1.1/lang`,
                    gzip: true
                });

                let queue = [];
                let selectedIds = new Set();
                let results = [];
                let queueIdCounter = 0;
                let subjectsLoaded = false;
                let bound = false;

                let cropper = null;
                let pendingCropFile = null;
                let pendingCropId = null;
                let pendingFiles = [];
                let currentFileIndex = 0;
                let cropMode = 'queue'; // 'queue', 'option', atau 'shape'
                let optionScanCardEl = null;
                let shapeImageCardEl = null;
                let shapeImageInputEl = null;

                const fallbackSubjects = [{
                        id: '1',
                        name: 'Matematika'
                    },
                    {
                        id: '2',
                        name: 'Bahasa Indonesia'
                    },
                    {
                        id: '3',
                        name: 'Bahasa Inggris'
                    },
                    {
                        id: '4',
                        name: 'IPA'
                    },
                    {
                        id: '5',
                        name: 'IPS'
                    },
                ];

                function open() {
                    document.getElementById('ocrScannerModal').classList.add('active');
                    backToSource();
                    if (!subjectsLoaded) loadSubjects();
                    bindEventsOnce();
                }

                function close() {
                    document.getElementById('ocrScannerModal').classList.remove('active');
                    clearQueue();
                    results = [];
                }

                function backToSource() {
                    document.getElementById('ocrStepSource').style.display = 'block';
                    document.getElementById('ocrLoading').style.display = 'none';
                    document.getElementById('ocrStepReview').style.display = 'none';
                }

                function clearQueue() {
                    queue = [];
                    selectedIds.clear();
                    renderQueue();
                }

                function loadSubjects() {
                    const sel = document.getElementById('ocrSubjectSelect');
                    fetch(baseUrl + '/question/ocrSubjects', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(r => {
                            if (!r.ok) throw new Error('Endpoint tidak ditemukan');
                            return r.json();
                        })
                        .then(data => {
                            if (!data.success || !data.subjects || !data.subjects.length) {
                                throw new Error('Tidak ada data mapel');
                            }
                            sel.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>' +
                                data.subjects.map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
                            subjectsLoaded = true;
                        })
                        .catch((err) => {
                            console.warn('Gagal load mapel dari server, pakai fallback:', err.message);
                            sel.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>' +
                                fallbackSubjects.map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
                            subjectsLoaded = true;
                        });
                }

                function bindEventsOnce() {
                    if (bound) return;
                    bound = true;

                    document.getElementById('ocrCameraInput').addEventListener('change', (e) => {
                        handleFileSelect(e.target.files);
                        e.target.value = '';
                    });

                    document.getElementById('ocrGalleryInput').addEventListener('change', (e) => {
                        handleFileSelect(e.target.files);
                        e.target.value = '';
                    });

                    const optionInput = document.getElementById('ocrOptionScanInput');
                    if (optionInput) {
                        optionInput.addEventListener('change', (e) => {
                            const file = e.target.files[0];
                            e.target.value = '';
                            if (!file || !file.type.startsWith('image/')) return;
                            handleOptionScanFile(file);
                        });
                    }

                    const shapeInput = document.getElementById('ocrShapeImageInput');
                    if (shapeInput) {
                        shapeInput.addEventListener('change', (e) => {
                            const file = e.target.files[0];
                            e.target.value = '';
                            if (!file || !file.type.startsWith('image/')) return;
                            handleShapeImageFile(file);
                        });
                    }

                    const reviewList = document.getElementById('ocrReviewList');
                    if (reviewList) {
                        reviewList.addEventListener('click', (e) => {
                            const btn = e.target.closest('.ocr-scan-options-btn');
                            if (!btn) return;
                            optionScanCardEl = btn.closest('.ocr-card');
                            const optionInputEl = document.getElementById('ocrOptionScanInput');
                            if (optionInputEl) optionInputEl.click();

                            // Handle upload shape image button
                            const uploadBtn = e.target.closest('.ocr-upload-image-btn');
                            if (uploadBtn) {
                                shapeImageCardEl = uploadBtn.closest('.ocr-card');
                                shapeImageInputEl = uploadBtn.closest('.ocr-image-upload').querySelector('.ocr-field-image');
                                const shapeInputEl = document.getElementById('ocrShapeImageInput');
                                if (shapeInputEl) shapeInputEl.click();
                            }
                        });
                    }
                }

                function handleFileSelect(fileList) {
                    const files = Array.from(fileList || []).filter(f => f.type.startsWith('image/'));
                    if (!files.length) return;
                    pendingFiles = files;
                    currentFileIndex = 0;
                    cropMode = 'queue';
                    showCropForNextFile();
                }

                function handleOptionScanFile(file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        cropMode = 'option';
                        pendingCropFile = file;
                        pendingCropId = null;
                        const cropImage = document.getElementById('ocrCropImage');
                        cropImage.src = e.target.result;
                        document.getElementById('ocrCropTitle').textContent = 'Pilih Area Opsi Jawaban';
                        document.getElementById('ocrCropModal').classList.add('active');
                        setTimeout(() => {
                            if (cropper) cropper.destroy();
                            cropper = new Cropper(cropImage, {
                                aspectRatio: NaN,
                                viewMode: 1,
                                dragMode: 'move',
                                autoCropArea: 0.9,
                                restore: false,
                                guides: true,
                                center: true,
                                highlight: true,
                                cropBoxMovable: true,
                                cropBoxResizable: true,
                                toggleDragModeOnDblclick: false,
                                background: true,
                            });
                        }, 100);
                    };
                    reader.readAsDataURL(file);
                }

                function handleShapeImageFile(file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        cropMode = 'shape';
                        pendingCropFile = file;
                        pendingCropId = null;
                        const cropImage = document.getElementById('ocrCropImage');
                        cropImage.src = e.target.result;
                        document.getElementById('ocrCropTitle').textContent = 'Pilih Area Gambar Bangun/Tabel';
                        document.getElementById('ocrCropModal').classList.add('active');
                        setTimeout(() => {
                            if (cropper) cropper.destroy();
                            cropper = new Cropper(cropImage, {
                                aspectRatio: NaN,
                                viewMode: 1,
                                dragMode: 'move',
                                autoCropArea: 0.9,
                                restore: false,
                                guides: true,
                                center: true,
                                highlight: true,
                                cropBoxMovable: true,
                                cropBoxResizable: true,
                                toggleDragModeOnDblclick: false,
                                background: true,
                            });
                        }, 100);
                    };
                    reader.readAsDataURL(file);
                }

                function showCropForNextFile() {
                    if (currentFileIndex >= pendingFiles.length) {
                        pendingFiles = [];
                        currentFileIndex = 0;
                        return;
                    }
                    const file = pendingFiles[currentFileIndex];
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        pendingCropFile = file;
                        pendingCropId = ++queueIdCounter;
                        const cropImage = document.getElementById('ocrCropImage');
                        cropImage.src = e.target.result;
                        document.getElementById('ocrCropTitle').textContent = 'Pilih Area Soal';
                        document.getElementById('ocrCropModal').classList.add('active');
                        setTimeout(() => {
                            if (cropper) cropper.destroy();
                            cropper = new Cropper(cropImage, {
                                aspectRatio: NaN,
                                viewMode: 1,
                                dragMode: 'move',
                                autoCropArea: 0.9,
                                restore: false,
                                guides: true,
                                center: true,
                                highlight: true,
                                cropBoxMovable: true,
                                cropBoxResizable: true,
                                toggleDragModeOnDblclick: false,
                                background: true,
                            });
                        }, 100);
                    };
                    reader.readAsDataURL(file);
                }

                function cancelCrop() {
                    document.getElementById('ocrCropModal').classList.remove('active');
                    if (cropper) {
                        cropper.destroy();
                        cropper = null;
                    }
                    pendingCropFile = null;
                    pendingCropId = null;

                    if (cropMode === 'option') {
                        cropMode = 'queue';
                        optionScanCardEl = null;
                        return;
                    }

                    if (cropMode === 'shape') {
                        cropMode = 'queue';
                        shapeImageCardEl = null;
                        shapeImageInputEl = null;
                        return;
                    }

                    currentFileIndex++;
                    showCropForNextFile();
                }

                function confirmCrop() {
                    if (!cropper) return;
                    const canvas = cropper.getCroppedCanvas({
                        maxWidth: 2048,
                        maxHeight: 2048,
                        imageSmoothingEnabled: true,
                        imageSmoothingQuality: 'high',
                    });
                    if (!canvas) {
                        alert('Gagal memproses gambar');
                        cancelCrop();
                        return;
                    }
                    const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.9);

                    if (cropMode === 'option') {
                        if (cropper) {
                            cropper.destroy();
                            cropper = null;
                        }
                        pendingCropFile = null;
                        document.getElementById('ocrCropModal').classList.remove('active');
                        const targetCard = optionScanCardEl;
                        cropMode = 'queue';
                        optionScanCardEl = null;
                        processOptionScan(croppedDataUrl, targetCard);
                        return;
                    }

                    if (cropMode === 'shape') {
                        if (cropper) {
                            cropper.destroy();
                            cropper = null;
                        }
                        pendingCropFile = null;
                        document.getElementById('ocrCropModal').classList.remove('active');

                        // Set cropped image to the card
                        if (shapeImageCardEl) {
                            const imagePreview = shapeImageCardEl.querySelector('.ocr-image-preview');
                            const imagePreviewImg = imagePreview.querySelector('img');
                            const uploadBtn = shapeImageCardEl.querySelector('.ocr-upload-image-btn');

                            imagePreviewImg.src = croppedDataUrl;
                            imagePreview.style.display = 'block';
                            if (uploadBtn) uploadBtn.style.display = 'none';
                            shapeImageCardEl.dataset.shapeImageData = croppedDataUrl;
                        }

                        cropMode = 'queue';
                        shapeImageCardEl = null;
                        shapeImageInputEl = null;
                        return;
                    }

                    queue.push({
                        id: pendingCropId,
                        file: pendingCropFile,
                        dataUrl: croppedDataUrl,
                        isCropped: true
                    });
                    selectedIds.add(pendingCropId);
                    if (cropper) {
                        cropper.destroy();
                        cropper = null;
                    }
                    pendingCropFile = null;
                    pendingCropId = null;
                    document.getElementById('ocrCropModal').classList.remove('active');
                    renderQueue();
                    currentFileIndex++;
                    showCropForNextFile();
                }

                function recropShapeImage(btn) {
                    const card = btn.closest('.ocr-card');
                    const currentDataUrl = card.dataset.shapeImageData;

                    if (currentDataUrl) {
                        cropMode = 'shape';
                        pendingCropFile = null;
                        pendingCropId = null;
                        shapeImageCardEl = card;

                        const cropImage = document.getElementById('ocrCropImage');
                        cropImage.src = currentDataUrl;
                        document.getElementById('ocrCropTitle').textContent = 'Crop Ulang Gambar Bangun/Tabel';
                        document.getElementById('ocrCropModal').classList.add('active');

                        setTimeout(() => {
                            if (cropper) cropper.destroy();
                            cropper = new Cropper(cropImage, {
                                aspectRatio: NaN,
                                viewMode: 1,
                                dragMode: 'move',
                                autoCropArea: 0.9,
                                restore: false,
                                guides: true,
                                center: true,
                                highlight: true,
                                cropBoxMovable: true,
                                cropBoxResizable: true,
                                toggleDragModeOnDblclick: false,
                                background: true,
                            });
                        }, 100);
                    }
                }

                function processOptionScan(dataUrl, cardEl) {
                    if (!cardEl) return;
                    const statusIcon = cardEl.querySelector('.ocr-status-icon');
                    const statusText = cardEl.querySelector('.ocr-status-text');
                    const scanBtn = cardEl.querySelector('.ocr-scan-options-btn');
                    const prevIconClass = statusIcon ? statusIcon.className : '';
                    const prevText = statusText ? statusText.textContent : '';
                    if (statusIcon) statusIcon.className = 'fas fa-circle-notch fa-spin ocr-status-icon';
                    if (statusText) statusText.textContent = 'Membaca opsi dari foto...';
                    if (scanBtn) scanBtn.disabled = true;

                    Promise.resolve(preprocessImageForOCR(dataUrl))
                        .then(preprocessedDataUrl => Tesseract.recognize(preprocessedDataUrl, 'ind+eng', tesseractOptions()))
                        .then(({
                            data
                        }) => {
                            console.log('=== RAW OPTION OCR ===', data.text);
                            const options = extractOptionsFromPhoto(data.text || '');
                            let filledAny = false;
                            ['a', 'b', 'c', 'd', 'e'].forEach(letter => {
                                const field = cardEl.querySelector('.ocr-field-' + letter);
                                if (field && options[letter]) {
                                    field.value = options[letter];
                                    filledAny = true;
                                }
                            });
                            if (!filledAny) {
                                alert('Tidak ada opsi yang terbaca.');
                            }
                        })
                        .catch((err) => {
                            console.error('OCR Option Error:', err);
                            alert('Gagal membaca opsi.');
                        })
                        .finally(() => {
                            if (statusIcon) statusIcon.className = prevIconClass;
                            if (statusText) statusText.textContent = prevText;
                            if (scanBtn) scanBtn.disabled = false;
                        });
                }

                function startProcessing() {
                    if (!queue.length) {
                        alert('Tidak ada gambar untuk diproses');
                        return;
                    }
                    if (!selectedIds.size) {
                        alert('Pilih minimal 1 gambar');
                        return;
                    }
                    const subjectId = document.getElementById('ocrSubjectSelect').value;
                    if (!subjectId) {
                        alert('Pilih mata pelajaran dulu');
                        return;
                    }
                    const defaultPoints = parseInt(document.getElementById('ocrDefaultPoints').value, 10) || 1;
                    const selectedQueue = queue.filter(item => selectedIds.has(item.id));

                    document.getElementById('ocrStepSource').style.display = 'none';
                    document.getElementById('ocrLoading').style.display = 'block';

                    results = [];
                    const total = selectedQueue.length;
                    let done = 0;

                    function processNext() {
                        if (done >= total) {
                            finishProcessing(subjectId, defaultPoints);
                            return;
                        }
                        const item = selectedQueue[done];
                        document.getElementById('ocrLoadingText').textContent = `Membaca gambar ${done + 1} dari ${total}...`;
                        document.getElementById('ocrProgressBar').style.width = Math.round((done / total) * 100) + '%';

                        Tesseract.recognize(item.dataUrl, 'ind+eng', {
                                ...tesseractOptions(),
                                logger: (m) => {
                                    if (m.status === 'recognizing text') {
                                        const pct = Math.round(((done + m.progress) / total) * 100);
                                        document.getElementById('ocrProgressBar').style.width = pct + '%';
                                    }
                                },
                            })
                            .then(({
                                data
                            }) => {
                                console.log(`\n--- HASIL OCR GAMBAR ${done + 1} ---`);
                                console.log('RAW:', data.text);
                                console.log('CONFIDENCE:', data.confidence);

                                const parsed = parseQuestionText(data.text || '');
                                console.log('PARSED:', parsed);

                                results.push({
                                    id: item.id,
                                    imageDataUrl: item.dataUrl,
                                    question: parsed.question || '(Teks tidak terbaca)',
                                    a: parsed.a || '',
                                    b: parsed.b || '',
                                    c: parsed.c || '',
                                    d: parsed.d || '',
                                    e: parsed.e || '',
                                    correct: parsed.correct || '',
                                    points: defaultPoints,
                                    status: 'pending',
                                });
                            })
                            .catch((err) => {
                                console.error('OCR Error:', err);
                                results.push({
                                    id: item.id,
                                    imageDataUrl: item.dataUrl,
                                    question: '(Gagal membaca teks)',
                                    a: '',
                                    b: '',
                                    c: '',
                                    d: '',
                                    e: '',
                                    correct: '',
                                    points: defaultPoints,
                                    status: 'pending',
                                });
                            })
                            .finally(() => {
                                done++;
                                processNext();
                            });
                    }
                    processNext();
                }

                function finishProcessing(subjectId, defaultPoints) {
                    document.getElementById('ocrLoading').style.display = 'none';
                    document.getElementById('ocrStepReview').style.display = 'block';
                    document.getElementById('ocrStepReview').dataset.subjectId = subjectId;
                    renderReviewList();
                }

                function preprocessImageForOCR(dataUrl) {
                    return new Promise((resolve) => {
                        const img = new Image();
                        img.onload = () => {
                            const canvas = document.createElement('canvas');
                            const ctx = canvas.getContext('2d', {
                                willReadFrequently: true
                            });
                            const maxWidth = 1200;
                            const scale = Math.min(1, maxWidth / img.width);
                            canvas.width = Math.max(1, Math.round(img.width * scale));
                            canvas.height = Math.max(1, Math.round(img.height * scale));
                            ctx.filter = 'grayscale(100%) contrast(150%) brightness(100%)';
                            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                            const pixels = imageData.data;
                            const threshold = 130;
                            for (let i = 0; i < pixels.length; i += 4) {
                                const avg = (pixels[i] + pixels[i + 1] + pixels[i + 2]) / 3;
                                const value = avg > threshold ? 255 : 0;
                                pixels[i] = value;
                                pixels[i + 1] = value;
                                pixels[i + 2] = value;
                            }
                            ctx.putImageData(imageData, 0, 0);
                            resolve(canvas.toDataURL('image/jpeg', 0.9));
                        };
                        img.onerror = () => resolve(dataUrl);
                        img.src = dataUrl;
                    });
                }

                function extractCorrectAnswer(text) {
                    if (!text) return null;
                    const patterns = [
                        /Jawaban:\s*([A-Ea-e])/i, /Kunci:\s*([A-Ea-e])/i,
                        /Answer:\s*([A-Ea-e])/i, /Benar:\s*([A-Ea-e])/i,
                    ];
                    for (const pattern of patterns) {
                        const match = text.match(pattern);
                        if (match) return match[1].toUpperCase();
                    }
                    const bracketMatch = text.match(/[\(\[]\s*([A-Ea-e])\s*[\)\]]/i);
                    if (bracketMatch) return bracketMatch[1].toUpperCase();
                    return null;
                }

                function extractOptionsFromPhoto(rawText) {
                    const options = {
                        a: '',
                        b: '',
                        c: '',
                        d: '',
                        e: ''
                    };
                    if (!rawText) return options;
                    const text = rawText.replace(/\r/g, ' ').replace(/\s+/g, ' ');
                    const boundaryRegex = /(?:^|[\s])([A-Ea-e])\s*[\.\)\:]\s*(?=\S)/g;
                    const boundaries = [];
                    let m;
                    while ((m = boundaryRegex.exec(text)) !== null) {
                        boundaries.push({
                            letter: m[1].toLowerCase(),
                            matchStart: m.index,
                            valueStart: boundaryRegex.lastIndex
                        });
                    }
                    if (!boundaries.length) return options;
                    boundaries.forEach((b, i) => {
                        const end = i + 1 < boundaries.length ? boundaries[i + 1].matchStart : text.length;
                        let value = text.slice(b.valueStart, end);
                        value = value.replace(/\s+/g, ' ').trim();
                        if (value && value.length > 2 && value.length < 150 && !options[b.letter]) {
                            options[b.letter] = value;
                        }
                    });
                    return options;
                }

                function extractOptions(text) {
                    const options = {
                        a: '',
                        b: '',
                        c: '',
                        d: '',
                        e: ''
                    };
                    if (!text) return options;

                    const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 2);

                    lines.forEach((line) => {
                        const match = line.match(/^([A-Ea-e])[\.\)]\s+(.+)$/);
                        if (match) {
                            const letter = match[1].toLowerCase();
                            let value = match[2].trim();
                            value = value.replace(/[^\w\s.,;:'"()\-\u00C0-\u024F]/g, ' ');
                            value = value.replace(/\s+/g, ' ').trim();
                            if (value.length >= 2 && value.length <= 150 && !options[letter]) {
                                options[letter] = value;
                            }
                        }
                    });

                    const inlineRegex = /(?:^|\s)([A-Ea-e])[\.\)]\s*([^\nA-Ea-e]{2,100}?)(?=\s+[A-Ea-e][\.\)]|$)/g;
                    let inlineMatch;
                    while ((inlineMatch = inlineRegex.exec(text)) !== null) {
                        const letter = inlineMatch[1].toLowerCase();
                        let value = inlineMatch[2].trim();
                        value = value.replace(/[^\w\s.,;:'"()\-\u00C0-\u024F]/g, '');
                        value = value.trim();
                        if (value.length >= 2 && value.length <= 150 && !options[letter]) {
                            options[letter] = value;
                        }
                    }

                    return options;
                }

                function parseQuestionText(rawText) {
                    if (!rawText || rawText.trim().length < 10) {
                        return {
                            question: '(Teks terlalu pendek)',
                            a: '',
                            b: '',
                            c: '',
                            d: '',
                            e: '',
                            correct: ''
                        };
                    }

                    let text = rawText.replace(/[^\w\s.,;:'"()\-\u00C0-\u024F]/g, ' ');
                    text = text.replace(/\s+/g, ' ').trim();

                    const options = extractOptions(text);
                    const correctAnswer = extractCorrectAnswer(text);

                    let questionText = text;
                    questionText = questionText.replace(/\s+[A-Ea-e][\.\)]\s+\S+/gi, '');
                    questionText = questionText.replace(/\s+[A-Ea-e][\.\)]\s*[0-9a-z\s,;:()]+?(?=\s+[A-Ea-e][\.\)]|$)/gi, '');
                    questionText = questionText.replace(/\s*(Jawaban|Kunci|Answer)\s*[:=\-]?\s*[A-Ea-e]?\.?\s*$/i, '');
                    questionText = questionText.replace(/^\s*\d+[\.\)]\s+/, '');
                    questionText = questionText.replace(/\s+/g, ' ').trim();
                    questionText = questionText.replace(/\.\.\.$/, '').trim();

                    if (questionText.length < 30) {
                        const firstOptionMatch = text.match(/\s+[A-Ea-e][\.\)]\s+/);
                        if (firstOptionMatch) {
                            questionText = text.substring(0, firstOptionMatch.index).trim();
                        }
                    }

                    return {
                        question: questionText || '(Teks soal tidak terbaca)',
                        a: options.a || '',
                        b: options.b || '',
                        c: options.c || '',
                        d: options.d || '',
                        e: options.e || '',
                        correct: correctAnswer || ''
                    };
                }

                function toggleImageSelection(id) {
                    if (selectedIds.has(id)) selectedIds.delete(id);
                    else selectedIds.add(id);
                    renderQueue();
                }

                function selectAllImages(select = true) {
                    if (select) queue.forEach(item => selectedIds.add(item.id));
                    else selectedIds.clear();
                    renderQueue();
                }

                function removeFromQueue(id) {
                    queue = queue.filter(i => i.id !== id);
                    selectedIds.delete(id);
                    renderQueue();
                }

                function renderQueue() {
                    const wrap = document.getElementById('ocrQueueWrap');
                    const grid = document.getElementById('ocrQueueGrid');
                    const count = document.getElementById('ocrQueueCount');
                    const selectedCount = document.getElementById('ocrSelectedCount');
                    wrap.style.display = queue.length ? 'block' : 'none';
                    count.textContent = queue.length;
                    selectedCount.textContent = selectedIds.size;
                    if (!queue.length) {
                        grid.innerHTML = '';
                        return;
                    }
                    grid.innerHTML = queue.map((item, idx) => {
                        const isSelected = selectedIds.has(item.id);
                        return `<div class="ocr-queue-thumb ${isSelected ? 'selected' : ''}">
                                <img src="${item.dataUrl}" alt="">
                                <span class="ocr-thumb-num">${idx + 1}</span>
                                ${item.isCropped ? '<span class="ocr-thumb-cropped"><i class="fas fa-crop-alt"></i></span>' : ''}
                                <label class="ocr-thumb-check"><input type="checkbox" ${isSelected ? 'checked' : ''} 
                                       onchange="OcrScanner.toggleImageSelection(${item.id})"></label>
                                <button type="button" class="ocr-thumb-remove" onclick="OcrScanner.removeFromQueue(${item.id})">
                                    <i class="fas fa-times"></i></button>
                            </div>`;
                    }).join('') + `<div class="ocr-queue-add" onclick="document.getElementById('ocrGalleryInput').click()">
                            <i class="fas fa-plus"></i></div>`;
                }

                function renderReviewList() {
                    const list = document.getElementById('ocrReviewList');
                    const tpl = document.getElementById('ocrCardTemplate');
                    list.innerHTML = '';

                    results.forEach((r, idx) => {
                        const node = tpl.content.cloneNode(true);
                        const card = node.querySelector('.ocr-card');
                        card.dataset.resultId = r.id;

                        node.querySelector('.ocr-card-thumb').src = r.imageDataUrl;
                        node.querySelector('.ocr-idx-num').textContent = idx + 1;
                        node.querySelector('.ocr-status-text').textContent = 'Siap ditinjau';
                        node.querySelector('.ocr-status-icon').className = 'fas fa-circle ocr-status-icon';

                        node.querySelector('.ocr-field-question').value = r.question;
                        node.querySelector('.ocr-field-a').value = r.a;
                        node.querySelector('.ocr-field-b').value = r.b;
                        node.querySelector('.ocr-field-c').value = r.c;
                        node.querySelector('.ocr-field-d').value = r.d;
                        node.querySelector('.ocr-field-e').value = r.e;
                        node.querySelector('.ocr-field-points').value = r.points;

                        // Setup MathJax preview
                        const questionField = node.querySelector('.ocr-field-question');
                        const mathPreview = node.querySelector('.ocr-math-preview');
                        const mathPreviewContent = node.querySelector('.ocr-math-preview-content');

                        questionField.addEventListener('input', () => {
                            const text = questionField.value;
                            if (text.includes('\\(') || text.includes('\\[') || text.includes('$$') || text.includes('$')) {
                                mathPreview.style.display = 'block';
                                mathPreviewContent.innerHTML = text;

                                setTimeout(() => {
                                    if (window.MathJax) {
                                        MathJax.typesetPromise([mathPreviewContent]).catch((err) => {
                                            console.error('MathJax error:', err);
                                        });
                                    }
                                }, 100);
                            } else {
                                mathPreview.style.display = 'none';
                            }
                        });

                        if (r.question && (r.question.includes('\\(') || r.question.includes('$$'))) {
                            mathPreview.style.display = 'block';
                            mathPreviewContent.innerHTML = r.question;
                            setTimeout(() => {
                                if (window.MathJax) {
                                    MathJax.typesetPromise([mathPreviewContent]).catch(console.error);
                                }
                            }, 100);
                        }

                        // Setup image upload dengan crop
                        const uploadBtn = node.querySelector('.ocr-upload-image-btn');
                        const imageInput = node.querySelector('.ocr-field-image');
                        const imagePreview = node.querySelector('.ocr-image-preview');

                        if (uploadBtn && imageInput && imagePreview) {
                            const imagePreviewImg = imagePreview.querySelector('img');
                            const removeImageBtn = node.querySelector('.ocr-remove-image-btn');

                            uploadBtn.addEventListener('click', () => {
                                shapeImageCardEl = card;
                                shapeImageInputEl = imageInput;
                                const shapeInputEl = document.getElementById('ocrShapeImageInput');
                                if (shapeInputEl) shapeInputEl.click();
                            });

                            imageInput.addEventListener('change', (e) => {
                                const file = e.target.files[0];
                                if (file) {
                                    handleShapeImageFile(file);
                                }
                            });

                            if (removeImageBtn) {
                                removeImageBtn.addEventListener('click', () => {
                                    imagePreview.style.display = 'none';
                                    imageInput.value = '';
                                    delete card.dataset.shapeImageData;
                                    if (uploadBtn) uploadBtn.style.display = 'block';
                                });
                            }
                        }

                        node.querySelectorAll('.ocr-correct-radio').forEach(radio => {
                            radio.name = 'ocr_correct_' + r.id;
                        });

                        node.querySelector('.ocr-card-remove').addEventListener('click', () => {
                            results = results.filter(x => x.id !== r.id);
                            renderReviewList();
                        });

                        list.appendChild(node);
                    });

                    document.getElementById('ocrSaveCount').textContent = results.length;

                    setTimeout(() => {
                        if (window.MathJax) {
                            MathJax.typesetPromise().catch(console.error);
                        }
                    }, 200);
                }

                function collectCardData(cardEl) {
                    const id = parseInt(cardEl.dataset.resultId, 10);
                    const checkedRadio = cardEl.querySelector('.ocr-correct-radio:checked');
                    const imageData = cardEl.dataset.imageData || '';
                    const shapeImageData = cardEl.dataset.shapeImageData || '';
                    return {
                        id,
                        question: cardEl.querySelector('.ocr-field-question').value.trim(),
                        a: cardEl.querySelector('.ocr-field-a').value.trim(),
                        b: cardEl.querySelector('.ocr-field-b').value.trim(),
                        c: cardEl.querySelector('.ocr-field-c').value.trim(),
                        d: cardEl.querySelector('.ocr-field-d').value.trim(),
                        e: cardEl.querySelector('.ocr-field-e').value.trim(),
                        points: parseInt(cardEl.querySelector('.ocr-field-points').value, 10) || 1,
                        correct: checkedRadio ? checkedRadio.value : '',
                        image: imageData,
                        shape_image: shapeImageData
                    };
                }

                function saveAll() {
                    const subjectId = document.getElementById('ocrStepReview').dataset.subjectId;
                    const cards = Array.from(document.querySelectorAll('#ocrReviewList .ocr-card'));

                    if (!cards.length) return;

                    let hasMissing = false;
                    const questionsData = [];

                    cards.forEach((cardEl, idx) => {
                        const data = collectCardData(cardEl);
                        const warnEl = cardEl.querySelector('.ocr-card-warn');
                        if (!data.correct) {
                            warnEl.style.display = 'block';
                            hasMissing = true;
                        } else {
                            warnEl.style.display = 'none';
                            questionsData.push({
                                subject_id: subjectId,
                                question_text: data.question,
                                option_a: data.a,
                                option_b: data.b,
                                option_c: data.c,
                                option_d: data.d,
                                option_e: data.e,
                                correct_answer: data.correct,
                                points: data.points,
                                image_data: data.image,
                                question_image: data.shape_image // Gambar bangun/tabel
                            });
                        }
                    });

                    if (hasMissing) {
                        alert('Masih ada soal yang belum dipilih jawaban benarnya.');
                        return;
                    }

                    if (questionsData.length === 0) {
                        alert('Tidak ada soal yang valid untuk disimpan');
                        return;
                    }

                    const btnSave = document.querySelector('#ocrStepReview .ocr-btn-primary');
                    const originalText = btnSave.innerHTML;
                    btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                    btnSave.disabled = true;

                    let savedCount = 0;
                    let idx = 0;

                    function saveNext() {
                        if (idx >= questionsData.length) {
                            setTimeout(() => {
                                alert(`Berhasil menyimpan ${savedCount} soal!`);
                                close();
                            }, 500);
                            return;
                        }

                        const q = questionsData[idx];
                        const formData = new URLSearchParams({
                            subject_id: q.subject_id,
                            question_text: q.question_text,
                            option_a: q.option_a,
                            option_b: q.option_b,
                            option_c: q.option_c,
                            option_d: q.option_d,
                            option_e: q.option_e,
                            correct_answer: q.correct_answer,
                            points: q.points,
                        });

                        if (q.image_data) {
                            formData.append('question_image_data', q.image_data);
                        }

                        if (q.question_image) {
                            formData.append('question_image', q.question_image);
                        }

                        fetch(baseUrl + '/question/ocrSave', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            })
                            .then(r => r.json())
                            .then(res => {
                                if (res.success) {
                                    const cardEl = cards[idx];
                                    cardEl.dataset.status = 'saved';
                                    const statusIcon = cardEl.querySelector('.ocr-status-icon');
                                    const statusText = cardEl.querySelector('.ocr-status-text');
                                    statusIcon.className = 'fas fa-check-circle ocr-status-icon';
                                    statusText.textContent = 'Tersimpan';
                                    savedCount++;
                                } else {
                                    alert(`Gagal menyimpan soal #${idx + 1}: ${res.message}`);
                                }
                            })
                            .catch(err => {
                                console.error(`Error soal #${idx + 1}:`, err);
                                alert(`Error koneksi soal #${idx + 1}`);
                            })
                            .finally(() => {
                                idx++;
                                saveNext();
                            });
                    }

                    saveNext();
                }

                function escapeHtml(str) {
                    const d = document.createElement('div');
                    d.textContent = str;
                    return d.innerHTML;
                }

                return {
                    open,
                    close,
                    backToSource,
                    clearQueue,
                    toggleImageSelection,
                    selectAllImages,
                    removeFromQueue,
                    startProcessing,
                    saveAll,
                    cancelCrop,
                    confirmCrop,
                    recropShapeImage // <-- PASTIKAN INI ADA
                };
            })();

        } else {
            console.log('OcrScanner sudah ada, skip deklarasi ulang');
        }
    </script>

<?php } ?>