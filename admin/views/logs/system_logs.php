<?php require 'views/layouts/header.php'; ?>

<div class="content-header">
    <div class="header-left">
        <h1>Sistem Logları</h1>
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
        <form action="/admin/logs/system" method="GET" class="filter-form">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <input type="text" 
                               name="search" 
                               value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                               placeholder="Açıklama veya veri ara..." 
                               class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <select name="module" class="form-control">
                            <option value="">Tüm Modüller</option>
                            <?php foreach ($modules as $mod): ?>
                                <option value="<?php echo htmlspecialchars($mod); ?>" <?php echo ($module ?? '') === $mod ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mod); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <select name="action" class="form-control">
                            <option value="">Tüm İşlemler</option>
                            <?php foreach ($actions as $act): ?>
                                <option value="<?php echo htmlspecialchars($act); ?>" <?php echo ($action ?? '') === $act ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($act); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <input type="date" 
                               name="date_from" 
                               value="<?php echo htmlspecialchars($dateFrom ?? ''); ?>" 
                               class="form-control"
                               placeholder="Başlangıç tarihi">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <input type="date" 
                               name="date_to" 
                               value="<?php echo htmlspecialchars($dateTo ?? ''); ?>" 
                               class="form-control"
                               placeholder="Bitiş tarihi">
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12 text-right">
                    <a href="/admin/logs/system" class="btn btn-secondary">
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
                        <th>Modül</th>
                        <th>İşlem</th>
                        <th>Açıklama</th>
                        <th>IP Adresi</th>
                        <th>Tarih</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td>
                                    <?php if ($log['user_id']): ?>
                                        <a href="/admin/users/edit?id=<?php echo $log['user_id']; ?>" title="<?php echo htmlspecialchars($log['username'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($log['username'] ?? 'Kullanıcı #' . $log['user_id']); ?>
                                        </a>
                                    <?php else: ?>
                                        Misafir
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge"><?php echo htmlspecialchars($log['module']); ?></span></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($log['description']); ?>">
                                    <?php echo htmlspecialchars($log['description']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <a href="/admin/logs/system/detail?id=<?php echo $log['id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Kayıt bulunamadı</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Sayfalama -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="/admin/logs/system?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&module=<?php echo urlencode($module ?? ''); ?>&action=<?php echo urlencode($action ?? ''); ?>&date_from=<?php echo urlencode($dateFrom ?? ''); ?>&date_to=<?php echo urlencode($dateTo ?? ''); ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="/admin/logs/system?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>&module=<?php echo urlencode($module ?? ''); ?>&action=<?php echo urlencode($action ?? ''); ?>&date_from=<?php echo urlencode($dateFrom ?? ''); ?>&date_to=<?php echo urlencode($dateTo ?? ''); ?>" 
                       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="/admin/logs/system?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&module=<?php echo urlencode($module ?? ''); ?>&action=<?php echo urlencode($action ?? ''); ?>&date_from=<?php echo urlencode($dateFrom ?? ''); ?>&date_to=<?php echo urlencode($dateTo ?? ''); ?>" class="page-link">
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
    background-color: #f0f0f0;
}

.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>

<?php require 'views/layouts/footer.php'; ?> 