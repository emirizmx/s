<?php require_once __DIR__ . '/../../../../includes/header.php'; ?>

<div class="container" style="margin-top: var(--header-height); padding: 2rem;">
    <div class="stories-list" style="max-width: 1200px; margin: 0 auto;">
        <div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        ">
            <h1 style="color: var(--heading-color);">Hikayelerim</h1>
            <a href="/story" style="
                padding: 0.8rem 1.5rem;
                background: var(--primary-color);
                color: white;
                text-decoration: none;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            ">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Yeni Hikaye Oluştur
            </a>
        </div>

        <?php if (!empty($stories)): ?>
            <div class="stories-grid" style="
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 2rem;
            ">
                <?php foreach ($stories as $story): ?>
                    <div class="story-card" style="
                        background: var(--surface-color);
                        border-radius: 15px;
                        box-shadow: var(--card-shadow);
                        overflow: hidden;
                    ">
                        <div class="story-header" style="
                            background: var(--primary-light);
                            padding: 1.5rem;
                            position: relative;
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <h3 style="
                                    color: var(--heading-color);
                                    margin: 0;
                                    font-size: 1.2rem;
                                    line-height: 1.4;
                                ">
                                    <?php echo htmlspecialchars($story['title']); ?>
                                </h3>
                                
                                <!-- Paylaşım Kontrolü -->
                                <div class="share-toggle" style="position: relative;">
                                    <button onclick="toggleShare(<?php echo $story['id']; ?>, <?php echo $story['is_public'] ? 'false' : 'true'; ?>)" 
                                            class="btn btn-sm <?php echo $story['is_public'] ? 'btn-success' : 'btn-outline-secondary'; ?>"
                                            style="
                                                min-width: 36px;
                                                height: 36px;
                                                padding: 0;
                                                border-radius: 50%;
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                                transition: all 0.2s ease;
                                            "
                                            title="<?php echo $story['is_public'] ? 'Paylaşımı Kapat' : 'Paylaşıma Aç'; ?>"
                                    >
                                        <i class="fas <?php echo $story['is_public'] ? 'fa-lock-open' : 'fa-lock'; ?>"></i>
                                    </button>
                                    
                                    <?php if ($story['is_public']): ?>
                                    <button onclick="copyShareLink('<?php echo 'https://dijitalhediye.com/story/share/' . $story['share_token']; ?>')"
                                            class="btn btn-sm btn-light copy-link-btn"
                                            style="
                                                position: absolute;
                                                top: 0;
                                                left: -36px;
                                                height: 36px;
                                                width: 36px;
                                                padding: 0;
                                                border-radius: 50%;
                                                font-size: 0.85rem;
                                                background: var(--surface-color);
                                                border: 1px solid var(--border-color);
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                            "
                                            title="Bağlantıyı Kopyala"
                                    >
                                        <i class="fas fa-link"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="
                                margin-top: 0.5rem;
                                font-size: 0.9rem;
                                color: var(--text-light);
                            ">
                                <?php echo date('d.m.Y H:i', strtotime($story['created_at'])); ?>
                            </div>
                        </div>

                        <div class="story-info" style="padding: 1.5rem;">
                            <div style="
                                display: grid;
                                gap: 0.5rem;
                                margin-bottom: 1rem;
                                font-size: 0.9rem;
                                color: var(--text-color);
                            ">
                                <div>
                                    <strong>Tür:</strong> 
                                    <?php 
                                    $types = [
                                        'fairytale' => 'Masal',
                                        'love' => 'Aşk Hikayesi',
                                        'adventure' => 'Macera',
                                        'memory' => 'Anı/Hatıra',
                                        'fantasy' => 'Fantastik',
                                        'comedy' => 'Komedi'
                                    ];
                                    echo $types[$story['type']] ?? $story['type']; 
                                    ?>
                                </div>
                                <div>
                                    <strong>Anlatım:</strong>
                                    <?php 
                                    $styles = [
                                        'classic' => 'Klasik Anlatım',
                                        'first_person' => 'Birinci Tekil Şahıs',
                                        'third_person' => 'Üçüncü Tekil Şahıs',
                                        'letter' => 'Mektup Formatı',
                                        'diary' => 'Günlük Formatı'
                                    ];
                                    echo $styles[$story['narrative_style']] ?? $story['narrative_style'];
                                    ?>
                                </div>
                                <?php if ($story['audio_path']): ?>
                                    <div style="color: var(--success-color);">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 0.3rem;">
                                            <path d="M11.536 14.01A8.473 8.473 0 0 0 14.026 8a8.473 8.473 0 0 0-2.49-6.01l-.708.707A7.476 7.476 0 0 1 13.025 8c0 2.071-.84 3.946-2.197 5.303l.708.707z"/>
                                            <path d="M10.121 12.596A6.48 6.48 0 0 0 12.025 8a6.48 6.48 0 0 0-1.904-4.596l-.707.707A5.483 5.483 0 0 1 11.025 8a5.483 5.483 0 0 1-1.61 3.89l.706.706z"/>
                                            <path d="M8.707 11.182A4.486 4.486 0 0 0 10.025 8a4.486 4.486 0 0 0-1.318-3.182L8 5.525A3.489 3.489 0 0 1 9.025 8 3.49 3.49 0 0 1 8 10.475l.707.707zM6.717 3.55A.5.5 0 0 1 7 4v8a.5.5 0 0 1-.812.39L3.825 10.5H1.5A.5.5 0 0 1 1 10V6a.5.5 0 0 1 .5-.5h2.325l2.363-1.89a.5.5 0 0 1 .529-.06z"/>
                                        </svg>
                                        Sesli Anlatım Mevcut
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="story-actions" style="
                                display: flex;
                                gap: 0.5rem;
                                flex-wrap: wrap;
                            ">
                                <a href="/story/share/<?php echo $story['share_token']; ?>" class="btn btn-primary" style="
                                    flex: 1;
                                    min-width: 140px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    gap: 0.5rem;
                                    padding: 0.7rem 1rem;
                                    border-radius: 8px;
                                    font-size: 0.9rem;
                                ">
                                    <i class="fas fa-eye"></i> Hikayeyi Görüntüle
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="
                text-align: center;
                padding: 3rem;
                background: var(--surface-color);
                border-radius: 15px;
                color: var(--text-light);
            ">
                <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom: 1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <h2 style="color: var(--heading-color); margin-bottom: 1rem;">Henüz Hikayen Yok</h2>
                <p style="margin-bottom: 2rem;">İlk hikayeni oluşturmaya ne dersin?</p>
                <a href="/story" style="
                    display: inline-block;
                    padding: 1rem 2rem;
                    background: var(--primary-color);
                    color: white;
                    text-decoration: none;
                    border-radius: 10px;
                    font-weight: 500;
                ">Hikaye Oluştur</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let isProcessing = false; // İşlem durumunu takip etmek için global değişken

