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
<section class="page-header">
    <div>
        <h2>Hasil Klasifikasi</h2>
        <p>Rekomendasi utama berasal dari KNN, sedangkan kelas tiap komoditas berasal dari evaluasi nilai kriteria.</p>
    </div>
    <div class="page-actions">
        <a class="btn-inline btn-inline-secondary" href="<?= site_url('klasifikasi') ?>">Klasifikasi Baru</a>
    </div>
</section>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<section class="result-grid">
    <div class="card-section">
        <h3 class="section-title">Ringkasan Lahan</h3>
        <div class="summary-grid">
            <div><strong>Lokasi</strong><span><?= esc($dataUji['nama_lahan'] ?: 'Tanpa nama') ?></span></div>
            <div><strong>Jenis Tanah</strong><span><?= esc($dataUji['jenis_tanah']) ?></span></div>
            <div><strong>Lereng</strong><span><?= esc(number_format((float) $dataUji['kemiringan_lereng'], 2)) ?>%</span></div>
            <div><strong>Ketinggian</strong><span><?= esc((string) $dataUji['ketinggian_tempat']) ?> mdpl</span></div>
            <div><strong>Curah Hujan</strong><span><?= esc((string) $dataUji['curah_hujan']) ?> mm/tahun</span></div>
            <div><strong>Irigasi</strong><span><?= esc((string) ($dataUji['irigasi'] ?? '-')) ?> m</span></div>
            <div><strong>pH Tanah</strong><span><?= esc(number_format((float) $dataUji['ph_tanah'], 2)) ?></span></div>
            <div><strong>Luas Lahan</strong><span><?= esc(number_format((float) $dataUji['luas_lahan'], 2)) ?> Ha</span></div>
            <div><strong>Kedalaman</strong><span><?= esc((string) $dataUji['kedalaman_tanah']) ?> cm</span></div>
        </div>
    </div>

    <div class="card-section">
        <h3 class="section-title">Kelas Semua Komoditas</h3>
        <div class="result-list">
            <?php foreach ($detailRows as $row): ?>
                <div class="result-list-item">
                    <span class="result-list-label"><?= esc($row['nama']) ?></span>
                    <span class="result-list-divider">:</span>
                    <strong class="result-list-value"><?= esc($row['kelas']) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="recommendation-banner">
            <strong>Komoditas Rekomendasi KNN</strong>
            <span><?= esc($hasil['rekomendasi_komoditas']) ?></span>
            <small>Nilai K yang digunakan: <?= esc((string) $hasil['nilai_k']) ?></small>
        </div>
    </div>
</section>

<section class="card-section">
    <h3 class="section-title">Area Lahan</h3>
    <div id="result-map" class="map-canvas map-canvas-compact"></div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
></script>
<script>
    const polygonGeoJson = <?= json_encode($dataUji['polygon_geojson'] ?? null, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const serviceAreaBounds = L.latLngBounds(
        [-7.7865, 114.1500],
        [-7.7300, 114.2150]
    );
    const map = L.map('result-map', {
        maxBounds: serviceAreaBounds,
        maxBoundsViscosity: 1.0
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    map.fitBounds(serviceAreaBounds);

    if (polygonGeoJson) {
        const layer = L.geoJSON(JSON.parse(polygonGeoJson), {
            style: {
                color: '#2f5801',
                weight: 2,
                fillColor: '#8cb369',
                fillOpacity: 0.35
            }
        }).addTo(map);
        map.fitBounds(layer.getBounds(), { padding: [20, 20] });
    }
</script>
<?= $this->endSection() ?>
