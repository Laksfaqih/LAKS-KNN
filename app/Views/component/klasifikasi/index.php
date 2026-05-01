<?= $this->extend('dashboard') ?>

<?= $this->section('styles') ?>
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
>
<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"
>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $validation = $validation ?? service('validation'); ?>
<section class="page-header">
    <div>
        <h2>Klasifikasi</h2>
        <p>Input parameter lahan dan gambar area/poligon untuk memproses klasifikasi.</p>
    </div>
</section>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<section class="card-section">
    <div class="info-banner">
        <strong>Total data latih aktif:</strong> <?= esc((string) $totalDataLatih) ?> data
    </div>

    <?php if ($validation->getErrors() !== []): ?>
        <div class="alert alert-error">
            <strong>Validasi gagal.</strong> Periksa kembali field yang ditandai.
        </div>
    <?php endif; ?>

    <form class="classification-form" action="<?= site_url('klasifikasi') ?>" method="post">
        <div class="classification-fields">
            <div class="group-input">
                <label for="nama_lahan">Lokasi / Nama Lahan</label>
                <input id="nama_lahan" name="nama_lahan" type="text" value="<?= esc(old('nama_lahan')) ?>" placeholder="Contoh: Lahan Blok A">
            </div>

            <?php foreach ([
                'kemiringan_lereng' => ['Lereng (%)', 'number', '0.01'],
                'ketinggian_tempat' => ['Ketinggian (mdpl)', 'number', '1'],
                'curah_hujan' => ['Curah Hujan (mm/tahun)', 'number', '1'],
                'ph_tanah' => ['pH Tanah', 'number', '0.01'],
                'luas_lahan' => ['Luas Lahan (Ha)', 'number', '0.01'],
                'kedalaman_tanah' => ['Kedalaman (cm)', 'number', '1'],
                'nilai_k' => ['Nilai K', 'number', '1'],
            ] as $field => [$label, $type, $step]): ?>
                <div class="group-input">
                    <label for="<?= esc($field) ?>"><?= esc($label) ?></label>
                    <input id="<?= esc($field) ?>" name="<?= esc($field) ?>" type="<?= esc($type) ?>" step="<?= esc($step) ?>" value="<?= esc(old($field, $field === 'nilai_k' ? '3' : '')) ?>" required>
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
                        <option value="<?= esc($option) ?>" <?= old('jenis_tanah') === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($validation->hasError('jenis_tanah')): ?>
                    <small class="field-error"><?= esc($validation->getError('jenis_tanah')) ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="classification-map-panel">
            <label class="map-draw-label">Area Lahan</label>
            <div id="classification-map" class="map-canvas map-canvas-compact"></div>
            <input id="polygon_geojson" name="polygon_geojson" type="hidden" value="<?= esc(old('polygon_geojson')) ?>">
            <?php if ($validation->hasError('polygon_geojson')): ?>
                <small class="field-error"><?= esc($validation->getError('polygon_geojson')) ?></small>
            <?php endif; ?>
            <p class="section-copy">Gambar satu poligon lahan pada peta. Gunakan tool polygon di kiri atas.</p>
        </div>

        <div class="form-actions full-width">
            <button class="btn-submit" type="submit">Proses Klasifikasi</button>
        </div>
    </form>
</section>

<section class="card-section">
    <h3 class="section-title">Riwayat Hasil Terbaru</h3>
    <?php if ($recentResults === []): ?>
        <div class="empty-state compact-empty">
            <p>Belum ada hasil klasifikasi yang tersimpan.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table history-table">
                <thead>
                    <tr>
                        <th>Nama Lahan</th>
                        <th>K</th>
                        <th>Rekomendasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentResults as $row): ?>
                        <tr>
                            <td><?= esc($row['nama_lahan'] ?: 'Tanpa nama') ?></td>
                            <td><?= esc((string) $row['nilai_k']) ?></td>
                            <td><span class="badge"><?= esc($row['rekomendasi_komoditas']) ?></span></td>
                            <td><a class="link-edit" href="<?= site_url('klasifikasi/hasil/' . $row['id']) ?>">Lihat</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script>
    const defaultCenter = [-7.709, 113.961];
    const map = L.map('classification-map').setView(defaultCenter, 13);
    const hiddenInput = document.getElementById('polygon_geojson');
    const drawnItems = new L.FeatureGroup().addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const drawControl = new L.Control.Draw({
        draw: {
            polygon: true,
            polyline: false,
            rectangle: false,
            circle: false,
            marker: false,
            circlemarker: false
        },
        edit: {
            featureGroup: drawnItems,
            remove: true
        }
    });
    map.addControl(drawControl);

    const syncGeoJson = () => {
        const layers = drawnItems.getLayers();
        if (layers.length === 0) {
            hiddenInput.value = '';
            return;
        }

        const geoJson = layers[0].toGeoJSON();
        hiddenInput.value = JSON.stringify(geoJson);
    };

    map.on(L.Draw.Event.CREATED, (event) => {
        drawnItems.clearLayers();
        drawnItems.addLayer(event.layer);
        syncGeoJson();
    });

    map.on(L.Draw.Event.EDITED, syncGeoJson);
    map.on(L.Draw.Event.DELETED, syncGeoJson);

    if (hiddenInput.value) {
        try {
            const parsed = JSON.parse(hiddenInput.value);
            const restoredLayer = L.geoJSON(parsed);
            restoredLayer.eachLayer((layer) => drawnItems.addLayer(layer));
            map.fitBounds(drawnItems.getBounds(), { padding: [20, 20] });
        } catch (error) {
            hiddenInput.value = '';
        }
    }
</script>
<?= $this->endSection() ?>
