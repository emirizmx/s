<?php require_once __DIR__ . '/../../../../includes/header.php'; ?>

<!-- Plyr CSS ve JS -->
<link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
<script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>

<script>
const B2_CDN_URL = '<?php echo B2_CDN_URL; ?>';
</script>

<div class="container" style="margin-top: var(--header-height); padding: 2rem;">
    <!-- Hikaye Yönetim Paneli (Sadece hikaye sahibi görür) -->
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $story['user_id']): ?>
    <div class="story-controls" style="
        background: var(--surface-color);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 2rem;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
        box-shadow: var(--card-shadow);
    ">
        <div class="controls-header">
            <h3 style="margin: 0; color: var(--heading-color); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-cog"></i> Hikaye Yönetimi
            </h3>
            <button id="toggleControls" class="toggle-btn">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>

        <div id="controlsContent" class="controls-content">
            <!-- Paylaşım Ayarları -->
            <div class="control-group" style="margin: 1rem 0;">
                <label class="switch-label">
                    <input type="checkbox" id="publicToggle" <?php echo $story['is_public'] ? 'checked' : ''; ?>>
                    <span class="switch-text">Herkese Açık</span>
                </label>
            </div>

            <!-- Sesli anlatım bilgi kısmını veritabanından çekilecek şekilde güncelle -->
