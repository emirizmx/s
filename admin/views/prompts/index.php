<?php require 'views/layouts/header.php'; ?>

<div class="content-header">
    <div class="header-left">
        <h1>AI Promptları</h1>
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
                    <th>Prompt Adı</th>
                    <th>Anahtar</th>
                    <th>Açıklama</th>
                    <th>Son Güncelleme</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prompts as $prompt): ?>
                    <tr>
                        <td><?php echo $prompt['id']; ?></td>
                        <td><?php echo htmlspecialchars($prompt['name']); ?></td>
                        <td><code><?php echo htmlspecialchars($prompt['prompt_key']); ?></code></td>
                        <td><?php echo htmlspecialchars($prompt['description']); ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($prompt['updated_at'])); ?></td>
                        <td>
                            <a href="/admin/prompts/edit?id=<?php echo $prompt['id']; ?>" 
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