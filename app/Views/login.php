<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('fontawesome/css/all.min.css') ?>">
</head>

<body>
    <div class="container">
        <div class="wrapper">
            <section class="form">
                <div class="title">
                    <h1>welcome back!</h1>
                    <p>enter your cridentials to access your account</p>
                </div>
                <form class="form-wrapper" action="">
                    <div class="group-input">
                        <label for="">username</label>
                        <input type="text">
                    </div>
                    <div class="group-input">
                        <span>
                            <label for="">Password</label>
                            <a href="">forgot password</a>
                        </span>
                        <input type="text">
                    </div>
                    <div class="ceckbox">
                        <input type="checkbox">
                        <label for="">remember me</label>
                    </div>
                    <button class="btn">Login</button>
                </form>
            </section>
            <section class="card-logo">
                <img src="<?= base_url('assets/tanaman.png') ?>" alt="">
            </section>
        </div>
    </div>
</body>

</html>