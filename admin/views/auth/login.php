<?php
// Session başlatılmış olmalı
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Yönetici Paneli</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/admin/login" class="auth-form">
                <div class="form-group">
                    <label for="email">E-posta</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           required 
                           autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Giriş Yap
                </button>
            </form>

            <div class="auth-footer">
                <a href="/" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Ana Sayfaya Dön
                </a>
            </div>
        </div>
    </div>
</body>
</html> 