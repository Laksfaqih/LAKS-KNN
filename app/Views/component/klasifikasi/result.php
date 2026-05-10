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
        <p>Hasil berasal dari KNN spreadsheet manual dengan tetangga dari seluruh data latih lintas tanaman.</p>
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
            <div><strong>Tanaman</strong><span><?= esc($targetCommodity['nama'] ?? '-') ?></span></div>
            <div><strong>Kelas Hasil</strong><span><?= esc($hasil['kelas_hasil'] ?? '-') ?></span></div>
            <div><strong>Jenis Tanah</strong><span><?= esc($dataUji['jenis_tanah']) ?></span></div>
            <div><strong>Lereng</strong><span><?= esc(number_format((float) $dataUji['kemiringan_lereng'], 2)) ?>%</span></div>
            <div><strong>Ketinggian</strong><span><?= esc((string) $dataUji['ketinggian_tempat']) ?> mdpl</span></div>
            <div><strong>Curah Hujan</strong><span><?= esc((string) $dataUji['curah_hujan']) ?> mm/tahun</span></div>
            <div><strong>Irigasi</strong><span><?= esc((string) ($dataUji['irigasi'] ?? '-')) ?> m</span></div>
            <div><strong>pH Tanah</strong><span><?= esc(number_format((float) $dataUji['ph_tanah'], 2)) ?></span></div>
            <div><strong>Kedalaman</strong><span><?= esc((string) $dataUji['kedalaman_tanah']) ?> cm</span></div>
        </div>
    </div>

    <div class="card-section">
        <h3 class="section-title">Ringkasan KNN</h3>

        <div class="recommendation-banner">
            <strong>Rekomendasi Peta</strong>
            <span><?= esc($hasil['rekomendasi_komoditas']) ?></span>
            <small>Nilai K: <?= esc((string) $hasil['nilai_k']) ?> | Kelas: <?= esc($hasil['kelas_hasil'] ?? '-') ?></small>
        </div>
    </div>
</section>

<section class="card-section">
    <h3 class="section-title">Area Lahan</h3>
    <div id="result-map" class="map-canvas map-canvas-compact"></div>
</section>

<section class="card-section">
    <h3 class="section-title">Kelas Per Tanaman</h3>
    <?php if (($detailRows ?? []) === []): ?>
        <div class="empty-state compact-empty">
            <p>Detail kelas belum tersedia.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table history-table">
                <thead>
                    <tr>
                        <th>Tanaman</th>
                        <th>Kelas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detailRows as $detail): ?>
                        <tr>
                            <td><?= esc($detail['nama']) ?></td>
                            <td><span class="badge"><?= esc($detail['kelas']) ?></span></td>
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
                color: <?= json_encode($mapColor) ?>,
                weight: 2,
                fillColor: <?= json_encode($mapColor) ?>,
                fillOpacity: 0.35
            }
        }).addTo(map);
        map.fitBounds(layer.getBounds(), { padding: [20, 20] });
    }
</script>
<?= $this->endSection() ?>