<?php if (!isset($story['voice_file']) || empty($story['voice_file'])): ?>
<div class="info-box" style="margin-bottom: 1rem; padding: 0.5rem 1rem; background: #f5f5f5; border-radius: 5px;">
    <?php
    // Sesli hikaye için gereken kredi bilgisini aynı sorguyla alıyoruz
    $stmtVoice = $this->db->prepare("
        SELECT voice_credits FROM dh_games 
        WHERE route = 'story/list'
    ");
    $stmtVoice->execute();
    $gameVoice = $stmtVoice->fetch(PDO::FETCH_ASSOC);
    $voiceCredits = $gameVoice && isset($gameVoice['voice_credits']) ? (int)$gameVoice['voice_credits'] : 50;
    ?>
    <span>Sesli anlatım: <?php echo $voiceCredits; ?> kredi</span>
</div>
<?php endif; ?>
                    <?php if ($story['audio_path']): ?>
                    <div class="warning-info">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Yeni ses oluşturulduğunda mevcut ses silinecektir
                    </div>
                    <?php endif; ?>
                </div>

                <div class="voice-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <?php foreach ($voices as $voice): ?>
                    <div class="voice-option">
                        <label>
                            <input type="radio" name="voice" value="<?php echo htmlspecialchars($voice['voice_id']); ?>"
                                   <?php echo ($voice['voice_id'] == '21m00Tcm4TlvDq8ikWAM') ? 'checked' : ''; ?>>
                            <span class="voice-demo">
                                <i class="fas <?php echo htmlspecialchars($voice['icon']); ?>"></i>
                                <span class="voice-name"><?php echo htmlspecialchars($voice['name']); ?></span>
                                <button class="demo-btn" data-demo="<?php echo htmlspecialchars($voice['demo_url']); ?>">
                                    <i class="fas fa-play"></i>
                                </button>
                            </span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Buton gösterilmeden önce, sesli hikaye için gereken kredi miktarını veritabanından al -->
                <?php
                // Buton gösterilmeden önce, sesli hikaye için gereken kredi miktarını veritabanından al
                $stmtVoice = $this->db->prepare("
                    SELECT voice_credits FROM dh_games 
                    WHERE route = 'story/list'
                ");
                $stmtVoice->execute();
                $gameVoice = $stmtVoice->fetch(PDO::FETCH_ASSOC);
                $voiceCredits = $gameVoice && isset($gameVoice['voice_credits']) ? (int)$gameVoice['voice_credits'] : 50;
                ?>

                <!-- Sesli anlatım butonunda kredi miktarını göster -->
                <button id="generateAudioBtn" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                    <i class="fas fa-microphone"></i>
                    Seslendir (<?php echo $voiceCredits; ?> Kredi)
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="story-view" style="max-width: 800px; margin: 0 auto;">
        <h1 style="
            text-align: center;
            color: var(--heading-color);
            margin-bottom: 2rem;
            font-size: 2rem;
        "><?php echo htmlspecialchars($story['title']); ?></h1>

        <div class="story-meta" style="
            text-align: center;
            margin-bottom: 2rem;
            color: var(--text-light);
            font-size: 0.9rem;
        ">
            <div>Yazar: <?php echo htmlspecialchars($story['author']); ?></div>
            <div>Oluşturulma: <?php echo date('d.m.Y H:i', strtotime($story['created_at'])); ?></div>
        </div>

        <div class="story-content" style="
            background: var(--surface-color);
            border-radius: 12px;
            padding: 2rem;
            line-height: 1.8;
            color: var(--text-color);
            box-shadow: var(--card-shadow);
            font-size: 1.1rem;
            white-space: pre-line;
        ">
            <?php echo nl2br(htmlspecialchars($story['content'])); ?>
        </div>

        <!-- Audio player'ı sayfanın altına taşıyalım, story-content'den sonra -->

        <?php if ($story['audio_path']): ?>
        <!-- Sabit Player Container -->
        <div class="fixed-player-container">
            <div class="player-inner">
                <!-- Başlık ve Kontroller -->
                <div class="player-info">
                    <div class="player-title">
                        <i class="fas fa-music"></i>
                        <span><?php echo htmlspecialchars($story['title']); ?></span>
                    </div>
                    
                    <!-- İndirme Butonu -->
                    <a href="/story/download/<?php echo $story['id']; ?>/<?php echo $story['share_token']; ?>" 
                       class="download-btn"
                    >
                        <i class="fas fa-download"></i>
                        <span class="hide-on-mobile">İndir</span>
                    </a>
                </div>

                <!-- Plyr Audio Player -->
                <audio id="player" class="audio-player">
                    <source src="<?php echo $story['audio_path']; ?>" type="audio/mp3">
                </audio>
            </div>
        </div>

        <!-- Player için boşluk -->
        <div style="height: 120px;"></div>

        <style>
        /* CSS Değişkenleri */
        :root {
            /* Ana Renkler */
            --primary-color: #ff4767;
            --primary-light: #ff6b84;
            --primary-dark: #d13d59;
            --primary-darker: #b32e47;
            
            /* RGB versiyonları */
            --primary-rgb: 255, 71, 103;
            --primary-light-rgb: 255, 107, 132;
            --primary-dark-rgb: 209, 61, 89;
            --white-rgb: 255, 255, 255;
        }

        /* Modern Player Stilleri */
        .fixed-player-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(180deg, 
                var(--primary-darker) 0%,
                var(--primary-dark) 100%
            );
            box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.15);
            z-index: 1000;
        }

        .player-inner {
            max-width: 800px;
            margin: 0 auto;
            padding: 1rem;
            background: linear-gradient(90deg, 
                rgba(var(--white-rgb), 0.05) 0%,
                rgba(var(--white-rgb), 0.1) 100%
            );
        }

        .player-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.8rem;
        }

        .player-title {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 0.95rem;
            color: rgba(var(--white-rgb), 0.95);
            font-weight: 500;
        }

        .player-title i {
            color: rgba(var(--white-rgb), 0.9);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .download-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            color: white;
            background: rgba(var(--white-rgb), 0.15);
            border: 1px solid rgba(var(--white-rgb), 0.2);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .download-btn:hover {
            background: rgba(var(--white-rgb), 0.25);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* Plyr Özelleştirmeleri */
        .fixed-player-container .plyr {
            --plyr-color-main: white;
            --plyr-range-fill-background: white;
            --plyr-audio-controls-background: transparent;
            --plyr-audio-control-color: rgba(255, 255, 255, 0.85);
            --plyr-audio-control-color-hover: white;
            --plyr-range-thumb-height: 14px;
            --plyr-range-thumb-width: 14px;
            --plyr-range-track-height: 4px;
        }

        .fixed-player-container .plyr__controls {
            padding: 0.5rem;
            background: rgba(var(--white-rgb), 0.1);
            border-radius: 30px;
            border: 1px solid rgba(var(--white-rgb), 0.15);
        }

        .fixed-player-container .plyr__control {
            padding: 8px;
            transition: all 0.2s ease;
        }

        .fixed-player-container .plyr__control:hover {
            background: rgba(var(--white-rgb), 0.2);
            transform: scale(1.05);
        }

        .fixed-player-container .plyr__progress__container {
            margin: 0 12px;
        }

        .fixed-player-container .plyr__time {
            color: rgba(var(--white-rgb), 0.9);
            font-size: 0.9rem;
            padding: 0 8px;
        }

        .fixed-player-container .plyr__progress__buffer {
            background: rgba(var(--white-rgb), 0.3);
        }

        /* Progress Bar Özelleştirmesi */
        .fixed-player-container .plyr--full-ui input[type='range'] {
            color: white;
        }

        /* Volume Bar Özelleştirmesi */
        .fixed-player-container .plyr__volume input[type='range'] {
            color: white;
        }

        /* Mobil Uyumluluk */
        @media (max-width: 768px) {
            .hide-on-mobile {
                display: none;
            }
            
            .player-inner {
                padding: 0.8rem;
            }
            
            .player-title {
                font-size: 0.85rem;
            }
            
            .download-btn {
                padding: 0.4rem 0.8rem;
            }
            
            .fixed-player-container .plyr__controls {
                padding: 0.4rem;
            }
            
            .fixed-player-container .plyr__control {
                padding: 6px;
            }
        }

        /* Karanlık Tema için Extra Ayarlar */
        @media (prefers-color-scheme: dark) {
            .fixed-player-container {
                background: linear-gradient(180deg, #f44972 0%, #885de1 100%);
            }
            
            .download-btn {
                background: rgba(var(--white-rgb), 0.1);
            }
            
            .download-btn:hover {
                background: rgba(var(--white-rgb), 0.2);
            }
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.querySelector('.audio-player')) {
                const player = new Plyr('#player', {
                    controls: [
                        'play',
                        'progress',
                        'current-time',
                        'duration',
                        'mute',
                        'volume',
                        'speed'
                    ],
                    speed: {
                        selected: 1,
                        options: [0.75, 1, 1.25, 1.5]
                    },
                    keyboard: {
                        focused: true,
                        global: true
                    },
                    tooltips: {
                        controls: true,
                        seek: true
                    },
                    i18n: {
                        speed: 'Hız',
                        normal: 'Normal',
                        play: 'Oynat',
                        pause: 'Duraklat',
                        mute: 'Sessiz',
                        unmute: 'Sesi Aç',
                        download: 'İndir',
                        currentTime: 'Geçen Süre',
                        duration: 'Toplam Süre'
                    }
                });

                // Scroll olayını dinle
                let lastScroll = 0;
                const playerContainer = document.querySelector('.fixed-player-container');
                
                window.addEventListener('scroll', () => {
                    const currentScroll = window.pageYOffset;
                    
                    // Aşağı scroll
                    if (currentScroll > lastScroll && currentScroll > 200) {
                        playerContainer.classList.add('minimized');
                    } 
                    // Yukarı scroll
                    else {
                        playerContainer.classList.remove('minimized');
                    }
                    
                    lastScroll = currentScroll;
                });
            }
        });

        function forceDownload(url, fileName) {
            fetch(url)
                .then(resp => resp.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = fileName;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                })
                .catch(error => {
                    console.error('İndirme hatası:', error);
                    // Alternatif yöntem
                    window.location.href = url;
                });
        }
        </script>
        <?php endif; ?>
    </div>
