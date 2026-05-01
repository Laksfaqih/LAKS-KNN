<aside class="sidebar">
    <?php
    $displayName = session('nama_lengkap') ?: (session('username') ?: 'Admin');
    $currentPath = trim(service('uri')->getPath(), '/');
    $menuItems = [
        [
            'label' => 'Dashboard',
            'href'  => site_url('dashboard'),
            'icon'  => 'fa-solid fa-chart-line',
            'match' => ['dashboard'],
        ],
        [
            'label' => 'Data Latih',
            'href'  => site_url('data-latih'),
            'icon'  => 'fa-solid fa-database',
            'match' => ['data-latih', 'data-latih/create'],
        ],
        [
            'label' => 'Kriteria',
            'href'  => site_url('kriteria'),
            'icon'  => 'fa-solid fa-seedling',
            'match' => ['kriteria', 'kriteria/create'],
        ],
        [
            'label' => 'Nilai Kriteria',
            'href'  => site_url('nilai-kriteria'),
            'icon'  => 'fa-solid fa-sliders',
            'match' => ['nilai-kriteria', 'nilai-kriteria/create'],
        ],
        [
            'label' => 'Klasifikasi',
            'href'  => site_url('klasifikasi'),
            'icon'  => 'fa-solid fa-brain',
            'match' => ['klasifikasi'],
        ],
        [
            'label' => 'Mapping',
            'href'  => site_url('mapping'),
            'icon'  => 'fa-solid fa-map-location-dot',
            'match' => ['mapping'],
        ],
    ];
    ?>

    <div class="sidebar-shell">
        <div class="sidebar-brand">
            <div class="sidebar-brand-mark">KL</div>
            <div>
                <strong>Sistem Klasifikasi</strong>
                <p>Kelayakan Lahan</p>
            </div>
        </div>

        <div class="sidebar-profile">
            <div class="sidebar-avatar">
                <?= esc(strtoupper(substr($displayName, 0, 1))) ?>
            </div>
            <div class="sidebar-profile-copy">
                <strong><?= esc($displayName) ?></strong>
                <span>Admin aktif</span>
            </div>
        </div>

        <div class="sidebar-section">
            <span class="sidebar-section-title">Navigasi</span>
            <ul class="list-menu">
                <?php foreach ($menuItems as $item): ?>
                    <?php
                    $isActive = false;
                    foreach ($item['match'] as $match) {
                        if ($currentPath === $match || str_starts_with($currentPath, $match . '/')) {
                            $isActive = true;
                            break;
                        }
                    }
                    ?>
                    <li>
                        <a class="<?= $isActive ? 'is-active' : '' ?>" href="<?= $item['href'] ?>">
                            <span class="menu-icon"><i class="<?= esc($item['icon']) ?>"></i></span>
                            <span><?= esc($item['label']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="sidebar-footer">
            <a class="sidebar-logout" href="<?= site_url('logout') ?>">
                <span class="menu-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                <span>Logout</span>
            </a>
        </div>
    </div>
</aside>