function toggleShare(storyId, makePublic) {
    // Eğer işlem devam ediyorsa yeni işlemi engelle
    if (isProcessing) return;
    
    const btn = event.currentTarget;
    const shareToggle = btn.closest('.share-toggle');
    
    isProcessing = true;
    btn.style.pointerEvents = 'none'; // Tıklamayı engelle
    btn.style.opacity = '0.7'; // Visual feedback
    
    fetch('/story/share/' + storyId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            is_public: makePublic
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }
        
        if (makePublic) {
            // Paylaşıma açıldı
            btn.className = 'btn btn-sm btn-success';
            btn.innerHTML = '<i class="fas fa-lock-open"></i>';
            btn.title = 'Paylaşımı Kapat';
            btn.onclick = (e) => {
                e.preventDefault();
                toggleShare(storyId, false);
            };
            
            // Kopyalama butonunu ekle
            const copyBtn = document.createElement('button');
            copyBtn.className = 'btn btn-sm btn-light copy-link-btn';
            copyBtn.onclick = () => copyShareLink(data.share_url);
            copyBtn.innerHTML = '<i class="fas fa-link"></i>';
            copyBtn.title = 'Bağlantıyı Kopyala';
            copyBtn.style.cssText = `
                position: absolute;
                top: 0;
                left: -36px;
                height: 36px;
                width: 36px;
                padding: 0;
                border-radius: 50%;
                font-size: 0.85rem;
                background: var(--surface-color);
                border: 1px solid var(--border-color);
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                opacity: 0;
                transform: translateX(10px);
                transition: all 0.3s ease;
            `;
            shareToggle.appendChild(copyBtn);
            
            // Animasyon için timeout
            setTimeout(() => {
                copyBtn.style.opacity = '1';
                copyBtn.style.transform = 'translateX(0)';
            }, 10);
            
            // Bağlantıyı otomatik kopyala
            copyShareLink(data.share_url);
        } else {
            // Paylaşım kapatıldı
            btn.className = 'btn btn-sm btn-outline-secondary';
            btn.innerHTML = '<i class="fas fa-lock"></i>';
            btn.title = 'Paylaşıma Aç';
            btn.onclick = (e) => {
                e.preventDefault();
                toggleShare(storyId, true);
            };
            
            // Kopyalama butonunu kaldır
            const copyBtn = shareToggle.querySelector('.copy-link-btn');
            if (copyBtn) {
                copyBtn.style.opacity = '0';
                copyBtn.style.transform = 'translateX(10px)';
                setTimeout(() => copyBtn.remove(), 300);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu: ' + error.message);
    })
    .finally(() => {
        // İşlem bitti, butonun durumunu resetle
        isProcessing = false;
        btn.style.pointerEvents = 'auto';
        btn.style.opacity = '1';
    });
}

function copyShareLink(url) {
    navigator.clipboard.writeText(url).then(() => {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--success-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 20px;
            font-size: 0.9rem;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        `;
        toast.innerHTML = '<i class="fas fa-check"></i> Bağlantı kopyalandı!';
        document.body.appendChild(toast);
        
        // Animasyon için timeout
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(-50%) translateY(0)';
        }, 10);
        
        // Toast'ı kaldır
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(10px)';
            setTimeout(() => toast.remove(), 3000);
        }, 3000);
    });
}
</script>

<?php require_once __DIR__ . '/../../../../includes/footer.php'; ?>