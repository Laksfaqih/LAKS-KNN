<?= $this->extend('dashboard') ?>

<?= $this->section('content') ?>
<section class="page-header">
    <div>
        <h2>Data Latih</h2>
        <p>Total data tersimpan: <?= esc((string) $totalData) ?></p>
    </div>
    <div class="page-actions">
        <a class="btn-inline btn-inline-secondary" href="<?= site_url('data-latih/export') ?>">Export CSV</a>
        <a class="btn-inline btn-inline-secondary" href="#import-csv">Import CSV</a>
        <a class="btn-inline" href="<?= site_url('data-latih/create') ?>">Tambah Data</a>
    </div>
</section>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php $importErrors = session()->getFlashdata('import_errors') ?? []; ?>
<?php if ($importErrors !== []): ?>
    <div class="alert alert-error">
        <strong>Detail import:</strong>
        <ul class="alert-list">
            <?php foreach ($importErrors as $message): ?>
                <li><?= esc($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<section class="card-section import-card" id="import-csv">
    <div class="import-copy">
        <h3>Import CSV</h3>
        <p>Gunakan file manual `data lahan.csv`. Sistem hanya mengambil data inti hingga marker `jenis tanaman uji`, lalu menyimpan 4 baris sisanya sebagai data uji contoh.</p>
        <code class="code-inline"><?= esc(implode(', ', $csvHeaders)) ?></code>
    </div>
    <form class="import-form" action="<?= site_url('data-latih/import') ?>" method="post" enctype="multipart/form-data">
        <input id="data_latih_csv" name="data_latih_csv" type="file" accept=".csv,text/csv" required>
        <button class="btn-inline" type="submit">Upload CSV</button>
    </form>
</section>

<section class="card-section">
    <?php if ($dataLatih === []): ?>
        <div class="empty-state">
            <h3>Belum ada data latih</h3>
            <p>Tambahkan data pertama untuk mulai membangun dataset klasifikasi.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table data-table-compact data-table-training">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Jenis Tanaman</th>
                        <th>Kelas</th>
                        <th>Kemiringan</th>
                        <th>pH</th>
                        <th>Jenis Tanah</th>
                        <th>Ketinggian</th>
                        <th>Kedalaman</th>
                        <th>Curah Hujan</th>
                        <th>Irigasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $nomor = 1 + (($page - 1) * $perPage); ?>
                    <?php foreach ($dataLatih as $row): ?>
                        <tr>
                            <td><?= esc((string) $nomor++) ?></td>
                            <td><span class="badge"><?= esc($row['nama_komoditas']) ?></span></td>
                            <td><span class="badge"><?= esc($row['kelas'] ?? '-') ?></span></td>
                            <td><?= esc(number_format((float) $row['kemiringan_lereng'], 2)) ?>%</td>
                            <td><?= esc(number_format((float) $row['ph_tanah'], 2)) ?></td>
                            <td><?= esc($row['jenis_tanah']) ?></td>
                            <td><?= esc((string) $row['ketinggian_tempat']) ?> mdpl</td>
                            <td><?= esc((string) $row['kedalaman_tanah']) ?> cm</td>
                            <td><?= esc((string) $row['curah_hujan']) ?> mm</td>
                            <td><?= esc((string) ($row['irigasi'] ?? '-')) ?> m</td>
                            <td>
                                <div class="table-actions">
                                    <a class="link-edit" href="<?= site_url('data-latih/' . $row['id'] . '/edit') ?>">Edit</a>
                                    <form action="<?= site_url('data-latih/' . $row['id'] . '/delete') ?>" method="post" onsubmit="return confirm('Hapus data latih ini?');">
                                        <button class="link-delete" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper">
            <?= $pager->links('data_latih', 'default_full') ?>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
