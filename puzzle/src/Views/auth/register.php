<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Puzzle Oyunu</title>
    <link rel="stylesheet" href="/puzzle/assets/css/style.css">
    <link rel="stylesheet" href="/puzzle/assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Puzzle Oyunu</h1>
            <h2>Kayıt Ol</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <form action="/puzzle/register" method="post">
                <div class="form-group">
                    <label for="username">Kullanıcı Adı:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">E-posta:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Şifre:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-primary">Kayıt Ol</button>
            </form>
            
            <div class="auth-links">
                <p>Zaten hesabınız var mı? <a href="/puzzle/login">Giriş Yap</a></p>
            </div>
        </div>
    </div>
</body>
</html> 