<?php require 'views/layouts/header.php'; ?>

<div class="content-header">
    <div class="header-left">
        <h1>Giriş Logları</h1>
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
        <!-- Filtreleme formu -->
        <form action="/admin/logs/login" method="GET" class="filter-form">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <input type="text" 
                               name="search" 
                               value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                               placeholder="Kullanıcı adı, e-posta veya IP ara..." 
                               class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <select name="status" class="form-control">
                            <option value="">Tüm Durumlar</option>
                            <option value="success" <?php echo ($status ?? '') === 'success' ? 'selected' : ''; ?>>Başarılı</option>
                            <option value="failed" <?php echo ($status ?? '') === 'failed' ? 'selected' : ''; ?>>Başarısız</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                </div>
                <div class="col-md-3 text-right">
                    <a href="/admin/logs/login" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> Filtreleri Temizle
                    </a>
                </div>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kullanıcı</th>
                        <th>E-posta</th>
                        <th>IP Adresi</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td><?php echo htmlspecialchars($log['username'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($log['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td>
                                    <?php if ($log['status'] === 'success'): ?>
                                        <span class="badge badge-success">Başarılı</span>
                                    <?php else: ?>
                                        <span class="badge badge-error">Başarısız</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <a href="/admin/logs/login/detail?id=<?php echo $log['id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Kayıt bulunamadı</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Sayfalama -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="/admin/logs/login?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="/admin/logs/login?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>" 
                       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="/admin/logs/login?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>" class="page-link">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.filter-form {
    margin-bottom: 20px;
}

.pagination {
    display: flex;
    margin-top: 20px;
    justify-content: center;
}

.page-link {
    padding: 5px 10px;
    margin: 0 5px;
    border: 1px solid #ddd;
    border-radius: 3px;
    color: #4a90e2;
    text-decoration: none;
}

.page-link.active {
    background-color: #4a90e2;
    color: white;
    border-color: #4a90e2;
}

.badge {
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 12px;
}
</style>

<?php require 'views/layouts/footer.php'; ?> 