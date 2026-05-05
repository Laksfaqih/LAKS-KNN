<?= $this->extend('dashboard') ?>

<?= $this->section('content') ?>
<section class="page-header">
    <div>
        <h2>Kriteria</h2>
        <p>Kelola komoditas yang akan dipakai dalam klasifikasi dan mapping.</p>
    </div>
    <div class="page-actions">
        <a class="btn-inline" href="<?= site_url('kriteria/create') ?>">Tambah Kriteria</a>
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
        <table class="data-table history-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Kriteria</th>
                    <th>Warna Peta</th>
                    <th>Urutan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($komoditas === []): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state compact-empty">
                                <p>Belum ada data kriteria.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($komoditas as $index => $row): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc($row['nama']) ?></td>
                        <td>
                            <span class="color-pill">
                                <span class="color-pill-swatch" style="background: <?= esc($row['warna_peta']) ?>"></span>
                                <?= esc($row['warna_peta']) ?>
                            </span>
                        </td>
                        <td><?= esc((string) $row['urutan_tampil']) ?></td>
                        <td>
                            <span class="badge"><?= $row['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="link-edit" href="<?= site_url('kriteria/' . $row['id'] . '/edit') ?>">Edit</a>
                                <form action="<?= site_url('kriteria/' . $row['id'] . '/delete') ?>" method="post" onsubmit="return confirm('Hapus kriteria ini?');">
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
