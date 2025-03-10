<?php require 'views/layouts/header.php'; ?>

<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3>Toplam Kullanıcı</h3>
            <p><?php echo number_format($stats['user_count']); ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-exchange-alt"></i>
        </div>
        <div class="stat-info">
            <h3>Toplam İşlem</h3>
            <p><?php echo number_format($stats['transaction_count']); ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-lira-sign"></i>
        </div>
        <div class="stat-info">
            <h3>Toplam Gelir</h3>
            <p><?php echo number_format($stats['total_revenue'], 2); ?> TL</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-info">
            <h3>Bugünkü Gelir</h3>
            <p><?php echo number_format($stats['today_revenue'], 2); ?> TL</p>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <div class="card-header">
            <h2>Son İşlemler</h2>
            <a href="/admin/credits/transactions" class="btn btn-sm">Tümünü Gör</a>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kullanıcı</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $transaction): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                            <td><?php echo number_format($transaction['amount'], 2); ?> TL</td>
                            <td>
                                <span class="status-badge <?php echo $transaction['status']; ?>">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="dashboard-card">
        <div class="card-header">
            <h2>Son Kayıt Olan Kullanıcılar</h2>
            <a href="/admin/users" class="btn btn-sm">Tümünü Gör</a>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kullanıcı Adı</th>
                        <th>E-posta</th>
                        <th>Krediler</th>
                        <th>Kayıt Tarihi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo number_format($user['credits']); ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require 'views/layouts/footer.php'; ?> 