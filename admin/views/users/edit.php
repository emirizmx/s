<?php require 'views/layouts/header.php'; ?>

<div class="content-header">
    <div class="header-left">
        <h1>Kullanıcı Düzenle</h1>
    </div>
    <div class="header-right">
        <a href="/admin/users" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Geri Dön
        </a>
    </div>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="/admin/users/edit?id=<?php echo $user['id']; ?>" method="POST">
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       value="<?php echo htmlspecialchars($user['username']); ?>" 
                       class="form-control" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="email">E-posta</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" 
                       class="form-control" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="credits">Krediler</label>
                <input type="number" 
                       id="credits" 
                       name="credits" 
                       value="<?php echo $user['credits']; ?>" 
                       class="form-control" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="password">Yeni Şifre (Boş bırakılabilir)</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-control">
                <small class="form-text">Şifreyi değiştirmek istemiyorsanız boş bırakın.</small>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" 
                           name="is_active" 
                           <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                    Hesap Aktif
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<?php require 'views/layouts/footer.php'; ?> 