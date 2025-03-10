<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Puzzle Oyunu</title>
    <link rel="stylesheet" href="/puzzle/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container header-content">
            <a href="/puzzle" class="logo">Puzzle Oyunu</a>
            
            <nav class="nav-menu">
                <a href="/puzzle" class="nav-link">Ana Sayfa</a>
                <a href="/puzzle/create" class="nav-link">Yeni Puzzle</a>
            </nav>
            
            <div class="auth-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="credit-info">
                        Kredi: <?php echo number_format($_SESSION['credits'] ?? 0); ?>
                    </span>
                    <a href="<?php echo MAIN_SITE_URL; ?>" class="btn btn-secondary">
                        Ana Siteye Dön
                    </a>
                    <a href="<?php echo MAIN_SITE_URL; ?>/logout" class="btn btn-primary">Çıkış Yap</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
</body>
</html> 