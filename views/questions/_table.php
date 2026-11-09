<table class="table" id="questionsTable">
    <thead>
        <tr>
            <th style="width:40px; text-align:center">
                <input type="checkbox" id="select-all" onchange="toggleAll(this)" style="width:16px; height:16px; cursor:pointer" title="Pilih semua">
            </th>
            <th style="width:60px; text-align:center">No</th>
            <th>Soal / Dokumen</th>
            <th style="width:130px; text-align:center">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($questions)): ?>
            <tr>
                <td colspan="4" style="text-align:center; padding:2.5rem 0; color:#94a3b8;">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i> Tidak ada soal yang cocok dengan pencarian.
                </td>
            </tr>
        <?php else: ?>
            <?php $no = ($page - 1) * 25 + 1;
            foreach ($questions as $q): ?>
                <tr id="row-<?= $q['id'] ?>"
                    data-type="<?= $q['type'] ?>"
                    data-subject="<?= htmlspecialchars(strtolower($q['subject_name'] ?? '')) ?>"
                    data-subtopic="<?= htmlspecialchars(strtolower($q['sub_topic'] ?? '')) ?>"
                    style="vertical-align:top">

                    <td style="text-align:center; padding-top:14px">
                        <input type="checkbox" class="soal-checkbox" value="<?= $q['id'] ?>" onchange="updateCount()" style="width:16px; height:16px; cursor:pointer">
                    </td>
                    <td style="text-align:center; padding-top:12px">
                        <span style="font-weight:700; color:#374151"><?= $no++ ?></span><br>
                        <span style="color:#94a3b8; font-size:10px">#<?= $q['id'] ?></span>
                    </td>

                    <td style="padding:10px 12px">
                        <?php if ($q['type'] === 'pdf'): ?>
                            <div style="display:flex; align-items:center; gap:15px;">
                                <i class="fas fa-file-pdf" style="font-size:2.5rem; color:#ef4444;"></i>
                                <div>
                                    <div style="font-weight:700; color:#1e293b; font-size:1rem;">
                                        📄 <?= htmlspecialchars($q['question_text'] ?: basename($q['pdf_file'])) ?>
                                    </div>
                                    <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                                        <span class="badge-subject">📚 <?= htmlspecialchars($q['subject_name']) ?></span>
                                        <?php if (!empty($q['sub_topic'])): ?>
                                            <span style="background:#ede9fe; color:#5b21b6; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:600;">
                                                🏷️ <?= htmlspecialchars($q['sub_topic']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="badge-points">⭐ <?= $q['points'] ?> poin</span>
                                        <?php
                                        $pdfPath = $q['pdf_file'] ?? '';
                                        if ($pdfPath) {
                                            $fileName = basename($pdfPath);
                                            $viewUrl = config('app_url') . '/question/viewPdf?file=' . urlencode($fileName);
                                        ?>
                                            <a href="<?= htmlspecialchars($viewUrl) ?>" target="_blank" class="badge-link">
                                                <i class="fas fa-file-pdf"></i> Lihat PDF
                                            </a>
                                        <?php } ?>
                                    </div>
                                    <div style="margin-top:6px; font-size:12px; color:#94a3b8;">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($q['creator_name']) ?> &bull;
                                        <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($q['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="display:flex; gap:5px; flex-wrap:wrap; align-items:center; margin-bottom:6px">
                                <?php
                                $typeLabel = 'Essay';
                                $typeBg = '#f3e8ff';
                                $typeColor = '#6b21a8';
                                if ($q['type'] === 'multiple_choice') {
                                    $typeLabel = 'PG'; $typeBg = '#dbeafe'; $typeColor = '#1e40af';
                                } elseif ($q['type'] === 'short_answer') {
                                    $typeLabel = 'Isian Singkat (Angka)'; $typeBg = '#dcfce7'; $typeColor = '#166534';
                                } elseif ($q['type'] === 'short_answer_text') {
                                    $typeLabel = 'Isian Singkat (Kalimat)'; $typeBg = '#fef9c3'; $typeColor = '#854d0e';
                                } elseif ($q['type'] === 'listening') {
                                    $typeLabel = '🎧 Listening'; $typeBg = '#e0f2fe'; $typeColor = '#0369a1';
                                } elseif ($q['type'] === 'tkp') {
                                    $typeLabel = '📊 TKP'; $typeBg = '#fce7f3'; $typeColor = '#9d174d';
                                }
                                ?>
                                <span style="background:<?= $typeBg ?>; color:<?= $typeColor ?>; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700"><?= $typeLabel ?></span>
                                <span style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:4px; font-size:11px"><i class="fas fa-book" style="font-size:9px"></i> <?= htmlspecialchars($q['subject_name'] ?? '-') ?></span>
                                <?php if (!empty($q['sub_topic'])): ?>
                                    <span style="background:#ede9fe; color:#5b21b6; padding:2px 8px; border-radius:4px; font-size:11px">
                                        <i class="fas fa-tag" style="font-size:9px"></i> <?= htmlspecialchars($q['sub_topic']) ?>
                                    </span>
                                <?php endif; ?>
                                <span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:4px; font-size:11px"><?= $q['points'] ?> poin</span>
                                <span style="color:#94a3b8; font-size:11px"><i class="fas fa-user" style="font-size:9px"></i> <?= htmlspecialchars($q['creator_name'] ?? '-') ?></span>
                                <?php if (!empty($q['question_image'])): ?><i class="fas fa-image" style="color:#6366f1; font-size:11px" title="Ada gambar"></i><?php endif; ?>
                            </div>
                            <div style="color:#1e293b; font-size:13px; line-height:1.5">
                                <?= truncateHtml(renderQuestionText($q['question_text']), 150) ?>
                            </div>
                        <?php endif; ?>
                    </td>

                    <td style="width:130px; padding:8px;">
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <?php if ($q['type'] === 'pdf'): ?>
                                <a href="<?= config('app_url') ?>/question/delete/<?= $q['id'] ?>" class="btn-action-delete" onclick="return confirm('Yakin ingin menghapus file PDF ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            <?php else: ?>
                                <button onclick="openPreview(<?= $q['id'] ?>)" style="background:#ede9fe; color:#4f46e5; border:none; padding:5px 10px; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; width:100%; text-align:center;">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <a href="<?= config('app_url') ?>/question/edit/<?= $q['id'] ?>" style="background:#fef3c7; color:#92400e; padding:5px 10px; border-radius:5px; font-size:12px; font-weight:600; text-decoration:none; display:block; text-align:center;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="<?= config('app_url') ?>/question/delete/<?= $q['id'] ?>" class="btn-action-delete" onclick="return confirm('Yakin ingin menghapus soal ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php if ($totalPages > 1): ?>
<div style="display:flex; justify-content:center; align-items:center; gap:0.4rem; margin-top:1.5rem; flex-wrap:wrap;">
    <?php if ($page > 1): ?>
        <a href="#" data-page="<?= $page - 1 ?>" class="btn-page">&laquo; Sebelumnya</a>
    <?php endif; ?>

    <?php
    $start = max(1, $page - 2);
    $end   = min($totalPages, $page + 2);
    for ($i = $start; $i <= $end; $i++):
    ?>
        <a href="#" data-page="<?= $i ?>" class="btn-page <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="#" data-page="<?= $page + 1 ?>" class="btn-page">Selanjutnya &raquo;</a>
    <?php endif; ?>
</div>
<style>
    .btn-page { padding: 0.5rem 0.9rem; border-radius: 0.5rem; text-decoration: none; background: white; border: 1.5px solid #e2e8f0; color: #374151; font-size: 0.85rem; font-weight: 600; }
    .btn-page.active { background: #6366f1; border-color: #6366f1; color: white; }
    .btn-page:hover:not(.active) { border-color: #6366f1; color: #6366f1; }
</style>
<?php endif; ?>