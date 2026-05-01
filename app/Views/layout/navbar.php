<nav class="navbar">
    <?php $displayName = session('nama_lengkap') ?: (session('username') ?: 'Admin'); ?>
    <div class="navbar-actions">
        <div class="navbar-user-chip">
            <div class="navbar-user-avatar">
                <?= esc(strtoupper(substr($displayName, 0, 1))) ?>
            </div>
            <div class="navbar-user-copy">
                <strong><?= esc($displayName) ?></strong>
                <span>Administrator</span>
            </div>
        </div>
        <a class="logout-link" href="<?= site_url('logout') ?>">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>
