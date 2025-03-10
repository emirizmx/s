<?php require 'views/layouts/header.php'; ?>

<div class="content-header">
    <div class="header-left">
        <h1><?php echo $pageTitle; ?></h1>
    </div>
    <div class="header-right">
        <a href="/admin/games" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Geri Dön
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="/admin/games/edit?id=<?php echo $game['id']; ?>" method="POST">
            <input type="hidden" name="id" value="<?php echo $game['id']; ?>">
            
            <div class="form-group">
                <label for="name">Oyun Adı</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="<?php echo htmlspecialchars($game['name']); ?>" 
                       class="form-control" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="description">Açıklama</label>
                <textarea id="description" 
                          name="description" 
                          class="form-control" 
                          required><?php echo htmlspecialchars($game['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="credits">Kredi Miktarı</label>
                <input type="number" 
                       id="credits" 
                       name="credits" 
                       value="<?php echo $game['credits']; ?>" 
                       class="form-control" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="voice_credits">Ses Oluşturma Kredisi</label>
                <input type="number" 
                       id="voice_credits" 
                       name="voice_credits" 
                       value="<?php echo $game['voice_credits']; ?>" 
                       class="form-control" 
                       required>
                <small class="form-text text-muted">
                    Hikaye için ses oluşturma maliyeti (Sadece Dijital Hikaye için geçerli)
                </small>
            </div>
            
            <div class="form-group">
                <label for="route">Yönlendirme Adresi</label>
                <input type="text" 
                       id="route" 
                       name="route" 
                       value="<?php echo htmlspecialchars($game['route']); ?>" 
                       class="form-control" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="image_path">Resim Yolu</label>
                <input type="text" 
                       id="image_path" 
                       name="image_path" 
                       value="<?php echo htmlspecialchars($game['image_path']); ?>" 
                       class="form-control" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="display_order">Görüntülenme Sırası</label>
                <input type="number" 
                       id="display_order" 
                       name="display_order" 
                       value="<?php echo $game['display_order']; ?>" 
                       class="form-control" 
                       required>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" 
                           name="is_active" 
                           <?php echo $game['is_active'] ? 'checked' : ''; ?>>
                    Oyun Aktif
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