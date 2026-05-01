<?= $this->extend('dashboard') ?>

<?= $this->section('content') ?>
<?php
$komoditas = $komoditas ?? [];
$validation = $validation ?? service('validation');
?>
<section class="page-header">
    <div>
        <h2><?= esc($formTitle) ?></h2>
        <p>Atur nama komoditas, slug, warna peta, dan status aktif.</p>
    </div>
    <a class="btn-inline btn-inline-secondary" href="<?= site_url('kriteria') ?>">Kembali</a>
</section>

<?php if ($validation->getErrors() !== []): ?>
    <div class="alert alert-error">
        <strong>Validasi gagal.</strong> Periksa kembali field yang ditandai.
    </div>
<?php endif; ?>

<section class="card-section">
    <form class="single-form-grid" action="<?= esc($formAction) ?>" method="post">
        <div class="group-input">
            <label for="nama">Nama Kriteria</label>
            <input id="nama" name="nama" type="text" value="<?= esc(old('nama', $komoditas['nama'] ?? '')) ?>" required>
            <?php if ($validation->hasError('nama')): ?>
                <small class="field-error"><?= esc($validation->getError('nama')) ?></small>
            <?php endif; ?>
        </div>

        <div class="group-input">
            <label for="slug">Slug</label>
            <input id="slug" name="slug" type="text" value="<?= esc(old('slug', $komoditas['slug'] ?? '')) ?>" required>
            <?php if ($validation->hasError('slug')): ?>
                <small class="field-error"><?= esc($validation->getError('slug')) ?></small>
            <?php endif; ?>
        </div>

        <div class="group-input">
            <label for="warna_peta">Warna Peta</label>
            <input id="warna_peta" name="warna_peta" type="color" value="<?= esc(old('warna_peta', $komoditas['warna_peta'] ?? '#4CAF50')) ?>" required>
            <?php if ($validation->hasError('warna_peta')): ?>
                <small class="field-error"><?= esc($validation->getError('warna_peta')) ?></small>
            <?php endif; ?>
        </div>

        <div class="group-input">
            <label for="urutan_tampil">Urutan Tampil</label>
            <input id="urutan_tampil" name="urutan_tampil" type="number" min="1" value="<?= esc(old('urutan_tampil', (string) ($komoditas['urutan_tampil'] ?? 1))) ?>" required>
            <?php if ($validation->hasError('urutan_tampil')): ?>
                <small class="field-error"><?= esc($validation->getError('urutan_tampil')) ?></small>
            <?php endif; ?>
        </div>

        <div class="group-input">
            <label for="is_active">Status</label>
            <select id="is_active" name="is_active">
                <option value="1" <?= old('is_active', isset($komoditas['is_active']) ? (string) (int) $komoditas['is_active'] : '1') === '1' ? 'selected' : '' ?>>Aktif</option>
                <option value="0" <?= old('is_active', isset($komoditas['is_active']) ? (string) (int) $komoditas['is_active'] : '1') === '0' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </div>

        <div class="form-actions">
            <a class="btn-inline btn-inline-secondary" href="<?= site_url('kriteria') ?>">Batal</a>
            <button class="btn-submit" type="submit"><?= esc($submitLabel) ?></button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
