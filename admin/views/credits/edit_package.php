<?php require 'views/layouts/header.php'; ?>

<div class="content-header">
    <div class="header-left">
        <h1><?php echo $pageTitle; ?></h1>
    </div>
    <div class="header-right">
        <a href="/admin/credits/packages" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Geri Dön
        </a>
    </div>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="<?php echo $package['id'] > 0 ? '/admin/credits/packages/edit?id=' . $package['id'] : '/admin/credits/packages'; ?>" method="POST">
            <input type="hidden" name="id" value="<?php echo $package['id']; ?>">
            <input type="hidden" name="action" value="<?php echo $package['id'] > 0 ? 'update' : 'create'; ?>">
            
            <div class="form-group">
                <label for="name">Paket Adı</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="<?php echo htmlspecialchars($package['name']); ?>" 
                       class="form-control" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="credits">Kredi Miktarı</label>
                <input type="number" 
                       id="credits" 
                       name="credits" 
                       value="<?php echo (int)$package['credits']; ?>" 
                       class="form-control" 
                       min="1"
                       required>
            </div>
            
            <div class="form-group">
                <label for="price">Fiyat (TL)</label>
                <input type="number" 
                       id="price" 
                       name="price" 
                       value="<?php echo number_format($package['price'], 2, '.', ''); ?>" 
                       class="form-control" 
                       min="0"
                       step="0.01"
                       required>
            </div>
            
            <div class="form-group">
                <label for="discount_percentage">İndirim Yüzdesi (%)</label>
                <input type="number" 
                       id="discount_percentage" 
                       name="discount_percentage" 
                       value="<?php echo (int)$package['discount_percentage']; ?>" 
                       class="form-control" 
                       min="0"
                       max="100">
                <small class="form-text">İndirim yüzdesi 0-100 arasında bir değer olmalıdır</small>
            </div>
            
            <div class="form-group checkbox-group">
                <label class="checkbox-container">
                    <input type="checkbox" 
                           name="is_active" 
                           value="1" 
                           <?php echo $package['is_active'] ? 'checked' : ''; ?>>
                    <span class="checkmark"></span>
                    Paket Aktif
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