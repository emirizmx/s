<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Puzzle Oyunu</title>
    <link rel="stylesheet" href="/puzzle/assets/css/style.css">
    <link rel="stylesheet" href="/puzzle/assets/css/game.css">
    <script src="https://www.youtube.com/iframe_api"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="game-container">
        <header class="game-header">
            <div class="game-info">
                <h1>Puzzle Oyunu</h1>
                <div class="timer" id="timer">00:00</div>
            </div>
            <div class="game-controls">
                <button id="pauseButton" class="btn-control">
                    <?php echo $puzzle['is_paused'] ? 'Devam Et' : 'Duraklat'; ?>
                </button>
                <button id="resetButton" class="btn-control">Yeniden Başlat</button>
                <?php if (!empty($puzzle['youtube_url'])): ?>
                <div class="music-controls">
                    <button id="toggleMusic" class="btn-control">
                        <i class="fas fa-play"></i>
                    </button>
                    <div class="volume-control">
                        <input type="range" id="volumeSlider" min="0" max="100" value="50">
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </header>

        <main class="game-area">
            <div class="puzzle-board" id="puzzleBoard">
                <!-- Hedef puzzle alanı -->
                <div class="puzzle-grid" style="--grid-size: <?php echo $this->getGridSize($puzzle['difficulty']); ?>; min-height: 400px;">
                    <?php for ($i = 0; $i < pow($this->getGridSize($puzzle['difficulty']), 2); $i++): ?>
                        <div class="puzzle-slot" data-slot-id="<?php echo $i; ?>"></div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="puzzle-pieces" id="puzzlePieces">
                <?php foreach ($pieces as $piece): ?>
                    <div class="puzzle-piece" 
                         draggable="true"
                         data-piece-id="<?php echo $piece['id']; ?>"
                         style="background-image: url('<?php echo $piece['path']; ?>');">
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Yeniden başlat popup'ı -->
    <div id="resetConfirmPopup" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>Yeniden Başlat</h3>
            <p>Oyunu yeniden başlatmak istediğinize emin misiniz?</p>
            <div class="modal-buttons">
                <button onclick="confirmReset()" class="btn-primary">Evet</button>
                <button onclick="closeResetPopup()" class="btn-secondary">Hayır</button>
            </div>
        </div>
    </div>

    <?php if ($puzzleData['isCompleted']): ?>
        <div class="completed-puzzle-overlay">
            <div class="completed-puzzle-container">
                <div class="puzzle-board completed">
                    <div class="puzzle-grid completed" style="--grid-size: <?php echo $puzzleData['gridSize']; ?>;">
                        <?php 
                        $imagePath = str_replace('/uploads//uploads/', '/uploads/', $puzzle['image_path']);
                        for ($i = 0; $i < pow($puzzleData['gridSize'], 2); $i++): 
                            $row = floor($i / $puzzleData['gridSize']);
                            $col = $i % $puzzleData['gridSize'];
                            $pieceSize = 100 / $puzzleData['gridSize'];
                            $bgPosition = (-$col * 100) . '% ' . (-$row * 100) . '%';
                        ?>
                            <div class="puzzle-piece completed" 
                                 style="background-image: url('<?= BASE_URL . $imagePath ?>');
                                        background-size: <?= $puzzleData['gridSize'] * 100 ?>%;
                                        background-position: <?= $bgPosition ?>;"></div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="completion-message"><?= htmlspecialchars($puzzle['completion_message']) ?></div>
                <div class="completion-time">Tamamlama Süresi: <?= $this->formatTime($puzzleData['completionTime']) ?></div>
                <?php if ($puzzleData['globalBestScore']): ?>
                    <div class="best-score">Puzzle'ın En İyi Süresi: <?= $this->formatTime($puzzleData['globalBestScore']) ?></div>
                <?php endif; ?>
                <div class="button-group">
                    <button class="restart-button" onclick="showResetPopup()">Yeniden Başlat</button>
                    <button class="home-button" onclick="location.href='/puzzle/'">Ana Sayfa</button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Mevcut oyun alanı HTML'i -->
    <?php endif; ?>

    <!-- Gizli YouTube player'ı -->
    <?php if (!empty($puzzle['youtube_url'])): ?>
    <div id="youtubePlayer" style="display: none;" data-video-id="<?php echo htmlspecialchars($puzzle['youtube_url']); ?>"></div>
    <?php endif; ?>

    <script>
        // puzzleData tanımlaması
        window.puzzleData = {
            id: <?php echo $puzzle['id']; ?>,
            difficulty: '<?php echo $puzzle['difficulty']; ?>',
            gridSize: <?php echo $this->getGridSize($puzzle['difficulty']); ?>,
            completionMessage: '<?php echo addslashes($puzzle['completion_message']); ?>',
            isCompleted: <?php echo $puzzleData['isCompleted'] ? 'true' : 'false'; ?>,
            completionTime: <?php echo $puzzleData['completionTime'] ?? 0; ?>
        };
    </script>
    <script src="/puzzle/assets/js/game.js"></script>

    <!-- Modal CSS'i -->
    <style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 2000;
    }

    .modal-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        max-width: 400px;
        width: 90%;
    }

    .modal-buttons {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 20px;
    }

    .btn-primary {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-secondary {
        background: #666;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
    }
    </style>
</body>
</html> 