<?php require 'views/layouts/header.php'; ?>

<div class="content-header">
    <div class="header-left">
        <h1>Giriş Log Detayı #<?php echo $log['id']; ?></h1>
    </div>
    <div class="header-right">
        <a href="/admin/logs/login" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Geri Dön
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="log-detail">
            <div class="detail-row">
                <div class="detail-label">ID:</div>
                <div class="detail-value"><?php echo $log['id']; ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Kullanıcı ID:</div>
                <div class="detail-value">
                    <?php if ($log['user_id']): ?>
                        <a href="/admin/users/edit?id=<?php echo $log['user_id']; ?>" class="user-link">
                            <?php echo $log['user_id']; ?>
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Kullanıcı Adı:</div>
                <div class="detail-value"><?php echo htmlspecialchars($log['username'] ?? '-'); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">E-posta:</div>
                <div class="detail-value"><?php echo htmlspecialchars($log['email'] ?? '-'); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">IP Adresi:</div>
                <div class="detail-value"><?php echo htmlspecialchars($log['ip_address']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Durum:</div>
                <div class="detail-value">
                    <?php if ($log['status'] === 'success'): ?>
                        <span class="badge badge-success">Başarılı</span>
                    <?php else: ?>
                        <span class="badge badge-error">Başarısız</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($log['status'] === 'failed' && $log['fail_reason']): ?>
                <div class="detail-row">
                    <div class="detail-label">Başarısızlık Nedeni:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($log['fail_reason']); ?></div>
                </div>
            <?php endif; ?>
            
            <div class="detail-row">
                <div class="detail-label">Tarayıcı Bilgisi:</div>
                <div class="detail-value">
                    <code><?php echo htmlspecialchars($log['user_agent'] ?? '-'); ?></code>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Tarih:</div>
                <div class="detail-value"><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></div>
            </div>
        </div>
    </div>
</div>

<style>
.log-detail {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.detail-row {
    display: flex;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.detail-label {
    font-weight: bold;
    width: 200px;
    flex-shrink: 0;
}

.detail-value {
    flex-grow: 1;
}

.user-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: var(--primary-color);
}

.badge {
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 12px;
}

code {
    display: block;
    white-space: pre-wrap;
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
    overflow-x: auto;
}
</style>

<?php require 'views/layouts/footer.php'; ?> 