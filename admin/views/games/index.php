<?php require 'views/layouts/header.php'; ?>

<div class="content-header">
    <div class="header-left">
        <h1>Oyunlar</h1>
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
                    <th>Sıra</th>
                    <th>Resim</th>
                    <th>Oyun Adı</th>
                    <th>Kredi</th>
                    <th>Durum</th>
                    <th>Son Güncelleme</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($games as $game): ?>
                    <tr>
                        <td><?php echo $game['display_order']; ?></td>
                        <td>
                            <img src="<?php echo $game['image_path']; ?>" 
                                 alt="<?php echo htmlspecialchars($game['name']); ?>"
                                 style="width: 50px; height: 50px; object-fit: cover;">
                        </td>
                        <td><?php echo htmlspecialchars($game['name']); ?></td>
                        <td><?php echo number_format($game['credits']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $game['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $game['is_active'] ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($game['updated_at'])); ?></td>
                        <td>
                            <a href="/admin/games/edit?id=<?php echo $game['id']; ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'views/layouts/footer.php'; ?> 