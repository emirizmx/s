<?php require 'views/layouts/header.php'; ?>

<div class="content-header">
    <div class="header-left">
        <h1>Kredi İşlemleri</h1>
    </div>
    <div class="header-right">
        <form action="/admin/credits/transactions" method="GET" class="search-form">
            <input type="text" 
                   name="search" 
                   value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="İşlem ara..."
                   class="search-input">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

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
        <table class="table" id="transactionsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kullanıcı</th>
                    <th>İşlem ID</th>
                    <th>Tutar</th>
                    <th>Krediler</th>
                    <th>Ödeme Detayı</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?php echo $transaction['id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($transaction['username']); ?>
                            <br>
                            <small><?php echo htmlspecialchars($transaction['email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                        <td><?php echo number_format($transaction['amount'], 2); ?> ₺</td>
                        <td><?php echo number_format($transaction['credits']); ?></td>
                        <td>
                            <?php if ($transaction['payment_method'] === 'bank_transfer' && $transaction['notification_id']): ?>
                                <div class="payment-details">
                                    <div><strong>Banka:</strong> <?php echo htmlspecialchars($transaction['bank_name']); ?></div>
                                    <div><strong>Gönderen:</strong> <?php echo htmlspecialchars($transaction['sender_name']); ?></div>
                                    <div><strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($transaction['transfer_date'])); ?></div>
                                    <?php if ($transaction['reference_number']): ?>
                                        <div><strong>Ref No:</strong> <?php echo htmlspecialchars($transaction['reference_number']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-<?php echo $transaction['payment_method'] === 'credit_card' ? 'success' : 'info'; ?>">
                                    <?php echo $transaction['payment_method'] === 'credit_card' ? 'Kredi Kartı' : 'Banka Transferi'; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            switch($transaction['status']) {
                                case 'pending':
                                    $statusClass = 'warning';
                                    $statusText = 'Bekliyor';
                                    break;
                                case 'approved':
                                    $statusClass = 'success';
                                    $statusText = 'Onaylandı';
                                    break;
                                case 'rejected':
                                    $statusClass = 'danger';
                                    $statusText = 'Reddedildi';
                                    break;
                                default:
                                    $statusClass = 'secondary';
                                    $statusText = 'Bilinmiyor';
                            }
                            ?>
                            <span class="badge bg-<?php echo $statusClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?></td>
                        <td>
                            <?php if ($transaction['status'] === 'pending' && $transaction['notification_id']): ?>
                                <div class="btn-group">
                                    <form action="/admin/credits/transactions/update-status" method="POST" class="d-inline">
                                        <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                        <button type="submit" name="status" value="approved" class="btn btn-success btn-sm" title="Onayla">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="submit" name="status" value="rejected" class="btn btn-danger btn-sm" title="Reddet">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#transactionsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
        },
        order: [[7, 'desc']],
        pageLength: 25
    });
});
</script>

<?php require 'views/layouts/footer.php'; ?> 