<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Puzzle Oyunu</title>
    <link rel="stylesheet" href="/puzzle/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Puzzle Oyunu</h1>
            <nav>
                <span>Hoş geldin, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <span class="user-credits">Krediniz: <?php echo $userCredits; ?></span>
                <a href="/puzzle/logout">Çıkış Yap</a>
            </nav>
        </header>

        <main>
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

            <?php 
            // Game ve userCredits değişkenlerinin gelip gelmediğini kontrol et
            error_log('View variables: game=' . (isset($game) ? 'yes' : 'no') . ', userCredits=' . (isset($userCredits) ? $userCredits : 'no'));
            ?>

            <?php 
            // Debug için değişkenleri kontrol et
            error_log('View variables check:');
            error_log('game isset: ' . (isset($game) ? 'yes' : 'no'));
            error_log('game credits: ' . ($game['credits'] ?? 'not set'));
            error_log('userCredits: ' . ($userCredits ?? 'not set'));
            ?>

            <?php
            // Debug bilgisi
            error_log('=== View Debug Info ===');
            error_log('$game variable: ' . (isset($game) ? print_r($game, true) : 'not set'));
            error_log('$userCredits variable: ' . (isset($userCredits) ? $userCredits : 'not set'));
            ?>

            <section class="upload-section">
                <h2>Yeni Puzzle Oluştur</h2>
                <?php 
                // dh_games'den gelen kredi değerini kullan
                $requiredCredits = $game['credits'];
                if ($userCredits < $requiredCredits): 
                ?>
                    <div class="credit-warning">
                        Puzzle oluşturmak için yeterli krediniz yok. Gerekli kredi: <?php echo number_format($requiredCredits); ?>
                    </div>
                <?php else: ?>
                    <div class="credit-info">
                        Bu işlem <?php echo isset($gameCredits) ? $gameCredits : 1; ?> kredinizi kullanacaktır. Mevcut krediniz: <?php echo $userCredits; ?>
                    </div>
                    <form action="/puzzle/upload" method="post" enctype="multipart/form-data" id="puzzleForm">
                        <div class="form-group">
                            <label for="image">Fotoğraf Seçin (max 2MB):</label>
                            <input type="file" id="image" name="image" accept="image/jpeg,image/png" required>
                        </div>

                        <!-- Resim önizleme ve kırpma alanı -->
                        <div class="image-preview-container" style="display: none;">
                            <div class="preview-wrapper">
                                <img id="preview" src="">
                            </div>
                            <p class="crop-info">Puzzle için kullanılacak kare alanı seçin</p>
                            <div class="crop-controls">
                                <button type="button" class="btn-secondary" id="rotateLeft">Sola Döndür</button>
                                <button type="button" class="btn-secondary" id="rotateRight">Sağa Döndür</button>
                                <button type="button" class="btn-secondary" id="resetCrop">Sıfırla</button>
                            </div>
                        </div>

                        <!-- Kırpma verilerini saklayacak gizli input -->
                        <input type="hidden" name="cropData" id="cropData">

                        <div class="form-group">
                            <label for="difficulty">Zorluk Seviyesi:</label>
                            <select id="difficulty" name="difficulty" required>
                                <option value="easy">Kolay (4x4)</option>
                                <option value="medium">Orta (6x6)</option>
                                <option value="hard">Zor (8x8)</option>
                                <option value="insane">Çılgın (10x10)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="completion_message">Oyun Sonu Mesajı:</label>
                            <input type="text" 
                                   name="completion_message" 
                                   id="completion_message" 
                                   class="form-control"
                                   value="Tebrikler! Puzzle'i tamamladiniz!"
                                   maxlength="255"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="visibility">Oyun Durumu:</label>
                            <select name="visibility" id="visibility" class="form-control" required>
                                <option value="public">Herkese Açık</option>
                                <option value="private">Özel</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="youtube_url">YouTube Müzik URL (İsteğe Bağlı):</label>
                            <div class="youtube-url-container">
                                <input type="text" 
                                       name="youtube_url" 
                                       id="youtube_url" 
                                       class="form-control"
                                       placeholder="Örn: https://www.youtube.com/watch?v=xxxxx"
                                       pattern="https?://(www\.)?youtube\.com/watch\?v=[\w-]{11}|https?://youtu\.be/[\w-]{11}">
                                <button type="button" id="checkYoutubeUrl" class="btn-secondary">
                                    <i class="fas fa-check"></i> URL Kontrol Et
                                </button>
                            </div>
                            <div id="youtubePreview" class="youtube-preview" style="display: none;">
                                <div class="video-info">
                                    <img src="" alt="Video Thumbnail" class="video-thumbnail">
                                    <div class="video-details">
                                        <h4 class="video-title"></h4>
                                        <p class="video-channel"></p>
                                    </div>
                                </div>
                                <button type="button" class="btn-close">×</button>
                            </div>
                            <small class="form-text text-muted">Oyuncuların puzzle çözerken dinleyebileceği bir YouTube müzik videosu ekleyin.</small>
                        </div>

                        <button type="submit" class="btn-primary">Puzzle Oluştur</button>
                    </form>
                <?php endif; ?>
            </section>

            <section class="recent-puzzles">
                <h2>Son Oyunlarınız</h2>
                <div class="puzzle-grid">
                    <?php if (!empty($recentPuzzles)): ?>
                        <?php foreach ($recentPuzzles as $puzzle): ?>
                            <div class="puzzle-card">
                                <img src="<?php echo BASE_URL . htmlspecialchars($puzzle['image_path']); ?>" alt="Puzzle">
                                <div class="puzzle-info">
                                    <p>Zorluk: <?php echo htmlspecialchars(ucfirst($puzzle['difficulty'])); ?></p>
                                    <p>
                                        Durum: 
                                        <select class="visibility-select" data-puzzle-id="<?php echo $puzzle['id']; ?>">
                                            <option value="public" <?php echo (!isset($puzzle['visibility']) || $puzzle['visibility'] === 'public') ? 'selected' : ''; ?>>
                                                Herkese Açık
                                            </option>
                                            <option value="private" <?php echo (isset($puzzle['visibility']) && $puzzle['visibility'] === 'private') ? 'selected' : ''; ?>>
                                                Özel
                                            </option>
                                        </select>
                                    </p>
                                    <p>
                                        Oyun Sonu Mesajı:
                                        <input type="text" 
                                               class="completion-message-input" 
                                               data-puzzle-id="<?php echo $puzzle['id']; ?>"
                                               value="<?php echo htmlspecialchars($puzzle['completion_message'] ?? 'Tebrikler! Puzzle\'ı tamamladınız!'); ?>"
                                               maxlength="255">
                                    </p>
                                    <p>Süre: <?php echo $puzzle['completion_time'] ? $this->formatTime($puzzle['completion_time']) : 'Tamamlanmadı'; ?></p>
                                    <a href="/puzzle/play/<?php echo htmlspecialchars($puzzle['access_token'] ?? bin2hex(random_bytes(32))); ?>" class="btn-secondary">Oyna</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-puzzles">Henüz hiç puzzle oluşturmadınız.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- JavaScript dosyasını ekleyelim -->
    <script src="/puzzle/assets/js/home.js"></script>
</body>
</html> 