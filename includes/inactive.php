<?php
require_once 'includes/header.php';
?>

<div class="container" style="margin-top: var(--header-height); padding: 2rem;">
    <div class="inactive-game-message" style="
        max-width: 600px;
        margin: 3rem auto;
        padding: 2rem;
        text-align: center;
        background-color: #f8f9fa;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    ">
        <div class="icon" style="font-size: 4rem; color: #dc3545; margin-bottom: 1rem;">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        
        <h1 style="color: #333; margin-bottom: 1rem;">Oyun Şu Anda Pasif</h1>
        
        <p style="color: #666; font-size: 1.1rem; margin-bottom: 1.5rem;">
            Bu oyun şu an bakım veya güncelleme nedeniyle geçici olarak kullanıma kapalıdır.
            Lütfen daha sonra tekrar deneyiniz.
        </p>
        
        <a href="/" class="btn btn-primary" style="
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        ">Ana Sayfaya Dön</a>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>