</div>

<style>
/* Player özel stilleri */
.audio-player-container .plyr {
    background: var(--surface-color);
}

.audio-player-container .plyr__controls {
    padding: 0.5rem;
    border-radius: 8px;
    background: var(--surface-hover);
}

.audio-player-container .plyr__control {
    transition: all 0.2s ease;
}

.audio-player-container .plyr__control:hover {
    background: var(--primary-light);
    color: var(--primary-color);
}

/* İndirme butonu hover efekti */
.audio-player-container .btn-outline-primary:hover {
    background: var(--primary-light);
    color: var(--primary-color);
}

/* Yönetim Paneli Stilleri */
.switch-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.switch-label input {
    position: relative;
    width: 50px;
    height: 24px;
    appearance: none;
    background: #ddd;
    border-radius: 12px;
    margin-right: 10px;
    transition: 0.3s;
}

.switch-label input:checked {
    background: var(--primary-color);
}

.switch-label input:before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: 0.3s;
}

.switch-label input:checked:before {
    left: 28px;
}

.switch-text {
    font-weight: 500;
    color: var(--text-color);
}

/* Buton stilleri */
.btn {
    padding: 0.8rem 1.5rem;
    border-radius: 25px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

/* Select stil */
.form-select {
    width: 100%;
    padding: 0.8rem;
    border-radius: 8px;
    border: 1px solid #ddd;
    background: var(--surface-color);
    color: var(--text-color);
    font-size: 0.9rem;
}

@media (prefers-color-scheme: dark) {
    .switch-label input {
        background: #666;
    }
    
    .form-select {
        background: #2d2d2d;
        border-color: #444;
        color: #fff;
    }
}

/* Sesli Anlatım Bölümü Stilleri */
.voice-control-section {
    background: var(--surface-color);
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1.5rem;
}

.credit-info {
    background: var(--primary-light);
    color: var(--primary-darker);
    padding: 0.8rem;
    border-radius: 8px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.warning-info {
    background: #fff3cd;
    color: #856404;
    padding: 0.8rem;
    border-radius: 8px;
    margin-top: 0.8rem;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.voice-options-container {
    margin-top: 1.5rem;
}

.voice-options-grid {
    display: grid;
    gap: 1rem;
    margin-top: 1rem;
}

.voice-option {
    background: var(--surface-hover);
    border-radius: 8px;
    overflow: hidden;
}

.voice-option label {
    cursor: pointer;
    display: block;
}

.voice-option input[type="radio"] {
    display: none;
}

.voice-demo {
    display: flex;
    align-items: center;
    padding: 1rem;
    gap: 1rem;
    transition: all 0.2s;
}

.voice-option input[type="radio"]:checked + .voice-demo {
    background: var(--primary-color);
    color: white;
}

.voice-name {
    flex: 1;
    font-weight: 500;
}

.demo-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    color: inherit;
    cursor: pointer;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.demo-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
}

@media (prefers-color-scheme: dark) {
    .credit-info {
        background: var(--primary-darker);
        color: var(--primary-light);
    }
    
    .warning-info {
        background: #2c2a1c;
        color: #fff3cd;
    }
}

/* Yönetim Paneli Header Stilleri */
.controls-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.toggle-btn {
    background: none;
    border: none;
    color: var(--text-color);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: all 0.2s;
}

.toggle-btn:hover {
    background: var(--surface-hover);
}

.toggle-btn i {
    transition: transform 0.2s;
}

.toggle-btn.collapsed i {
    transform: rotate(180deg);
}

.controls-content {
    max-height: 1000px;
    overflow: hidden;
    transition: max-height 0.3s ease-in-out;
}

.controls-content.collapsed {
    max-height: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Public/Private Toggle
    const publicToggle = document.getElementById('publicToggle');
    if (publicToggle) {
        publicToggle.addEventListener('change', function() {
            fetch('/story/share/<?php echo $story['id']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    is_public: this.checked
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Hata: ' + data.error);
                    this.checked = !this.checked;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bir hata oluştu');
                this.checked = !this.checked;
            });
        });
    }

    // Sesli Anlatım Oluşturma
    const generateAudioBtn = document.getElementById('generateAudioBtn');
    
    if (generateAudioBtn) {
        generateAudioBtn.addEventListener('click', function() {
            // Seçili ses seçeneğini al
            const selectedVoice = document.querySelector('input[name="voice"]:checked');
            if (!selectedVoice) {
                alert('Lütfen bir ses seçin');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Oluşturuluyor...';
            
            fetch('/story/index.php?action=generateAudio&id=<?php echo $story['id']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    voiceId: selectedVoice.value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Hata: ' + data.error);
                } else {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bir hata oluştu');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-microphone"></i> Sesli Anlatım Oluştur';
            });
        });
    }

    // Gizle/Göster fonksiyonalitesi - Güncellendi
    const toggleBtn = document.getElementById('toggleControls');
    const controlsContent = document.getElementById('controlsContent');
    
    if (toggleBtn && controlsContent) {
        toggleBtn.addEventListener('click', function() {
            this.classList.toggle('collapsed');
            controlsContent.classList.toggle('collapsed');
            
            // İkon yönünü değiştir
            const icon = this.querySelector('i');
            if (this.classList.contains('collapsed')) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
            
            // Sesli anlatım seçeneklerini ve butonu gizle/göster
            const voiceOptions = document.querySelector('.voice-options-grid');
            const voiceBtn = document.getElementById('generateAudioBtn');
            
            if (controlsContent.classList.contains('collapsed')) {
                if (voiceOptions) voiceOptions.style.display = 'none';
                if (voiceBtn) voiceBtn.style.display = 'none';
            } else {
                if (voiceOptions) voiceOptions.style.display = '';
                if (voiceBtn) voiceBtn.style.display = '';
            }
            
            // Tercihi localStorage'a kaydet
            localStorage.setItem('controlsCollapsed', controlsContent.classList.contains('collapsed'));
        });
        
        // Sayfa yüklendiğinde önceki tercihi kontrol et
        if (localStorage.getItem('controlsCollapsed') === 'true') {
            toggleBtn.classList.add('collapsed');
            controlsContent.classList.add('collapsed');
            
            // İkonu güncelle
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
            
            // Sesli anlatım elemanlarını da gizle
            const voiceOptions = document.querySelector('.voice-options-grid');
            const voiceBtn = document.getElementById('generateAudioBtn');
            
            if (voiceOptions) voiceOptions.style.display = 'none';
            if (voiceBtn) voiceBtn.style.display = 'none';
        }
    }

    // Demo ses oynatma fonksiyonalitesi
    const demoButtons = document.querySelectorAll('.demo-btn');
    const demoAudio = new Audio();
    
    demoButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const demoUrl = encodeURI(this.getAttribute('data-demo'));
            
            // Eğer aynı ses çalıyorsa durdur
            if (demoAudio.src.endsWith(demoUrl) && !demoAudio.paused) {
                demoAudio.pause();
                demoAudio.currentTime = 0;
                this.innerHTML = '<i class="fas fa-play"></i>';
                return;
            }
            
            // Tüm butonları play ikonuna çevir 
            demoButtons.forEach(btn => {
                btn.innerHTML = '<i class="fas fa-play"></i>';
            });
            
            // Yeni sesi oynat
            demoAudio.src = B2_CDN_URL + demoUrl;
            
            // Hata yakalama ekleyelim
            demoAudio.onerror = () => {
                console.error('Ses dosyası yüklenemedi:', demoAudio.src);
                this.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            };
            
            const playPromise = demoAudio.play();
            if (playPromise) {
                playPromise.then(() => {
                    this.innerHTML = '<i class="fas fa-pause"></i>';
                }).catch(error => {
                    console.error('Oynatma hatası:', error);
                    this.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                });
            }
            
            // Ses bitince ikonu değiştir
            demoAudio.onended = () => {
                this.innerHTML = '<i class="fas fa-play"></i>';
            };
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../../../includes/footer.php'; ?> 