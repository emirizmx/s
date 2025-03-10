<?php require 'views/layouts/header.php'; ?>

<div class="content-header">
    <div class="header-left">
        <h1>Kredi Paketleri</h1>
    </div>
    <div class="header-right">
        <a href="/admin/credits/packages/edit" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Yeni Paket Ekle
        </a>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Paket Adı</th>
                    <th>Kredi</th>
                    <th>Fiyat (TL)</th>
                    <th>İndirim (%)</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($packages as $package): ?>
                    <tr>
                        <td><?php echo $package['id']; ?></td>
                        <td><?php echo htmlspecialchars($package['name']); ?></td>
                        <td><?php echo number_format($package['credits']); ?></td>
                        <td><?php echo number_format($package['price'], 2); ?> ₺</td>
                        <td><?php echo $package['discount_percentage']; ?>%</td>
                        <td>
                            <?php if ($package['is_active']): ?>
                                <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-error">Pasif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/admin/credits/packages/edit?id=<?php echo $package['id']; ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <button type="button" 
                                    class="btn btn-sm btn-danger delete-package" 
                                    data-id="<?php echo $package['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($package['name']); ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($packages)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Henüz hiç kredi paketi bulunmuyor.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Silme onay modalı -->
<div class="modal" id="deleteModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kredi Paketi Sil</h5>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Bu kredi paketini silmek istediğinize emin misiniz?</p>
                <p><strong id="packageName"></strong></p>
            </div>
            <div class="modal-footer">
                <form action="/admin/credits/packages" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="packageId">
                    
                    <button type="button" class="btn btn-secondary close-modal">İptal</button>
                    <button type="submit" class="btn btn-danger">Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Silme modalı işlemleri
    const deleteModal = document.getElementById('deleteModal');
    const packageIdInput = document.getElementById('packageId');
    const packageNameElement = document.getElementById('packageName');
    
    // Silme butonlarına tıklama olayı ekle
    document.querySelectorAll('.delete-package').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            
            packageIdInput.value = id;
            packageNameElement.textContent = name;
            
            deleteModal.classList.add('show');
        });
    });
    
    // Modalı kapatma işlemleri
    document.querySelectorAll('.close-modal').forEach(button => {
        button.addEventListener('click', function() {
            deleteModal.classList.remove('show');
        });
    });
    
    // Modal dışına tıklandığında kapat
    window.addEventListener('click', function(event) {
        if (event.target === deleteModal) {
            deleteModal.classList.remove('show');
        }
    });
});
</script>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 100;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-dialog {
    width: 100%;
    max-width: 500px;
}

.modal-content {
    background-color: #fff;
    border-radius: 5px;
    box-shadow: 0 3px 7px rgba(0,0,0,0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #ddd;
}

.modal-body {
    padding: 1rem;
}

.modal-footer {
    padding: 1rem;
    border-top: 1px solid #ddd;
    display: flex;
    justify-content: flex-end;
}

.close-modal {
    cursor: pointer;
    background: none;
    border: none;
    font-size: 1.5rem;
    font-weight: bold;
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
}

.badge-success {
    background-color: #2ecc71;
    color: white;
}

.badge-error {
    background-color: #e74c3c;
    color: white;
}
</style>

<?php require 'views/layouts/footer.php'; ?> 