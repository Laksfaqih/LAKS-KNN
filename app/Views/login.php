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
    <div class="container">
        <div class="wrapper">
            <section class="form">
                <div class="title">
                    <h1>welcome back!</h1>
                    <p>enter your credentials to access your account</p>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php endif; ?>

                <form class="form-wrapper" action="<?= site_url('login/auth') ?>" method="post">
                    <div class="group-input">
                        <label for="username">username</label>
                        <input id="username" name="username" type="text" value="<?= esc(old('username')) ?>" required>
                    </div>
                    <div class="group-input">
                        <span>
                            <label for="password">Password</label>
                        </span>
                        <input id="password" name="password" type="password" required>
                    </div>
                    <button class="btn" type="submit">Login</button>
                </form>
            </section>
            <section class="card-logo">
                <img src="<?= base_url('assets/tanaman.png') ?>" alt="">
            </section>
        </div>
    </div>
</body>

</html>
