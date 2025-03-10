<?php require 'views/layouts/header.php'; ?>

<div class="content-header">
    <div class="header-left">
        <h1>Sistem Log Detayı #<?php echo $log['id']; ?></h1>
    </div>
    <div class="header-right">
        <a href="/admin/logs/system" class="btn btn-secondary">
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
                <div class="detail-label">Kullanıcı:</div>
                <div class="detail-value">
                    <?php if ($log['user_id']): ?>
                        <a href="/admin/users/edit?id=<?php echo $log['user_id']; ?>" class="user-link">
                            <?php echo htmlspecialchars($log['username'] ?? 'Kullanıcı #' . $log['user_id']); ?>
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    <?php else: ?>
                        Misafir
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Modül:</div>
                <div class="detail-value"><?php echo htmlspecialchars($log['module']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">İşlem:</div>
                <div class="detail-value"><?php echo htmlspecialchars($log['action']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Açıklama:</div>
                <div class="detail-value"><?php echo htmlspecialchars($log['description']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">IP Adresi:</div>
                <div class="detail-value"><?php echo htmlspecialchars($log['ip_address']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Tarih:</div>
                <div class="detail-value"><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></div>
            </div>
            
            <?php if (!empty($log['data'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Veri:</div>
                    <div class="detail-value">
                        <?php if (isset($log['data_decoded']) && is_array($log['data_decoded'])): ?>
                            <div class="json-data">
                                <pre><code><?php echo json_encode($log['data_decoded'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></code></pre>
                            </div>
                        <?php else: ?>
                            <pre><code><?php echo htmlspecialchars($log['data']); ?></code></pre>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
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

pre {
    margin: 0;
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

.json-data {
    max-height: 300px;
    overflow-y: auto;
}
</style>

<?php require 'views/layouts/footer.php'; ?> 