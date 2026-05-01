<aside class="sidebar">
    <?php $displayName = session('nama_lengkap') ?: (session('username') ?: 'Admin'); ?>
    <ul class="profil-aside">
        <li class="profil-img">
            <img src="" alt="">
        </li>
        <li>
            <p><?= esc($displayName) ?></p>
            <span> online</span>
        </li>
    </ul>

    <li class="search">
        <input type="text">
        <div>
            <!-- search -->
        </div>
    </li>

    <ul class="list-menu">
        <li><a href="<?= site_url('dashboard') ?>">Dashboard</a></li>
        <li><a href="<?= site_url('data-latih') ?>">Data Latih</a></li>
        <li><a href="">Kriteria</a></li>
        <li><a href="">Nilai Kriteria</a></li>
        <li><a href="">Klasifikasi</a></li>
        <li><a href="">Mapping</a></li>
        <li><a href="<?= site_url('logout') ?>">Logout</a></li>
    </ul>
</aside>
