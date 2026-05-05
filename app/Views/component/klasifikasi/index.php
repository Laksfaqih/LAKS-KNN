<?= $this->extend('dashboard') ?>

<?= $this->section('styles') ?>
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
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
                <label for="latitude">Latitude</label>
                <input id="latitude" name="latitude" type="number" step="0.00000001" value="<?= esc(old('latitude')) ?>" required>
                <?php if ($validation->hasError('latitude')): ?>
                    <small class="field-error"><?= esc($validation->getError('latitude')) ?></small>
                <?php endif; ?>
            </div>

            <div class="group-input">
                <label for="longitude">Longitude</label>
                <input id="longitude" name="longitude" type="number" step="0.00000001" value="<?= esc(old('longitude')) ?>" required>
                <?php if ($validation->hasError('longitude')): ?>
                    <small class="field-error"><?= esc($validation->getError('longitude')) ?></small>
                <?php endif; ?>
            </div>

            <div class="group-input full-width">
                <label for="detected_address">Alamat Terdeteksi</label>
                <input id="detected_address" type="text" value="" placeholder="Alamat akan terisi otomatis dari koordinat" readonly>
            </div>

            <?php foreach ([
                'kemiringan_lereng' => ['Lereng (%)', 'number', '0.01'],
                'ketinggian_tempat' => ['Ketinggian (mdpl)', 'number', '1'],
                'curah_hujan' => ['Curah Hujan (mm/tahun)', 'number', '1'],
                'irigasi' => ['Irigasi (m)', 'number', '1'],
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
            <label class="map-draw-label">Titik Koordinat Lahan</label>
            <div id="classification-map" class="map-canvas map-canvas-compact"></div>
            <p class="section-copy">Masukkan latitude/longitude. Marker peta akan berpindah otomatis.</p>
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
                        <th>Lokasi</th>
                        <th>Koordinat</th>
                        <th>K</th>
                        <th>Rekomendasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentResults as $row): ?>
                        <tr>
                            <td><?= esc($row['nama_lahan'] ?: 'Tanpa nama') ?></td>
                            <td><?= esc(number_format((float) $row['latitude'], 6)) ?>, <?= esc(number_format((float) $row['longitude'], 6)) ?></td>
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
<script>
    const reverseGeocodeUrl = <?= json_encode(site_url('klasifikasi/reverse-geocode')) ?>;
    const serviceAreaBounds = L.latLngBounds(
        [-7.7865, 114.1500], // southWest (Curah Kalak)
        [-7.7300, 114.2150]  // northEast (Curah Kalak)
    );
    const map = L.map('classification-map', {
        maxBounds: serviceAreaBounds,
        maxBoundsViscosity: 1.0
    });
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const detectedAddressInput = document.getElementById('detected_address');
    const form = document.querySelector('.classification-form');
    let marker = null;
    let geocodeOk = false;
    let geocodeTimer = null;

    map.fitBounds(serviceAreaBounds);
    map.setMinZoom(map.getBoundsZoom(serviceAreaBounds));

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const updateMarker = () => {
        const lat = parseFloat(latitudeInput.value);
        const lng = parseFloat(longitudeInput.value);
        if (Number.isNaN(lat) || Number.isNaN(lng)) {
            geocodeOk = false;
            detectedAddressInput.value = '';
            return;
        }
        const point = L.latLng(lat, lng);
        if (! serviceAreaBounds.contains(point)) {
            geocodeOk = false;
            detectedAddressInput.value = 'Koordinat di luar area Curah Kalak';
            return;
        }

        if (!marker) {
            marker = L.marker(point).addTo(map);
        } else {
            marker.setLatLng(point);
        }
        map.setView(point, Math.max(map.getZoom(), 16));
    };

    const runReverseGeocode = async () => {
        updateMarker();
        const lat = parseFloat(latitudeInput.value);
        const lng = parseFloat(longitudeInput.value);
        if (Number.isNaN(lat) || Number.isNaN(lng)) {
            geocodeOk = false;
            return;
        }
        const point = L.latLng(lat, lng);
        if (! serviceAreaBounds.contains(point)) {
            geocodeOk = false;
            return;
        }

        detectedAddressInput.value = 'Mendeteksi alamat...';
        geocodeOk = false;

        try {
            const body = new URLSearchParams();
            body.set('latitude', latitudeInput.value);
            body.set('longitude', longitudeInput.value);

            const response = await fetch(reverseGeocodeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: body.toString()
            });
            const json = await response.json();
            if (!response.ok || !json.ok) {
                throw new Error(json.message || 'Gagal membaca alamat otomatis.');
            }

            detectedAddressInput.value = json.address;
            geocodeOk = true;
        } catch (error) {
            detectedAddressInput.value = error.message || 'Gagal membaca alamat otomatis.';
            geocodeOk = false;
        }
    };

    const scheduleReverseGeocode = () => {
        if (geocodeTimer) {
            clearTimeout(geocodeTimer);
        }
        geocodeTimer = setTimeout(runReverseGeocode, 450);
    };

    latitudeInput.addEventListener('input', scheduleReverseGeocode);
    longitudeInput.addEventListener('input', scheduleReverseGeocode);

    form.addEventListener('submit', async (event) => {
        if (geocodeOk) {
            return;
        }
        event.preventDefault();
        await runReverseGeocode();
        if (!geocodeOk) {
            alert('Alamat otomatis belum valid. Periksa koordinat atau koneksi internet lalu coba lagi.');
            return;
        }
        form.submit();
    });

    if (latitudeInput.value && longitudeInput.value) {
        runReverseGeocode();
    }
</script>
<?= $this->endSection() ?>
