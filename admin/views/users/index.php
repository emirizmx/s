<?php require 'views/layouts/header.php'; ?>

<div class="content-header">
    <div class="header-left">
        <h1>Kullanıcılar</h1>
    </div>
    <div class="header-right">
        <form action="/admin/users" method="GET" class="search-form">
            <input type="text" 
                   name="search" 
                   value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Kullanıcı ara..."
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
        <table class="table" id="usersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kullanıcı Adı</th>
                    <th>E-posta</th>
                    <th>Krediler</th>
                    <th>Durum</th>
                    <th>Kayıt Tarihi</th>
                    <th>Son Giriş</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo number_format($user['credits']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($user['kayit_tarihi'])); ?></td>
                        <td><?php echo $user['son_giris'] ? date('d.m.Y H:i', strtotime($user['son_giris'])) : '-'; ?></td>
                        <td>
                            <a href="/admin/users/edit?id=<?php echo $user['id']; ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="page-link <?php echo $page === $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        // Bootstrap 5 entegrasyonu
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        // Responsive tasarım
        responsive: true
    });
});
</script>

<?php require 'views/layouts/footer.php'; ?> 