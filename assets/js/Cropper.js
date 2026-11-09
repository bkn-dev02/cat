const OcrScanner = (function () {
    const baseUrl = (window.APP_URL || document.querySelector('meta[name="app-url"]')?.content || '').replace(/\/$/, '');

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
                sel.innerHTML = data.subjects
                    .map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`)
                    .join('');
                subjectsLoaded = true;
            })
            .catch(() => {
                sel.innerHTML = '<option value="">Gagal memuat mapel</option>';
            });
    }

    // ─ Event listener (sekali pasang) ──
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
    }

    // ── Handle file selection dengan crop ──
    function handleFileSelect(fileList) {
        const files = Array.from(fileList || []).filter(f => f.type.startsWith('image/'));
        if (!files.length) return;

        // Proses satu per satu dengan crop
        processFilesWithCrop(files, 0);
    }

    function processFilesWithCrop(files, index) {
        if (index >= files.length) return;

        const file = files[index];
        const reader = new FileReader();
        
        reader.onload = (e) => {
            pendingCropFile = file;
            pendingCropId = ++queueIdCounter;
            
            // Tampilkan modal crop
            const cropImage = document.getElementById('ocrCropImage');
            cropImage.src = e.target.result;
            document.getElementById('ocrCropModal').style.display = 'flex';
            
            // Inisialisasi Cropper.js
            setTimeout(() => {
                if (cropper) cropper.destroy();
                
                cropper = new Cropper(cropImage, {
                    aspectRatio: NaN, // Free ratio
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.8,
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

    // ── Crop controls ──
    function cancelCrop() {
        document.getElementById('ocrCropModal').style.display = 'none';
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        pendingCropFile = null;
        pendingCropId = null;
    }

    function confirmCrop() {
        if (!cropper) return;

        // Dapatkan hasil crop sebagai canvas
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

        // Convert canvas ke data URL
        const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.9);

        // Tambahkan ke queue
        queue.push({
            id: pendingCropId,
            file: pendingCropFile,
            dataUrl: croppedDataUrl,
            isCropped: true
        });
        selectedIds.add(pendingCropId);

        // Cleanup
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        pendingCropFile = null;
        pendingCropId = null;

        // Sembunyikan modal crop
        document.getElementById('ocrCropModal').style.display = 'none';

        // Update tampilan
        renderQueue();

        // Jika ada file berikutnya, proses
        // (tidak otomatis, user upload lagi manual)
    }

    // ── Toggle seleksi gambar ──
    function toggleImageSelection(id) {
        if (selectedIds.has(id)) {
            selectedIds.delete(id);
        } else {
            selectedIds.add(id);
        }
        renderQueue();
    }

    function selectAllImages(select = true) {
        if (select) {
            queue.forEach(item => selectedIds.add(item.id));
        } else {
            selectedIds.clear();
        }
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
            return `
            <div class="ocr-queue-thumb ${isSelected ? 'selected' : ''}">
                <img src="${item.dataUrl}" alt="">
                <span class="ocr-thumb-num">${idx + 1}</span>
                ${item.isCropped ? '<span class="ocr-thumb-cropped"><i class="fas fa-crop-alt"></i></span>' : ''}
                <label class="ocr-thumb-check">
                    <input type="checkbox" ${isSelected ? 'checked' : ''} 
                           onchange="OcrScanner.toggleImageSelection(${item.id})"
                           title="Centang untuk memproses gambar ini">
                </label>
                <button type="button" class="ocr-thumb-remove" onclick="OcrScanner.removeFromQueue(${item.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        }).join('') + `
            <div class="ocr-queue-add" onclick="document.getElementById('ocrGalleryInput').click()" title="Tambah gambar lagi">
                <i class="fas fa-plus"></i>
            </div>
        `;
    }

    // ─ Proses semua gambar yang DIPILIH di antrian: OCR satu per satu ──
    function startProcessing() {
        if (!queue.length) {
            alert('Tidak ada gambar untuk diproses');
            return;
        }

        if (!selectedIds.size) {
            alert('Pilih minimal 1 gambar untuk diproses (centang gambar yang diinginkan)');
            return;
        }

        const subjectId = document.getElementById('ocrSubjectSelect').value;
        if (!subjectId) {
            alert('Pilih mata pelajaran dulu sebelum memproses.');
            return;
        }

        const defaultPoints = parseInt(document.getElementById('ocrDefaultPoints').value, 10) || 1;

        // Filter hanya gambar yang dipilih
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
                    id: item.id,
                    imageDataUrl: item.dataUrl,
                    question: parsed.question,
                    a: parsed.a, b: parsed.b, c: parsed.c, d: parsed.d, e: parsed.e,
                    correct: '',
                    points: defaultPoints,
                    status: 'pending',
                });
            })
            .catch(() => {
                results.push({
                    id: item.id,
                    imageDataUrl: item.dataUrl,
                    question: '(Gagal membaca teks — isi manual)',
                    a: '', b: '', c: '', d: '', e: '',
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

    // ── PARSING LOGIC (IMPROVED) ──
    function parseQuestionText(rawText) {
        const lines = rawText.split('\n').map(l => l.trim()).filter(l => l.length);
        const opts = { a: '', b: '', c: '', d: '', e: '' };
        const questionLines = [];
        let currentOpt = null;
        let foundFirstOption = false;

        const optPattern = /^([A-Ea-e])[\.\)\:]\s*(.*)$/;
        const questionNumPattern = /^(\d+[\.\)]\s*)/;
        
        let cleanedLines = lines.map(line => {
            const match = line.match(questionNumPattern);
            if (match && questionLines.length === 0 && !foundFirstOption) {
                return line.replace(match[1], '').trim();
            }
            return line;
        }).filter(l => l.length);

        cleanedLines.forEach(line => {
            const optMatch = line.match(optPattern);
            
            if (optMatch) {
                foundFirstOption = true;
                currentOpt = optMatch[1].toLowerCase();
                const optText = optMatch[2].trim();
                
                const additionalOpts = optText.split(/\s+([B-Eb-e])[\.\)\:]\s*/);
                if (additionalOpts.length > 1) {
                    opts[currentOpt] = additionalOpts[0].trim();
                    for (let i = 1; i < additionalOpts.length; i += 2) {
                        const optLetter = additionalOpts[i].toLowerCase();
                        const optValue = additionalOpts[i + 1] || '';
                        if (optLetter && optValue) {
                            opts[optLetter] = optValue.trim();
                        }
                    }
                } else {
                    opts[currentOpt] = optText;
                }
            } else if (currentOpt) {
                if (!line.match(/^\d+[\.\)]/) && !line.match(/^[A-Ea-e][\.\)\:]/)) {
                    opts[currentOpt] = (opts[currentOpt] + ' ' + line).trim();
                }
            } else {
                questionLines.push(line);
            }
        });

        let question = questionLines.join(' ').trim();
        question = question.replace(/\s*(Jawaban|Kunci|Answer)\s*[:\-]?\s*[A-Ea-e][\.\)]?\s*$/i, '').trim();
        question = question.replace(/^\d+[\.\)]\s+/, '').trim();

        return {
            question: question,
            a: opts.a || '',
            b: opts.b || '',
            c: opts.c || '',
            d: opts.d || '',
            e: opts.e || ''
        };
    }

    function cleanOCRText(text) {
        return text
            .replace(/\b0\.([A-Ea-e])\b/g, '$1.')
            .replace(/\b([0-9])\.([A-Ea-e])\b/g, '$1. $2.')
            .replace(/\s+/g, ' ')
            .replace(/([a-zA-Z])\.([A-Z])\s/g, '$1. $2 ')
            .trim();
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
        };
    }

    function saveAll() {
        const subjectId = document.getElementById('ocrStepReview').dataset.subjectId;
        const cards = Array.from(document.querySelectorAll('#ocrReviewList .ocr-card'));

        if (!cards.length) return;

        let hasMissing = false;
        cards.forEach(cardEl => {
            const data = collectCardData(cardEl);
            const warnEl = cardEl.querySelector('.ocr-card-warn');
            if (!data.correct) {
                warnEl.style.display = 'block';
                hasMissing = true;
            } else {
                warnEl.style.display = 'none';
            }
        });
        if (hasMissing) {
            alert('Masih ada soal yang belum dipilih jawaban benarnya. Cek tanda peringatan kuning.');
            return;
        }

        let idx = 0;
        function saveNext() {
            if (idx >= cards.length) {
                setTimeout(() => {
                    alert('Semua soal berhasil disimpan!');
                    close();
                }, 500);
                return;
            }

            const cardEl = cards[idx];
            const data = collectCardData(cardEl);
            const statusIcon = cardEl.querySelector('.ocr-status-icon');
            const statusText = cardEl.querySelector('.ocr-status-text');

            statusIcon.className = 'fas fa-circle-notch fa-spin ocr-status-icon';
            statusText.textContent = 'Menyimpan...';

            fetch(baseUrl + '/question/ocrSave', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams({
                    subject_id: subjectId,
                    question_text: data.question,
                    option_a: data.a,
                    option_b: data.b,
                    option_c: data.c,
                    option_d: data.d,
                    option_e: data.e,
                    correct_answer: data.correct,
                    points: data.points,
                }),
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    cardEl.dataset.status = 'saved';
                    statusIcon.className = 'fas fa-check-circle ocr-status-icon';
                    statusText.textContent = 'Tersimpan ke bank soal';
                } else {
                    cardEl.dataset.status = 'save-error';
                    statusIcon.className = 'fas fa-times-circle ocr-status-icon';
                    statusText.textContent = res.message || 'Gagal menyimpan';
                }
            })
            .catch(() => {
                cardEl.dataset.status = 'save-error';
                statusIcon.className = 'fas fa-times-circle ocr-status-icon';
                statusText.textContent = 'Gagal menyimpan (koneksi)';
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
        open, close, backToSource, clearQueue,
        toggleImageSelection, selectAllImages, removeFromQueue, startProcessing, saveAll,
        cancelCrop, confirmCrop
    };
})();