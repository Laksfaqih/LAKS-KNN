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
        <h2>Mapping</h2>
        <p>Warna area mewakili rekomendasi KNN, popup menampilkan evaluasi kelas semua komoditas.</p>
    </div>
</section>

<section class="card-section">
    <?php if ($mapPolygons === []): ?>
        <div class="empty-state">
            <h3>Belum ada area lahan</h3>
            <p>Tambahkan hasil klasifikasi dengan polygon untuk melihat peta.</p>
        </div>
    <?php else: ?>
        <div id="map-canvas" class="map-canvas"></div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if ($mapPolygons !== []): ?>
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>
    <script>
        const mapPolygons = <?= json_encode($mapPolygons, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const legendItems = <?= json_encode($legendItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const map = L.map('map-canvas');

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const bounds = [];
        mapPolygons.forEach((item) => {
            const geoJson = JSON.parse(item.polygon_geojson);
            const layer = L.geoJSON(geoJson, {
                style: {
                    color: item.rekomendasi_warna,
                    fillColor: item.rekomendasi_warna,
                    fillOpacity: 0.45,
                    weight: 2
                }
            }).addTo(map);

            let details = '';
            item.hasil_detail.forEach((detail) => {
                details += `<p><strong>${detail.nama}:</strong> ${detail.kelas}</p>`;
            });

            layer.bindPopup(`
                <div class="map-popup">
                    <h4>${item.nama_lahan || 'Tanpa nama'}</h4>
                    <p><strong>Rekomendasi KNN:</strong> ${item.rekomendasi_komoditas}</p>
                    <p><strong>Jenis Tanah:</strong> ${item.jenis_tanah}</p>
                    <p><strong>Lereng:</strong> ${item.kemiringan_lereng}%</p>
                    <p><strong>pH:</strong> ${item.ph_tanah}</p>
                    <p><strong>Ketinggian:</strong> ${item.ketinggian_tempat} mdpl</p>
                    <p><strong>Kedalaman:</strong> ${item.kedalaman_tanah} cm</p>
                    <p><strong>Curah Hujan:</strong> ${item.curah_hujan} mm/tahun</p>
                    <hr>
                    ${details}
                </div>
            `);

            bounds.push(layer.getBounds());
        });

        if (bounds.length > 0) {
            const group = L.featureGroup(bounds.map((bound) => L.rectangle(bound))).addTo(map);
            map.fitBounds(group.getBounds(), { padding: [20, 20] });
            group.remove();
        }

        const legend = L.control({ position: 'topright' });
        legend.onAdd = function () {
            const div = L.DomUtil.create('div', 'map-legend');
            let html = '<strong>Legenda</strong>';
            legendItems.forEach((item) => {
                html += `<div><span style="background:${item.warna_peta}"></span>${item.nama}</div>`;
            });
            div.innerHTML = html;
            return div;
        };
        legend.addTo(map);
    </script>
<?php endif; ?>
<?= $this->endSection() ?>
