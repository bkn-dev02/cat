const OcrScanner = (function () {
    const baseUrl = (window.APP_URL || document.querySelector('meta[name="app-url"]')?.content || '').replace(/\/$/, '');
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

    // Crop variables
    let cropper = null;
    let pendingCropFile = null;
    let pendingCropId = null;
    let pendingFiles = [];
    let currentFileIndex = 0;
    let cropMode = 'queue'; // 'queue', 'option', atau 'shape'

    let optionScanCardEl = null;
    let shapeImageCardEl = null;
    let shapeImageInputEl = null;

    // ── Buka / tutup modal ──
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

    // ── Load daftar mapel untuk dropdown ──
    function loadSubjects() {
        const sel = document.getElementById('ocrSubjectSelect');
        fetch(baseUrl + '/question/ocrSubjects', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.subjects || !data.subjects.length) {
                    sel.innerHTML = '<option value="">Tidak ada mapel ditemukan</option>';
                    return;
                }
                sel.innerHTML = data.subjects.map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
                subjectsLoaded = true;
            })
            .catch(() => {
                sel.innerHTML = '<option value="">Gagal memuat mapel</option>';
            });
    }

    // ── Event listener (sekali pasang) ──
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
                // Handle scan options button
                const btn = e.target.closest('.ocr-scan-options-btn');
                if (btn) {
                    optionScanCardEl = btn.closest('.ocr-card');
                    const optionInputEl = document.getElementById('ocrOptionScanInput');
                    if (optionInputEl) optionInputEl.click();
                }

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

    // ── Handle file selection dengan crop ──
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
                    aspectRatio: NaN, viewMode: 1, dragMode: 'move',
                    autoCropArea: 0.9, restore: false, guides: true,
                    center: true, highlight: true, cropBoxMovable: true,
                    cropBoxResizable: true, toggleDragModeOnDblclick: false, background: true,
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
                    aspectRatio: NaN, viewMode: 1, dragMode: 'move',
                    autoCropArea: 0.9, restore: false, guides: true,
                    center: true, highlight: true, cropBoxMovable: true,
                    cropBoxResizable: true, toggleDragModeOnDblclick: false, background: true,
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
                    aspectRatio: NaN, viewMode: 1, dragMode: 'move',
                    autoCropArea: 0.9, restore: false, guides: true,
                    center: true, highlight: true, cropBoxMovable: true,
                    cropBoxResizable: true, toggleDragModeOnDblclick: false, background: true,
                });
            }, 100);
        };
        reader.readAsDataURL(file);
    }

    // ── Crop controls ──
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
            if (cropper) { cropper.destroy(); cropper = null; }
            pendingCropFile = null;
            document.getElementById('ocrCropModal').classList.remove('active');
            const targetCard = optionScanCardEl;
            cropMode = 'queue';
            optionScanCardEl = null;
            processOptionScan(croppedDataUrl, targetCard);
            return;
        }

        if (cropMode === 'shape') {
            if (cropper) { cropper.destroy(); cropper = null; }
            pendingCropFile = null;
            document.getElementById('ocrCropModal').classList.remove('active');

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

        queue.push({ id: pendingCropId, file: pendingCropFile, dataUrl: croppedDataUrl, isCropped: true });
        selectedIds.add(pendingCropId);
        if (cropper) { cropper.destroy(); cropper = null; }
        pendingCropFile = null;
        pendingCropId = null;
        document.getElementById('ocrCropModal').classList.remove('active');
        renderQueue();
        currentFileIndex++;
        showCropForNextFile();
    }

    // ── FUNGSI CROP ULANG GAMBAR BANGUN/TABEL ──
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
                    aspectRatio: NaN, viewMode: 1, dragMode: 'move',
                    autoCropArea: 0.9, restore: false, guides: true,
                    center: true, highlight: true, cropBoxMovable: true,
                    cropBoxResizable: true, toggleDragModeOnDblclick: false, background: true,
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
            .then(({ data }) => {
                const options = extractOptionsFromPhoto(data.text || '');
                let filledAny = false;
                ['a', 'b', 'c', 'd', 'e'].forEach(letter => {
                    const field = cardEl.querySelector('.ocr-field-' + letter);
                    if (field && options[letter]) {
                        field.value = options[letter];
                        filledAny = true;
                    }
                });
                if (!filledAny) alert('Tidak ada opsi yang terbaca.');
            })
            .catch((err) => { console.error('OCR Option Error:', err); alert('Gagal membaca opsi.'); })
            .finally(() => {
                if (statusIcon) statusIcon.className = prevIconClass;
                if (statusText) statusText.textContent = prevText;
                if (scanBtn) scanBtn.disabled = false;
            });
    }

    // ── Proses semua gambar yang DIPILIH di antrian ──
    function startProcessing() {
        if (!queue.length) { alert('Tidak ada gambar untuk diproses'); return; }
        if (!selectedIds.size) { alert('Pilih minimal 1 gambar untuk diproses'); return; }

        const subjectId = document.getElementById('ocrSubjectSelect').value;
        if (!subjectId) { alert('Pilih mata pelajaran dulu sebelum memproses.'); return; }

        const defaultPoints = parseInt(document.getElementById('ocrDefaultPoints').value, 10) || 1;
        const selectedQueue = queue.filter(item => selectedIds.has(item.id));

        document.getElementById('ocrStepSource').style.display = 'none';
        document.getElementById('ocrLoading').style.display = 'block';

        results = [];
        const total = selectedQueue.length;
        let done = 0;

        function processNext() {
            if (done >= total) { finishProcessing(subjectId, defaultPoints); return; }

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
                .then(({ data }) => {
                    const cleanedText = cleanOCRText(data.text || '');
                    const parsed = parseQuestionText(cleanedText);
                    results.push({
                        id: item.id, imageDataUrl: item.dataUrl,
                        question: parsed.question || '(Teks tidak terbaca)',
                        a: parsed.a || '', b: parsed.b || '', c: parsed.c || '', d: parsed.d || '', e: parsed.e || '',
                        correct: '', points: defaultPoints, status: 'pending',
                    });
                })
                .catch(() => {
                    results.push({
                        id: item.id, imageDataUrl: item.dataUrl,
                        question: '(Gagal membaca teks)', a: '', b: '', c: '', d: '', e: '',
                        correct: '', points: defaultPoints, status: 'pending',
                    });
                })
                .finally(() => { done++; processNext(); });
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
                const ctx = canvas.getContext('2d', { willReadFrequently: true });
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
                    pixels[i] = value; pixels[i + 1] = value; pixels[i + 2] = value;
                }
                ctx.putImageData(imageData, 0, 0);
                resolve(canvas.toDataURL('image/jpeg', 0.9));
            };
            img.onerror = () => resolve(dataUrl);
            img.src = dataUrl;
        });
    }

    // ── PARSING LOGIC ──
    function parseQuestionText(rawText) {
        if (!rawText || rawText.trim().length < 10) {
            return { question: '(Teks terlalu pendek)', a: '', b: '', c: '', d: '', e: '', correct: '' };
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
            a: options.a || '', b: options.b || '', c: options.c || '', d: options.d || '', e: options.e || '',
            correct: correctAnswer || ''
        };
    }

    function extractCorrectAnswer(text) {
        if (!text) return null;
        const patterns = [/Jawaban:\s*([A-Ea-e])/i, /Kunci:\s*([A-Ea-e])/i, /Answer:\s*([A-Ea-e])/i, /Benar:\s*([A-Ea-e])/i];
        for (const pattern of patterns) {
            const match = text.match(pattern);
            if (match) return match[1].toUpperCase();
        }
        const bracketMatch = text.match(/[\(\[]\s*([A-Ea-e])\s*[\)\]]/i);
        if (bracketMatch) return bracketMatch[1].toUpperCase();
        return null;
    }

    function extractOptionsFromPhoto(rawText) {
        const options = { a: '', b: '', c: '', d: '', e: '' };
        if (!rawText) return options;
        const text = rawText.replace(/\r/g, ' ').replace(/\s+/g, ' ');
        const boundaryRegex = /(?:^|[\s])([A-Ea-e])\s*[\.\)\:]\s*(?=\S)/g;
        const boundaries = [];
        let m;
        while ((m = boundaryRegex.exec(text)) !== null) {
            boundaries.push({ letter: m[1].toLowerCase(), matchStart: m.index, valueStart: boundaryRegex.lastIndex });
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
        const options = { a: '', b: '', c: '', d: '', e: '' };
        if (!text) return options;

        const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 2);
        lines.forEach((line) => {
            const match = line.match(/^([A-Ea-e])[\.\)]\s+(.+)$/);
            if (match) {
                const letter = match[1].toLowerCase();
                let value = match[2].trim().replace(/[^\w\s.,;:'"()\-\u00C0-\u024F]/g, ' ').trim();
                if (value.length >= 2 && value.length <= 150 && !options[letter]) {
                    options[letter] = value;
                }
            }
        });

        const inlineRegex = /(?:^|\s)([A-Ea-e])[\.\)]\s*([^\nA-Ea-e]{2,100}?)(?=\s+[A-Ea-e][\.\)]|$)/g;
        let inlineMatch;
        while ((inlineMatch = inlineRegex.exec(text)) !== null) {
            const letter = inlineMatch[1].toLowerCase();
            let value = inlineMatch[2].trim().replace(/[^\w\s.,;:'"()\-\u00C0-\u024F]/g, '').trim();
            if (value.length >= 2 && value.length <= 150 && !options[letter]) {
                options[letter] = value;
            }
        }
        return options;
    }

    function cleanOCRText(text) {
        return text.replace(/\b0\.([A-Ea-e])\b/g, '$1.')
            .replace(/\b([0-9])\.([A-Ea-e])\b/g, '$1. $2.')
            .replace(/\s+/g, ' ').replace(/([a-zA-Z])\.([A-Z])\s/g, '$1. $2 ').trim();
    }

    // ── Toggle seleksi gambar ──
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

        if (!queue.length) { grid.innerHTML = ''; return; }

        grid.innerHTML = queue.map((item, idx) => {
            const isSelected = selectedIds.has(item.id);
            return `
            <div class="ocr-queue-thumb ${isSelected ? 'selected' : ''}">
                <img src="${item.dataUrl}" alt="">
                <span class="ocr-thumb-num">${idx + 1}</span>
                ${item.isCropped ? '<span class="ocr-thumb-cropped"><i class="fas fa-crop-alt"></i></span>' : ''}
                <label class="ocr-thumb-check">
                    <input type="checkbox" ${isSelected ? 'checked' : ''} 
                           onchange="OcrScanner.toggleImageSelection(${item.id})" title="Centang untuk memproses">
                </label>
                <button type="button" class="ocr-thumb-remove" onclick="OcrScanner.removeFromQueue(${item.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>`;
        }).join('') + `
            <div class="ocr-queue-add" onclick="document.getElementById('ocrGalleryInput').click()" title="Tambah gambar lagi">
                <i class="fas fa-plus"></i>
            </div>`;
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
                        if (window.MathJax) MathJax.typesetPromise([mathPreviewContent]).catch(console.error);
                    }, 100);
                } else {
                    mathPreview.style.display = 'none';
                }
            });

            if (r.question && (r.question.includes('\\(') || r.question.includes('$$'))) {
                mathPreview.style.display = 'block';
                mathPreviewContent.innerHTML = r.question;
                setTimeout(() => {
                    if (window.MathJax) MathJax.typesetPromise([mathPreviewContent]).catch(console.error);
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
                    if (file) handleShapeImageFile(file);
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
        setTimeout(() => { if (window.MathJax) MathJax.typesetPromise().catch(console.error); }, 200);
    }

    function collectCardData(cardEl) {
        const id = parseInt(cardEl.dataset.resultId, 10);
        const checkedRadio = cardEl.querySelector('.ocr-correct-radio:checked');
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
            image: cardEl.dataset.imageData || '',
            shape_image: cardEl.dataset.shapeImageData || ''
        };
    }

    function saveAll() {
        const subjectId = document.getElementById('ocrStepReview').dataset.subjectId;
        const cards = Array.from(document.querySelectorAll('#ocrReviewList .ocr-card'));

        if (!cards.length) return;

        let hasMissing = false;
        const questionsData = [];

        cards.forEach((cardEl) => {
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
                    option_a: data.a, option_b: data.b, option_c: data.c, option_d: data.d, option_e: data.e,
                    correct_answer: data.correct,
                    points: data.points,
                    image_data: data.image,
                    question_image: data.shape_image
                });
            }
        });

        if (hasMissing) {
            alert('Masih ada soal yang belum dipilih jawaban benarnya. Cek tanda peringatan kuning.');
            return;
        }

        if (questionsData.length === 0) {
            alert('Tidak ada soal yang valid untuk disimpan');
            return;
        }

        const btnSave = document.querySelector('#ocrStepReview .ocr-btn-primary');
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
                subject_id: q.subject_id, question_text: q.question_text,
                option_a: q.option_a, option_b: q.option_b, option_c: q.option_c,
                option_d: q.option_d, option_e: q.option_e,
                correct_answer: q.correct_answer, points: q.points,
            });

            if (q.image_data) formData.append('question_image_data', q.image_data);
            if (q.question_image) formData.append('question_image', q.question_image);

            fetch(baseUrl + '/question/ocrSave', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const cardEl = cards[idx];
                        cardEl.dataset.status = 'saved';
                        cardEl.querySelector('.ocr-status-icon').className = 'fas fa-check-circle ocr-status-icon';
                        cardEl.querySelector('.ocr-status-text').textContent = 'Tersimpan';
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

    // ── PUBLIC API ──
    return {
        open, close, backToSource, clearQueue,
        toggleImageSelection, selectAllImages, removeFromQueue,
        startProcessing, saveAll, cancelCrop, confirmCrop,
        recropShapeImage  // <-- SUDAH DITAMBAHKAN DI SINI
    };
})();