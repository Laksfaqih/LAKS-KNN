<nav class="navbar">
    <?php $displayName = session('nama_lengkap') ?: (session('username') ?: 'Admin'); ?>
    <div class="logo">
        <img src="<?= base_url('') ?>" alt="">
        <span class="title-nav">
            <h1>SIG</h1>
            <p>Klasifikasi Lahan</p>
        </span>
    </div>
    <div class="profil-nav">
        <li>
            <!-- hamburger -->
            <i class="fa-solid fa-bars"></i>
            <span class="title-hal"><?= $title ?></span>
        </li>
        <li>
            <div class="profil-img">
                <img src="" alt="">
            </div>
            <p><?= esc($displayName) ?></p>
            <a class="logout-link" href="<?= site_url('logout') ?>">Logout</a>
        </li>
    </div>
</nav>
