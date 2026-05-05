<?= $this->extend('dashboard') ?>

<?= $this->section('content') ?>
<section class="page-header">
    <div>
        <h2>Nilai Kriteria</h2>
        <p>Rentang acuan komoditas untuk referensi admin dan dokumentasi.</p>
    </div>
    <div class="page-actions">
        <form action="<?= site_url('nilai-kriteria/import') ?>" method="post" enctype="multipart/form-data" style="display:inline-flex;gap:8px;align-items:center;">
            <input name="nilai_kriteria_csv" type="file" accept=".csv,text/csv" required>
            <button class="btn-inline btn-inline-secondary" type="submit">Import CSV</button>
        </form>
        <a class="btn-inline" href="<?= site_url('nilai-kriteria/create') ?>">Tambah Nilai Kriteria</a>
    </div>
</section>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<section class="card-section">
    <div class="table-wrapper">
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th>Komoditas</th>
                    <th>Kelas</th>
                    <th>Lereng</th>
                    <th>Luas</th>
                    <th>pH</th>
                    <th>Ketinggian</th>
                    <th>Kedalaman</th>
                    <th>Curah Hujan</th>
                    <th>Irigasi (m)</th>
                    <th>Jenis Tanah</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($nilaiKriteria === []): ?>
                    <tr>
                        <td colspan="11">
                            <div class="empty-state compact-empty">
                                <p>Belum ada nilai kriteria.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($nilaiKriteria as $row): ?>
                    <?php $jenisTanah = json_decode((string) ($row['jenis_tanah_json'] ?? '[]'), true) ?: []; ?>
                    <tr>
                        <td>
                            <span class="color-pill">
                                <span class="color-pill-swatch" style="background: <?= esc($row['warna_peta']) ?>"></span>
                                <?= esc($row['nama_komoditas']) ?>
                            </span>
                        </td>
                        <td><span class="badge"><?= esc($row['kelas']) ?></span></td>
                        <td><?= esc(($row['kemiringan_min'] ?? '-') . ' - ' . ($row['kemiringan_max'] ?? '-')) ?></td>
                        <td><?= esc(($row['luas_min'] ?? '-') . ' - ' . ($row['luas_max'] ?? '-')) ?></td>
                        <td><?= esc(($row['ph_min'] ?? '-') . ' - ' . ($row['ph_max'] ?? '-')) ?></td>
                        <td><?= esc(($row['ketinggian_min'] ?? '-') . ' - ' . ($row['ketinggian_max'] ?? '-')) ?></td>
                        <td><?= esc(($row['kedalaman_min'] ?? '-') . ' - ' . ($row['kedalaman_max'] ?? '-')) ?></td>
                        <td><?= esc(($row['curah_hujan_min'] ?? '-') . ' - ' . ($row['curah_hujan_max'] ?? '-')) ?></td>
                        <td><?= esc(($row['irigasi_min'] ?? '-') . ' - ' . ($row['irigasi_max'] ?? '-')) ?></td>
                        <td><?= esc($jenisTanah === [] ? '-' : implode(', ', $jenisTanah)) ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="link-edit" href="<?= site_url('nilai-kriteria/' . $row['id'] . '/edit') ?>">Edit</a>
                                <form action="<?= site_url('nilai-kriteria/' . $row['id'] . '/delete') ?>" method="post" onsubmit="return confirm('Hapus nilai kriteria ini?');">
                                    <button class="link-delete" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
