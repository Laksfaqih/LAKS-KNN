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
        <h2>Dashboard</h2>
        <p>Ringkasan statistik klasifikasi, distribusi komoditas, dan riwayat terbaru.</p>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Data Latih</span>
        <strong class="stat-value"><?= esc((string) $totalDataLatih) ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Data Uji</span>
        <strong class="stat-value"><?= esc((string) $totalDataUji) ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Hasil Klasifikasi</span>
        <strong class="stat-value"><?= esc((string) $totalHasil) ?></strong>
    </article>
</section>

<section class="dashboard-panels">
    <article class="card-section">
        <h3 class="section-title">Distribusi Komoditas Rekomendasi</h3>
        <div class="chart-canvas-wrapper">
            <canvas id="distribution-chart"></canvas>
        </div>
    </article>

    <article class="card-section">
        <h3 class="section-title">Mini Map Overview</h3>
        <?php if ($miniMapItems === []): ?>
            <div class="empty-state compact-empty">
                <p>Belum ada area polygon yang bisa ditampilkan.</p>
            </div>
        <?php else: ?>
            <div id="dashboard-map" class="dashboard-map"></div>
        <?php endif; ?>
    </article>
</section>

<section class="card-section">
    <h3 class="section-title">Riwayat Klasifikasi</h3>
    <?php if ($historyRows === []): ?>
        <div class="empty-state compact-empty">
            <p>Belum ada riwayat klasifikasi.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table history-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Lahan</th>
                        <th>Rekomendasi</th>
                        <th>K</th>
                        <th>Kelas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historyRows as $row): ?>
                        <tr>
                            <td><?= esc(date('Y-m-d', strtotime((string) $row['tanggal_input']))) ?></td>
                            <td><?= esc($row['nama_lahan'] ?: 'Tanpa nama') ?></td>
                            <td><span class="badge"><?= esc($row['rekomendasi_komoditas']) ?></span></td>
                            <td><?= esc((string) $row['nilai_k']) ?></td>
                            <?php
                            $targetKelas = '-';
                            if ($row['komoditas_rekomendasi_id'] ?? null) {
                                $targetKelas = $row['hasil_detail'][(int) $row['komoditas_rekomendasi_id']] ?? '-';
                            }
                            ?>
                            <td><span class="badge"><?= esc($targetKelas) ?></span></td>
                            <td><a class="link-edit" href="<?= site_url('klasifikasi/hasil/' . $row['id']) ?>">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>
<?php if ($miniMapItems !== []): ?>
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>
<?php endif; ?>
<script>
    const chartItems = <?= json_encode($chartItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    new Chart(document.getElementById('distribution-chart'), {
        type: 'bar',
        data: {
            labels: chartItems.map((item) => item.nama),
            datasets: [{
                label: 'Jumlah rekomendasi',
                data: chartItems.map((item) => item.total),
                backgroundColor: chartItems.map((item) => item.warna_peta)
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

    <?php if ($miniMapItems !== []): ?>
    const miniMapItems = <?= json_encode($miniMapItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const dashboardMap = L.map('dashboard-map');

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(dashboardMap);

    const layerGroup = L.featureGroup().addTo(dashboardMap);
    miniMapItems.forEach((item) => {
        const layer = L.geoJSON(JSON.parse(item.polygon_geojson), {
            style: {
                color: item.warna_peta,
                fillColor: item.warna_peta,
                fillOpacity: 0.35,
                weight: 2
            }
        });
        layer.bindPopup(`<strong>${item.nama_lahan || 'Tanpa nama'}</strong><br>${item.rekomendasi_komoditas}`);
        layer.addTo(layerGroup);
    });

    dashboardMap.fitBounds(layerGroup.getBounds(), { padding: [20, 20] });
    <?php endif; ?>
</script>
<?= $this->endSection() ?>
