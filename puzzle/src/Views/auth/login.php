<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Puzzle Oyunu</title>
    <link rel="stylesheet" href="/puzzle/assets/css/style.css">
    <link rel="stylesheet" href="/puzzle/assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Puzzle Oyunu</h1>
            <h2>Giriş Yap</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <form action="/puzzle/login" method="post">
                <div class="form-group">
                    <label for="email">E-posta:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Şifre:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-primary">Giriş Yap</button>
            </form>
            
            <div class="auth-links">
                <p>Hesabınız yok mu? <a href="/puzzle/register">Kayıt Ol</a></p>
            </div>
        </div>
    </div>
</body>
</html> 