<?= $this->extend('dashboard') ?>

<?= $this->section('content') ?>
<?php
$currentData = $dataLatih ?? [];
$validation = $validation ?? service('validation');
$labels = $currentData['commodity_labels'] ?? [];
?>

<section class="page-header">
    <div>
        <h2><?= esc($formTitle) ?></h2>
        <p>Lengkapi 7 parameter lahan dan kelas kesesuaian untuk setiap komoditas aktif.</p>
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

            <?php foreach ([
                'kemiringan_lereng' => ['Kemiringan Lereng (%)', 'number', '0.01'],
                'luas_lahan' => ['Luas Lahan (Ha)', 'number', '0.01'],
                'ph_tanah' => ['pH Tanah', 'number', '0.01'],
                'ketinggian_tempat' => ['Ketinggian Tempat (mdpl)', 'number', '1'],
                'kedalaman_tanah' => ['Kedalaman Tanah (cm)', 'number', '1'],
                'curah_hujan' => ['Curah Hujan (mm/tahun)', 'number', '1'],
            ] as $field => [$label, $type, $step]): ?>
                <div class="group-input">
                    <label for="<?= esc($field) ?>"><?= esc($label) ?></label>
                    <input id="<?= esc($field) ?>" name="<?= esc($field) ?>" type="<?= esc($type) ?>" step="<?= esc($step) ?>" value="<?= esc(old($field, isset($currentData[$field]) ? (string) $currentData[$field] : '')) ?>" required>
                    <?php if ($validation->hasError($field)): ?>
                        <small class="field-error"><?= esc($validation->getError($field)) ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

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
        </div>

        <div class="form-card">
            <h3>Kelas Kesesuaian</h3>

            <?php foreach ($komoditas as $commodity): ?>
                <?php $field = 'kelas_' . $commodity['slug']; ?>
                <div class="group-input">
                    <label for="<?= esc($field) ?>"><?= esc($commodity['nama']) ?></label>
                    <select id="<?= esc($field) ?>" name="<?= esc($field) ?>" required>
                        <option value="">Pilih kelas</option>
                        <?php foreach ($kelasOptions as $option): ?>
                            <option value="<?= esc($option) ?>" <?= old($field, $labels[(int) $commodity['id']] ?? '') === $option ? 'selected' : '' ?>>
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
