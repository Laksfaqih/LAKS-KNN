<?= $this->extend('dashboard') ?>

<?= $this->section('content') ?>
<?php
$currentData = $dataLatih ?? [];
$validation = $validation ?? service('validation');

$valueOf = static function (string $field) use ($currentData): string {
    return esc(old($field, isset($currentData[$field]) ? (string) $currentData[$field] : ''));
};
?>

<section class="page-header">
    <div>
        <h2><?= esc($formTitle) ?></h2>
        <p>Lengkapi 7 parameter lahan dan 4 kelas kesesuaian komoditas.</p>
    </div>
    <a class="btn-inline btn-inline-secondary" href="<?= site_url('data-latih') ?>">Kembali</a>
</section>

<?php if ($validation->getErrors() !== []): ?>
    <div class="alert alert-error">
        <strong>Validasi gagal.</strong> Periksa kembali field yang ditandai.
    </div>
<?php endif; ?>

<section class="card-section">
    <form class="data-form-grid" action="<?= esc($formAction) ?>" method="post">
        <div class="form-card">
            <h3>Parameter Lahan</h3>

            <div class="group-input">
                <label for="kemiringan_lereng">Kemiringan Lereng (%)</label>
                <input id="kemiringan_lereng" name="kemiringan_lereng" type="number" step="0.01" min="0" value="<?= $valueOf('kemiringan_lereng') ?>" required>
                <?php if ($validation->hasError('kemiringan_lereng')): ?>
                    <small class="field-error"><?= esc($validation->getError('kemiringan_lereng')) ?></small>
                <?php endif; ?>
            </div>

            <div class="group-input">
                <label for="luas_lahan">Luas Lahan (Ha)</label>
                <input id="luas_lahan" name="luas_lahan" type="number" step="0.01" min="0.01" value="<?= $valueOf('luas_lahan') ?>" required>
                <?php if ($validation->hasError('luas_lahan')): ?>
                    <small class="field-error"><?= esc($validation->getError('luas_lahan')) ?></small>
                <?php endif; ?>
            </div>

            <div class="group-input">
                <label for="ph_tanah">pH Tanah</label>
                <input id="ph_tanah" name="ph_tanah" type="number" step="0.01" min="0" max="14" value="<?= $valueOf('ph_tanah') ?>" required>
                <?php if ($validation->hasError('ph_tanah')): ?>
                    <small class="field-error"><?= esc($validation->getError('ph_tanah')) ?></small>
                <?php endif; ?>
            </div>

            <div class="group-input">
                <label for="jenis_tanah">Jenis Tanah</label>
                <select id="jenis_tanah" name="jenis_tanah" required>
                    <option value="">Pilih jenis tanah</option>
                    <?php foreach ($jenisTanahOptions as $option): ?>
                        <option value="<?= esc($option) ?>" <?= old('jenis_tanah', $currentData['jenis_tanah'] ?? '') === $option ? 'selected' : '' ?>>
                            <?= esc($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($validation->hasError('jenis_tanah')): ?>
                    <small class="field-error"><?= esc($validation->getError('jenis_tanah')) ?></small>
                <?php endif; ?>
            </div>

            <div class="group-input">
                <label for="ketinggian_tempat">Ketinggian Tempat (mdpl)</label>
                <input id="ketinggian_tempat" name="ketinggian_tempat" type="number" min="0" value="<?= $valueOf('ketinggian_tempat') ?>" required>
                <?php if ($validation->hasError('ketinggian_tempat')): ?>
                    <small class="field-error"><?= esc($validation->getError('ketinggian_tempat')) ?></small>
                <?php endif; ?>
            </div>

            <div class="group-input">
                <label for="kedalaman_tanah">Kedalaman Tanah (cm)</label>
                <input id="kedalaman_tanah" name="kedalaman_tanah" type="number" min="1" value="<?= $valueOf('kedalaman_tanah') ?>" required>
                <?php if ($validation->hasError('kedalaman_tanah')): ?>
                    <small class="field-error"><?= esc($validation->getError('kedalaman_tanah')) ?></small>
                <?php endif; ?>
            </div>

            <div class="group-input">
                <label for="curah_hujan">Curah Hujan (mm/tahun)</label>
                <input id="curah_hujan" name="curah_hujan" type="number" min="1" value="<?= $valueOf('curah_hujan') ?>" required>
                <?php if ($validation->hasError('curah_hujan')): ?>
                    <small class="field-error"><?= esc($validation->getError('curah_hujan')) ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-card">
            <h3>Kelas Kesesuaian</h3>

            <?php foreach (['kelas_tebu' => 'Kelas Tebu', 'kelas_cabai' => 'Kelas Cabai', 'kelas_jagung' => 'Kelas Jagung', 'kelas_padi' => 'Kelas Padi'] as $field => $label): ?>
                <div class="group-input">
                    <label for="<?= esc($field) ?>"><?= esc($label) ?></label>
                    <select id="<?= esc($field) ?>" name="<?= esc($field) ?>" required>
                        <option value="">Pilih kelas</option>
                        <?php foreach ($kelasOptions as $option): ?>
                            <option value="<?= esc($option) ?>" <?= old($field, $currentData[$field] ?? '') === $option ? 'selected' : '' ?>>
                                <?= esc($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($validation->hasError($field)): ?>
                        <small class="field-error"><?= esc($validation->getError($field)) ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <a class="btn-inline btn-inline-secondary" href="<?= site_url('data-latih') ?>">Batal</a>
                <button class="btn-submit" type="submit"><?= esc($submitLabel) ?></button>
            </div>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
