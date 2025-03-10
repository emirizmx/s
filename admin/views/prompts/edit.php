<?php require 'views/layouts/header.php'; ?>

<div class="content-header">
    <div class="header-left">
        <h1><?php echo $pageTitle; ?></h1>
    </div>
    <div class="header-right">
        <a href="/admin/prompts" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Geri Dön
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="/admin/prompts/edit?id=<?php echo $prompt['id']; ?>" method="POST">
            <input type="hidden" name="id" value="<?php echo $prompt['id']; ?>">
            
            <div class="form-group">
                <label for="name">Prompt Adı</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="<?php echo htmlspecialchars($prompt['name']); ?>" 
                       class="form-control" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="prompt_key">Anahtar (Düzenlenemez)</label>
                <input type="text" 
                       id="prompt_key" 
                       value="<?php echo htmlspecialchars($prompt['prompt_key']); ?>" 
                       class="form-control" 
                       disabled>
                <small class="form-text">Bu anahtar, uygulamada promptu çağırmak için kullanılır.</small>
            </div>
            
            <div class="form-group">
                <label for="description">Açıklama</label>
                <textarea id="description" 
                          name="description" 
                          class="form-control" 
                          rows="2"><?php echo htmlspecialchars($prompt['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="content">Prompt İçeriği</label>
                <textarea id="content" 
                          name="content" 
                          class="form-control" 
                          rows="15"
                          required><?php echo htmlspecialchars($prompt['content']); ?></textarea>
                <small class="form-text">{{user_prompt}} etiketi kullanıcı girdisi ile değiştirilecektir.</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<!-- CodeMirror entegrasyonu için -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.3/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.3/theme/monokai.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.3/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.3/mode/markdown/markdown.min.js"></script>

<style>
.CodeMirror {
    height: auto;
    min-height: 400px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prompt içeriği için CodeMirror editörü
    var contentEditor = CodeMirror.fromTextArea(document.getElementById('content'), {
        mode: 'markdown',
        theme: 'default',
        lineNumbers: true,
        lineWrapping: true
    });
});
</script>

<?php require 'views/layouts/footer.php'; ?> 