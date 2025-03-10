<?php
// Session kontrolü kaldırıldı çünkü config.php'de zaten yapılıyor
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <a href="/" class="logo">Dijital Hediye</a>
                </div>
                
                <button class="mobile-menu-toggle" aria-label="Menüyü aç/kapat">
                    <i class="fas fa-bars"></i>
                </button>

                <nav class="nav-menu">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="user-credits">
                            <i class="fas fa-coins"></i>
                            <?php echo number_format($_SESSION['credits']); ?> Kredi
                        </span>
                        <a href="/credits" class="nav-link">Kredi Yükle</a>
                        <a href="/profile" class="nav-link">Profilim</a>
                        <a href="/logout" class="nav-link">Çıkış Yap</a>
                    <?php else: ?>
                        <a href="/login" class="nav-link">Giriş Yap</a>
                        <a href="/register" class="nav-link">Kayıt Ol</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <!-- JavaScript dosyalarını body sonuna ekleyelim -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html> 