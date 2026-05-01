<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="dashboard">
        <?= $this->include('layout/navbar.php') ?>
        <div class="main-content">
            <?= $this->include('layout/sidebar.php') ?>
            <main class="content">
                <?= $this->renderSection('content') ?>
            </main>
            <?= $this->include('layout/footer.php') ?>
        </div>
    </div>
</body>

</html>