<?= $this->extend('dashboard') ?>

<?= $this->section('content') ?>
<?php
$nilaiKriteria = $nilaiKriteria ?? [];
$validation = $validation ?? service('validation');
$selectedJenisTanah = json_decode((string) ($nilaiKriteria['jenis_tanah_json'] ?? '[]'), true) ?: [];
?>
<section class="page-header">
    <div>
        <h2><?= esc($formTitle) ?></h2>
        <p>Lengkapi rentang acuan untuk semua parameter klasifikasi.</p>
    </div>
    <a class="btn-inline btn-inline-secondary" href="<?= site_url('nilai-kriteria') ?>">Kembali</a>
</section>

<?php if ($validation->getErrors() !== []): ?>
    <div class="alert alert-error">
        <strong>Validasi gagal.</strong> Periksa kembali field yang ditandai.
    </div>
<?php endif; ?>

<section class="card-section">
    <form class="double-form-grid" action="<?= esc($formAction) ?>" method="post">
        <div class="group-input">
            <label for="komoditas_id">Komoditas</label>
            <select id="komoditas_id" name="komoditas_id" required>
                <option value="">Pilih komoditas</option>
                <?php foreach ($komoditasOptions as $option): ?>
                    <option value="<?= esc((string) $option['id']) ?>" <?= old('komoditas_id', (string) ($nilaiKriteria['komoditas_id'] ?? '')) === (string) $option['id'] ? 'selected' : '' ?>>
                        <?= esc($option['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($validation->hasError('komoditas_id')): ?>
                <small class="field-error"><?= esc($validation->getError('komoditas_id')) ?></small>
            <?php endif; ?>
        </div>

        <?php foreach ([
            'kemiringan' => 'Kemiringan Lereng',
            'luas' => 'Luas Lahan',
            'ph' => 'pH Tanah',
            'ketinggian' => 'Ketinggian',
            'kedalaman' => 'Kedalaman',
            'curah_hujan' => 'Curah Hujan',
        ] as $prefix => $label): ?>
            <div class="range-group">
                <label><?= esc($label) ?></label>
                <div class="range-inputs">
                    <input name="<?= esc($prefix) ?>_min" type="number" step="0.01" value="<?= esc(old($prefix . '_min', (string) ($nilaiKriteria[$prefix . '_min'] ?? ''))) ?>" placeholder="Min">
                    <input name="<?= esc($prefix) ?>_max" type="number" step="0.01" value="<?= esc(old($prefix . '_max', (string) ($nilaiKriteria[$prefix . '_max'] ?? ''))) ?>" placeholder="Max">
                </div>
            </div>
        <?php endforeach; ?>

        <div class="group-input full-width">
            <label for="jenis_tanah">Jenis Tanah</label>
            <select id="jenis_tanah" name="jenis_tanah[]" multiple class="multi-select">
                <?php foreach ($jenisTanahOptions as $option): ?>
                    <?php $selected = in_array($option, old('jenis_tanah', $selectedJenisTanah), true); ?>
                    <option value="<?= esc($option) ?>" <?= $selected ? 'selected' : '' ?>><?= esc($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="group-input full-width">
            <label for="catatan">Catatan</label>
            <textarea id="catatan" name="catatan" rows="4"><?= esc(old('catatan', $nilaiKriteria['catatan'] ?? '')) ?></textarea>
        </div>

        <div class="form-actions full-width">
            <a class="btn-inline btn-inline-secondary" href="<?= site_url('nilai-kriteria') ?>">Batal</a>
            <button class="btn-submit" type="submit"><?= esc($submitLabel) ?></button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
