// Global fonksiyonlar
window.showResetPopup = function() {
    document.getElementById('resetConfirmPopup').style.display = 'flex';
};

window.closeResetPopup = function() {
    document.getElementById('resetConfirmPopup').style.display = 'none';
};

window.confirmReset = async function() {
    try {
        // Önce API'yi çağır ve puzzle'ı sıfırla
        const response = await fetch('/puzzle/api/reset-puzzle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                puzzleId: window.puzzleData.id
            })
        });

        const data = await response.json();
        if (data.success) {
            // Sayfayı yeniden yükle
            location.href = `?id=${window.puzzleData.id}`;
        } else {
            console.error('Puzzle sıfırlama hatası:', data.error);
        }
    } catch (error) {
        console.error('Sıfırlama işlemi hatası:', error);
    }
};

// YouTube player entegrasyonu
let youtubePlayer = null;
let isPlaying = false;
let isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

// YouTube API hazır olduğunda çağrılacak fonksiyon
window.onYouTubeIframeAPIReady = function() {
    const playerElement = document.getElementById('youtubePlayer');
    if (!playerElement) return;

    youtubePlayer = new YT.Player('youtubePlayer', {
        height: '0',
        width: '0',
        videoId: playerElement.dataset.videoId,
        playerVars: {
            'autoplay': 0,
            'controls': 0,
            'loop': 1,
            'playlist': playerElement.dataset.videoId,
            'playsinline': 1,
            'mute': isMobile ? 1 : 0 // Mobilde sessiz başlat
        },
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
        }
    });
};

function onPlayerReady(event) {
    const volumeSlider = document.getElementById('volumeSlider');
    const toggleButton = document.getElementById('toggleMusic');
    
    if (volumeSlider) {
        volumeSlider.value = 50;
        volumeSlider.addEventListener('input', function() {
            if (youtubePlayer) {
                youtubePlayer.unMute();
                youtubePlayer.setVolume(this.value);
            }
        });
    }

    if (toggleButton) {
        toggleButton.addEventListener('click', function() {
            if (!isPlaying) {
                if (isMobile) {
                    youtubePlayer.unMute(); // Mobilde sesi aç
                    youtubePlayer.setVolume(volumeSlider ? volumeSlider.value : 50);
                }
                youtubePlayer.playVideo();
                this.innerHTML = '<i class="fas fa-pause"></i>';
                this.classList.add('playing');
            } else {
                youtubePlayer.pauseVideo();
                this.innerHTML = '<i class="fas fa-play"></i>';
                this.classList.remove('playing');
            }
            isPlaying = !isPlaying;
        });
    }
}

function onPlayerStateChange(event) {
    const toggleButton = document.getElementById('toggleMusic');
    
    if (event.data === YT.PlayerState.ENDED) {
        youtubePlayer.playVideo();
    } else if (event.data === YT.PlayerState.PLAYING) {
        if (toggleButton) {
            toggleButton.innerHTML = '<i class="fas fa-pause"></i>';
            toggleButton.classList.add('playing');
        }
        isPlaying = true;
        
        // Mobilde çalmaya başladığında sesi aç
        if (isMobile && youtubePlayer.isMuted()) {
            youtubePlayer.unMute();
            const volumeSlider = document.getElementById('volumeSlider');
            youtubePlayer.setVolume(volumeSlider ? volumeSlider.value : 50);
        }
    } else if (event.data === YT.PlayerState.PAUSED) {
        if (toggleButton) {
            toggleButton.innerHTML = '<i class="fas fa-play"></i>';
            toggleButton.classList.remove('playing');
        }
        isPlaying = false;
    }
}

class PuzzleGame {
    constructor(puzzleData) {
        this.puzzleData = puzzleData;
        this.timer = null;
        this.seconds = 0;
        this.isPaused = puzzleData.is_paused === 1;
        this.draggedPiece = null;
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.lockedPieces = new Set();
        this.lastMoveId = 0;
        this.lastTimeSave = 0;
        this.isDragging = false;
        this.dragTimeout = null;
        this.lastResetTimestamp = null;
        
        // Hareket yönetimi için yeni değişkenler
        this.moveQueue = [];
        this.isProcessingQueue = false;
        this.processingDelay = 100;
        this.lastMove = new Map(); // Son hareket takibi için
        
        this.initializeGame();
        this.startPolling();
    }

    async initializeGame() {
        // Mevcut süreyi sunucudan al
        try {
            const response = await fetch(`/puzzle/api/get-time?puzzleId=${this.puzzleData.id}`);
            if (!response.ok) {
                throw new Error('Sunucu hatası');
            }
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Bilinmeyen hata');
            }
            this.seconds = data.seconds || 0; // Süre yoksa 0'dan başla
            this.lastTimeSave = this.seconds;
            document.getElementById('timer').textContent = this.formatTime(this.seconds);
        } catch (error) {
            console.error('Süre alma hatası:', error);
            // Hata durumunda 0'dan başla
            this.seconds = 0;
            this.lastTimeSave = 0;
            document.getElementById('timer').textContent = this.formatTime(0);
        }

        this.setupDragAndDrop();
        this.setupTouchEvents();
        this.startTimer();
        this.setupControls();
    }

    setupDragAndDrop() {
        const pieces = document.querySelectorAll('.puzzle-piece');
        const slots = document.querySelectorAll('.puzzle-slot');
        const piecesArea = document.querySelector('.puzzle-pieces');

        pieces.forEach(piece => {
            piece.addEventListener('dragstart', this.handleDragStart.bind(this));
            piece.addEventListener('dragend', this.handleDragEnd.bind(this));
        });

        slots.forEach(slot => {
            slot.addEventListener('dragover', this.handleDragOver.bind(this));
            slot.addEventListener('dragenter', this.handleDragEnter.bind(this));
            slot.addEventListener('dragleave', this.handleDragLeave.bind(this));
            slot.addEventListener('drop', this.handleDrop.bind(this));
        });

        piecesArea.addEventListener('dragover', e => e.preventDefault());
        piecesArea.addEventListener('drop', this.handleDrop.bind(this));
    }

    setupTouchEvents() {
        const pieces = document.querySelectorAll('.puzzle-piece');
        const slots = document.querySelectorAll('.puzzle-slot');

        pieces.forEach(piece => {
            piece.addEventListener('touchstart', this.handleTouchStart.bind(this));
            piece.addEventListener('touchmove', this.handleTouchMove.bind(this));
            piece.addEventListener('touchend', this.handleTouchEnd.bind(this));
        });

        slots.forEach(slot => {
            slot.addEventListener('touchstart', (e) => e.preventDefault());
        });
    }

    handleDragStart(e) {
        if (this.isPaused) {
            e.preventDefault();
            return;
        }
        const piece = e.target;
        
        if (piece.classList.contains('locked')) {
            e.preventDefault();
            return;
        }

        this.isDragging = true;
        clearTimeout(this.dragTimeout);
        
        e.dataTransfer.setData('text/plain', piece.dataset.pieceId);
        piece.classList.add('dragging');

        // Dolu slot'ları işaretle
        document.querySelectorAll('.puzzle-slot').forEach(slot => {
            if (slot.hasChildNodes()) {
                slot.classList.add('occupied');
            }
        });
    }

    handleDragEnd(e) {
        e.target.classList.remove('dragging');
        
        // Sürükleme durumunu ve işaretleri temizle
        this.dragTimeout = setTimeout(() => {
            this.isDragging = false;
            document.querySelectorAll('.puzzle-slot').forEach(slot => {
                slot.classList.remove('occupied');
                slot.classList.remove('hover');
            });
        }, 100);
    }

    handleDragEnter(e) {
        const slot = e.target.closest('.puzzle-slot');
        if (slot && slot.hasChildNodes()) {
            slot.classList.add('occupied');
        }
    }

    handleDragLeave(e) {
        const slot = e.target.closest('.puzzle-slot');
        if (slot) {
            slot.classList.remove('hover');
            if (!this.isDragging) {
                slot.classList.remove('occupied');
            }
        }
    }

    handleDragOver(e) {
        e.preventDefault();
        const slot = e.target.closest('.puzzle-slot');
        
        if (!slot) return;

        // Slot doluysa sürükleme efektini engelle
        if (slot.hasChildNodes()) {
            e.dataTransfer.dropEffect = 'none';
            slot.classList.add('occupied');
            slot.classList.remove('hover');
        } else {
            e.dataTransfer.dropEffect = 'move';
            slot.classList.add('hover');
        }
    }

    handleTouchStart(e) {
        if (this.isPaused) {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        const piece = e.target;
        
        // Eğer parça kilitliyse dokunma işlemini engelle
        if (piece.classList.contains('locked')) {
            return;
        }
        
        const touch = e.touches[0];
        const rect = piece.getBoundingClientRect();
        this.draggedPiece = piece;
        this.touchStartX = touch.clientX - rect.left;
        this.touchStartY = touch.clientY - rect.top;

        const pieceRect = piece.getBoundingClientRect();
        piece.style.width = pieceRect.width + 'px';
        piece.style.height = pieceRect.height + 'px';
        piece.style.position = 'fixed';
        piece.style.left = rect.left + 'px';
        piece.style.top = rect.top + 'px';
        piece.style.zIndex = '1000';
        piece.classList.add('dragging');
    }

    handleTouchMove(e) {
        if (!this.draggedPiece) return;
        e.preventDefault();

        const touch = e.touches[0];
        const rect = this.draggedPiece.getBoundingClientRect();
        
        // Yeni pozisyonu hesapla
        let newX = touch.clientX - this.touchStartX;
        let newY = touch.clientY - this.touchStartY;

        // Ekran sınırlarını kontrol et
        const maxX = window.innerWidth - rect.width;
        const maxY = window.innerHeight - rect.height;

        // Sınırlar içinde kal
        newX = Math.max(0, Math.min(maxX, newX));
        newY = Math.max(0, Math.min(maxY, newY));

        // Ani hareketleri yumuşat
        if (!this.lastPosition) {
            this.lastPosition = { x: newX, y: newY };
        } else {
            // Pozisyon değişimini sınırla
            const maxDelta = 20; // maksimum ani hareket mesafesi
            const deltaX = newX - this.lastPosition.x;
            const deltaY = newY - this.lastPosition.y;

            if (Math.abs(deltaX) > maxDelta) {
                newX = this.lastPosition.x + (maxDelta * Math.sign(deltaX));
            }
            if (Math.abs(deltaY) > maxDelta) {
                newY = this.lastPosition.y + (maxDelta * Math.sign(deltaY));
            }

            this.lastPosition = { x: newX, y: newY };
        }

        // Pozisyonu güncelle
        requestAnimationFrame(() => {
            if (this.draggedPiece) {
                this.draggedPiece.style.left = newX + 'px';
                this.draggedPiece.style.top = newY + 'px';
            }
        });

        // Slot kontrolü
        const slots = document.querySelectorAll('.puzzle-slot');
        const pieceCenter = {
            x: newX + rect.width / 2,
            y: newY + rect.height / 2
        };

        slots.forEach(slot => {
            const slotRect = slot.getBoundingClientRect();
            if (pieceCenter.x >= slotRect.left && pieceCenter.x <= slotRect.right &&
                pieceCenter.y >= slotRect.top && pieceCenter.y <= slotRect.bottom) {
                slot.classList.add('hover');
            } else {
                slot.classList.remove('hover');
            }
        });
    }

    handleTouchEnd(e) {
        if (!this.draggedPiece) return;

        const piecesArea = document.querySelector('.puzzle-pieces');
        let dropped = false;

        const pieceRect = this.draggedPiece.getBoundingClientRect();
        const pieceCenter = {
            x: pieceRect.left + pieceRect.width / 2,
            y: pieceRect.top + pieceRect.height / 2
        };

        // Önce draggedPiece'i yedekleyelim çünkü resetDraggedPiece ile null olacak
        const currentPiece = this.draggedPiece;

        // Parça kutusuna bırakma kontrolü
        const piecesAreaRect = piecesArea.getBoundingClientRect();
        if (pieceCenter.x >= piecesAreaRect.left && pieceCenter.x <= piecesAreaRect.right &&
            pieceCenter.y >= piecesAreaRect.top && pieceCenter.y <= piecesAreaRect.bottom) {
            
            this.resetDraggedPiece();
            currentPiece.style.position = '';
            currentPiece.style.width = '';
            currentPiece.style.height = '';
            piecesArea.appendChild(currentPiece);
            this.addToMoveQueue(currentPiece.dataset.pieceId, 'pieces-area', true);
            this.processMoveQueue(); // Hemen işle
            return;
        }

        // Slot'lara bırakma kontrolü
        const slots = document.querySelectorAll('.puzzle-slot');
        slots.forEach(slot => {
            const rect = slot.getBoundingClientRect();
            if (pieceCenter.x >= rect.left && pieceCenter.x <= rect.right &&
                pieceCenter.y >= rect.top && pieceCenter.y <= rect.bottom) {
                
                if (!slot.hasChildNodes() || slot.firstChild === currentPiece) {
                    this.resetDraggedPiece();
                    slot.innerHTML = ''; // Slot'u temizle
                    slot.appendChild(currentPiece);
                    dropped = true;

                    const slotIndex = Array.from(slot.parentNode.children).indexOf(slot);
                    const correctPosition = `${Math.floor(slotIndex / this.puzzleData.gridSize)}_${slotIndex % this.puzzleData.gridSize}`;

                    if (currentPiece.dataset.pieceId === correctPosition) {
                        slot.classList.add('correct');
                        currentPiece.classList.add('locked');
                        this.lockedPieces.add(currentPiece.dataset.pieceId);
                    }

                    this.addToMoveQueue(currentPiece.dataset.pieceId, slot.dataset.slotId);
                    this.processMoveQueue(); // Hemen işle
                    this.checkCompletion();
                }
            }
            slot.classList.remove('hover');
        });

        // Eğer geçerli bir yere bırakılmadıysa
        if (!dropped) {
            this.resetDraggedPiece();
            if (currentPiece.parentNode.classList.contains('puzzle-slot')) {
                currentPiece.parentNode.appendChild(currentPiece);
            } else {
                piecesArea.appendChild(currentPiece);
                this.addToMoveQueue(currentPiece.dataset.pieceId, 'pieces-area', true);
                this.processMoveQueue(); // Hemen işle
            }
        }
    }

    resetDraggedPiece() {
        if (!this.draggedPiece) return;
        this.draggedPiece.style.position = '';
        this.draggedPiece.style.left = '';
        this.draggedPiece.style.top = '';
        this.draggedPiece.style.width = '';
        this.draggedPiece.style.height = '';
        this.draggedPiece.style.zIndex = '';
        this.draggedPiece.classList.remove('dragging');
        this.draggedPiece = null;
        this.lastPosition = null; // Son pozisyonu temizle
    }

    checkCompletion() {
        const slots = document.querySelectorAll('.puzzle-slot');
        let isComplete = true;

        slots.forEach((slot, index) => {
            const piece = slot.firstChild;
            if (!piece || piece.dataset.pieceId !== `${Math.floor(index / this.puzzleData.gridSize)}_${index % this.puzzleData.gridSize}`) {
                isComplete = false;
            }
        });

        if (isComplete) {
            this.handleGameComplete();
        }
    }

    async handleGameComplete() {
        // Timer'ı durdur
        clearInterval(this.timer);
        this.stopAllControls();
        
        try {
            const response = await this.saveScore();
            const data = await response.json();
            
            // Orijinal resim yolunu al
            const response2 = await fetch(`/puzzle/api/get-image-path?puzzleId=${this.puzzleData.id}`);
            const imageData = await response2.json();
            const imagePath = imageData.image_path;
            
            // Tamamlanmış puzzle görünümü
            const completedPuzzleHTML = `
                <div class="completed-puzzle-overlay">
                    <div class="completed-puzzle-container">
                        <div class="puzzle-board completed">
                            <div class="puzzle-grid completed" style="--grid-size: ${this.puzzleData.gridSize};">
                                ${this.generateCompletedPuzzlePieces(imagePath)}
                            </div>
                        </div>
                        <div class="completion-message">${this.puzzleData.completionMessage}</div>
                        <div class="completion-time">Tamamlama Süresi: ${this.formatTime(this.seconds)}</div>
                        ${data.globalBestScore ? `
                            <div class="best-score">Puzzle'ın En İyi Süresi: ${this.formatTime(data.globalBestScore)}</div>
                        ` : ''}
                        <div class="button-group">
                            <button class="restart-button" onclick="showResetPopup()">Yeniden Başlat</button>
                            <button class="home-button" onclick="location.href='/puzzle/'">Ana Sayfa</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Mevcut tamamlanma ekranını kaldır
            const existingOverlay = document.querySelector('.completed-puzzle-overlay');
            if (existingOverlay) {
                existingOverlay.remove();
            }
            
            document.body.insertAdjacentHTML('beforeend', completedPuzzleHTML);
            
            // Polling ve diğer kontrolleri durdur
            clearInterval(this.pollingInterval);
            this.disablePieceMovement();
            
            // Oyun tamamlandı bayrağını ayarla
            this.puzzleData.isCompleted = true;
            
            // Timer'ı durdur ve son süreyi kaydet
            clearInterval(this.timer);
            this.lastTimeSave = this.seconds;

            // 3 saniye sonra hamleleri sil
            setTimeout(async () => {
                try {
                    await fetch('/puzzle/api/delete-moves', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            puzzleId: this.puzzleData.id
                        })
                    });
                } catch (error) {
                    console.error('Hamle silme hatası:', error);
                }
            }, 3000);
            
        } catch (error) {
            console.error('Oyun sonu işlemleri hatası:', error);
        }
    }

    generateCompletedPuzzlePieces(imagePath) {
        const pieces = [];
        const gridSize = this.puzzleData.gridSize;
        
        for (let i = 0; i < gridSize * gridSize; i++) {
            const row = Math.floor(i / gridSize);
            const col = i % gridSize;
            pieces.push(`
                <div class="puzzle-piece completed" 
                     style="background-image: url('${imagePath}');
                            background-size: ${gridSize * 100}%;
                            background-position: ${-col * 100}% ${-row * 100}%;">
                </div>
            `);
        }
        
        return pieces.join('');
    }

    stopAllControls() {
        // Hamle kontrolünü durdur
        clearInterval(this.moveCheckInterval);
        // Süre kontrolünü durdur
        clearInterval(this.timer);
        // Polling'i durdur
        clearInterval(this.pollingInterval);
        // Parça hareketlerini devre dışı bırak
        this.disablePieceMovement();
    }

    disablePieceMovement() {
        const pieces = document.querySelectorAll('.puzzle-piece');
        pieces.forEach(piece => {
            piece.draggable = false;
            piece.style.pointerEvents = 'none';
        });
    }

    startTimer() {
        this.timer = setInterval(async () => {
            if (!this.isPaused && !this.puzzleData.isCompleted) { // isCompleted kontrolü ekle
                this.seconds++;
                document.getElementById('timer').textContent = this.formatTime(this.seconds);
                
                // Her 5 saniyede bir süreyi sunucuya kaydet
                if (this.seconds - this.lastTimeSave >= 5) {
                    this.lastTimeSave = this.seconds;
                    try {
                        await fetch('/puzzle/api/save-time', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                puzzleId: this.puzzleData.id,
                                seconds: this.seconds
                            })
                        });
                    } catch (error) {
                        console.error('Süre kaydetme hatası:', error);
                    }
                }
            }
        }, 1000);
    }

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    setupControls() {
        // Önce elementlerin varlığını kontrol edelim
        const pauseButton = document.getElementById('pauseButton');
        const resetButton = document.getElementById('resetButton');

        if (pauseButton) {
            pauseButton.addEventListener('click', () => {
                this.togglePause();
            });
        }

        if (resetButton) {
            resetButton.addEventListener('click', () => {
                showResetPopup();
            });
        }

        // Oyun duraklatılmış olarak başladıysa
        if (this.isPaused) {
            this.pauseGame();
        }
    }

    togglePause() {
        if (this.isPaused) {
            this.resumeGame();
        } else {
            this.pauseGame();
        }

        // API'ye durumu bildir
        fetch('/puzzle/api/toggle-pause', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                puzzleId: this.puzzleData.id,
                isPaused: this.isPaused
            })
        });
    }

    pauseGame() {
        this.isPaused = true;
        clearInterval(this.timer);
        const pauseButton = document.getElementById('pauseButton');
        if (pauseButton) {
            pauseButton.textContent = 'Devam Et';
        }

        // Puzzle alanını kilitlemek için
        const puzzleBoard = document.getElementById('puzzleBoard');
        if (puzzleBoard) {
            puzzleBoard.style.opacity = '0.7';
            puzzleBoard.style.pointerEvents = 'none';
        }

        // Parça alanını kilitlemek için
        const piecesArea = document.querySelector('.puzzle-pieces');
        if (piecesArea) {
            piecesArea.style.opacity = '0.7';
            piecesArea.style.pointerEvents = 'none';
        }
    }

    resumeGame() {
        this.isPaused = false;
        this.startTimer();
        const pauseButton = document.getElementById('pauseButton');
        if (pauseButton) {
            pauseButton.textContent = 'Duraklat';
        }

        // Puzzle alanını tekrar aktif etmek için
        const puzzleBoard = document.getElementById('puzzleBoard');
        if (puzzleBoard) {
            puzzleBoard.style.opacity = '1';
            puzzleBoard.style.pointerEvents = 'auto';
        }

        // Parça alanını tekrar aktif etmek için
        const piecesArea = document.querySelector('.puzzle-pieces');
        if (piecesArea) {
            piecesArea.style.opacity = '1';
            piecesArea.style.pointerEvents = 'auto';
        }
    }

    async saveScore() {
        return await fetch('/puzzle/api/save-score', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                puzzleId: this.puzzleData.id,
                completionTime: this.seconds
            })
        });
    }

    updateGameState(isPaused) {
        this.isPaused = isPaused;
        document.getElementById('pauseButton').textContent = isPaused ? 'Devam Et' : 'Duraklat';
        
        // Tüm parçaları güncelle
        const pieces = document.querySelectorAll('.puzzle-piece');
        pieces.forEach(piece => {
            piece.draggable = !isPaused;
            piece.style.opacity = isPaused ? '0.6' : '1';
            if (isPaused) {
                piece.style.pointerEvents = 'none';
            } else {
                piece.style.pointerEvents = 'auto';
            }
        });

        // Tüm slot'ları güncelle
        const slots = document.querySelectorAll('.puzzle-slot');
        slots.forEach(slot => {
            if (isPaused) {
                slot.style.pointerEvents = 'none';
            } else {
                slot.style.pointerEvents = 'auto';
            }
        });
    }

    // Düzenli kontrol için
    startPolling() {
        setInterval(async () => {
            try {
                // Her zaman durum kontrolü yap
                const pauseResponse = await fetch(`/puzzle/api/get-puzzle-state?puzzleId=${this.puzzleData.id}`);
                if (!pauseResponse.ok) throw new Error('Durum kontrolü başarısız');
                const pauseData = await pauseResponse.json();

                // Reset kontrolü
                if (pauseData.reset_timestamp && 
                    pauseData.reset_timestamp !== this.lastResetTimestamp && 
                    this.lastResetTimestamp !== null) {
                    
                    this.lastResetTimestamp = pauseData.reset_timestamp;
                    // Oyunu sıfırla
                    this.seconds = 0;
                    this.lastTimeSave = 0;
                    document.getElementById('timer').textContent = this.formatTime(0);
                    
                    // Timer'ı yeniden başlat
                    if (this.timer) {
                        clearInterval(this.timer);
                    }
                    this.startTimer();
                    
                    // Sayfayı yenile
                    window.location.reload();
                    return;
                }

                // İlk yüklemede timestamp'i kaydet
                if (this.lastResetTimestamp === null) {
                    this.lastResetTimestamp = pauseData.reset_timestamp;
                }

                // Durum değişikliği varsa uygula
                if (this.isPaused !== (pauseData.is_paused === 1)) {
                    if (pauseData.is_paused === 1) {
                        this.pauseGame();
                    } else {
                        this.resumeGame();
                    }
                }

                // Sadece oyun duraklatılmamışsa diğer kontrolleri yap
                if (!this.isPaused) {
                    const [movesResponse, timeResponse] = await Promise.all([
                        fetch(`/puzzle/api/check-moves?puzzleId=${this.puzzleData.id}&lastMoveId=${this.lastMoveId}`),
                        fetch(`/puzzle/api/get-time?puzzleId=${this.puzzleData.id}`)
                    ]);

                    if (!movesResponse.ok || !timeResponse.ok) {
                        throw new Error('API yanıt hatası');
                    }

                    const [movesData, timeData] = await Promise.all([
                        movesResponse.json(),
                        timeResponse.json()
                    ]);

                    // Hamleleri güncelle
                    if (movesData.moves && movesData.moves.length > 0) {
                        movesData.moves.forEach(move => {
                            this.updatePiecePosition(move.piece_id, move.slot_id, move.is_return === 1);
                            this.lastMoveId = Math.max(this.lastMoveId, move.id);
                        });
                    }

                    // Süreyi sadece sunucudaki süre daha büyükse güncelle
                    if (timeData.seconds > this.seconds) {
                        this.seconds = timeData.seconds;
                        document.getElementById('timer').textContent = this.formatTime(this.seconds);
                    }
                }
            } catch (error) {
                console.error('Polling hatası:', error);
            }
        }, 1000);
    }

    // Kuyruğa hareket ekle
    addToMoveQueue(pieceId, slotId, isReturn = false) {
        // Eğer aynı hareket zaten kuyrukta varsa ekleme
        const lastMoveForPiece = this.lastMove.get(pieceId);
        if (lastMoveForPiece === slotId) {
            return;
        }

        // Son hareketi güncelle
        this.lastMove.set(pieceId, slotId);

        // Kuyruğa ekle
        this.moveQueue.push({
            pieceId,
            slotId,
            isReturn,
            timestamp: Date.now()
        });

        // Kuyruk işlemeyi başlat
        if (!this.isProcessingQueue) {
            this.processMoveQueue();
        }
    }

    // Kuyruktaki hareketleri işle
    async processMoveQueue() {
        if (this.isProcessingQueue) return;
        this.isProcessingQueue = true;

        try {
            while (this.moveQueue.length > 0) {
                const move = this.moveQueue[0];
                
                // Son hareket kontrolü
                if (this.lastMove.get(move.pieceId) === move.slotId) {
                    try {
                        await this.saveMove(move.pieceId, move.slotId, move.isReturn);
                        this.moveQueue.shift();
                    } catch (error) {
                        console.error('Move save error:', error);
                        // Hata durumunda kuyruğun sonuna at
                        const failedMove = this.moveQueue.shift();
                        if (!failedMove.retryCount || failedMove.retryCount < 2) {
                            failedMove.retryCount = (failedMove.retryCount || 0) + 1;
                            this.moveQueue.push(failedMove);
                        }
                    }
                } else {
                    // Hareket artık geçerli değil, kaldır
                    this.moveQueue.shift();
                }

                // İşlemler arası küçük bir bekleme
                await new Promise(resolve => setTimeout(resolve, this.processingDelay));
            }
        } finally {
            this.isProcessingQueue = false;
        }
    }

    // Hareket kaydetme
    async saveMove(pieceId, slotId, isReturn = false) {
        const response = await fetch('/puzzle/api/save-move', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                puzzleId: this.puzzleData.id,
                pieceId,
                slotId,
                isReturn
            })
        });

        if (!response.ok) {
            throw new Error('Move save failed');
        }

        return response.json();
    }

    updatePiecePosition(pieceId, slotId, isReturn = false) {
        const piece = document.querySelector(`[data-piece-id="${pieceId}"]`);
        const piecesArea = document.querySelector('.puzzle-pieces');
        
        if (!piece) return;

        // Önce parçanın mevcut konumunu temizle
        const currentSlot = document.querySelector(`.puzzle-slot .puzzle-piece[data-piece-id="${pieceId}"]`);
        if (currentSlot) {
            const parentSlot = currentSlot.closest('.puzzle-slot');
            if (parentSlot) {
                // Parçayı önce parça kutusuna taşı
                const removedPiece = parentSlot.removeChild(currentSlot);
                removedPiece.classList.remove('locked');
                removedPiece.style.position = '';
                removedPiece.style.width = '';
                removedPiece.style.height = '';
                piecesArea.appendChild(removedPiece);
                
                // Sonra slot'u temizle
                parentSlot.classList.remove('correct');
                this.lockedPieces.delete(pieceId);
            }
        }

        // Eğer parça geri alınıyorsa veya pieces-area'ya taşınıyorsa
        if (isReturn || slotId === 'pieces-area') {
            return; // Parça zaten parça kutusuna taşındı
        }
        
        const slot = document.querySelector(`[data-slot-id="${slotId}"]`);
        if (!slot) return;

        // Eğer slot doluysa ve içindeki parça kilitli değilse
        if (slot.hasChildNodes()) {
            const existingPiece = slot.firstChild;
            if (!existingPiece.classList.contains('locked')) {
                existingPiece.style.position = '';
                existingPiece.style.width = '';
                existingPiece.style.height = '';
                piecesArea.appendChild(existingPiece);
                slot.innerHTML = ''; // Slot'u temizle
            } else {
                return; // Kilitli parçayı değiştirmeye çalışıyorsa işlemi iptal et
            }
        }

        // Yeni parçayı yerleştir
        slot.appendChild(piece);

        // Parçanın doğru yerde olup olmadığını kontrol et
        const slotIndex = Array.from(slot.parentNode.children).indexOf(slot);
        const correctPosition = `${Math.floor(slotIndex / this.puzzleData.gridSize)}_${slotIndex % this.puzzleData.gridSize}`;
        
        if (piece.dataset.pieceId === correctPosition) {
            slot.classList.add('correct');
            piece.classList.add('locked');
            this.lockedPieces.add(piece.dataset.pieceId);
        }

        this.checkCompletion();
    }

    async resetGame() {
        showResetPopup();
    }

    handleDrop(e) {
        if (this.isPaused) {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        
        const piece = document.querySelector('.dragging');
        if (!piece) return;
        
        piece.classList.remove('dragging');
        
        const target = e.target.closest('.puzzle-slot') || e.target.closest('.puzzle-pieces');
        if (!target) return;
        
        // Parça zaten kilitliyse işlemi iptal et
        if (piece.classList.contains('locked')) return;
        
        // Hedef slot doluysa ve içindeki parça kilitliyse işlemi iptal et
        if (target.classList.contains('puzzle-slot') && 
            target.firstChild && 
            target.firstChild.classList.contains('locked')) {
            return;
        }

        const pieceId = piece.dataset.pieceId;
        const slotId = target.classList.contains('puzzle-slot') ? target.dataset.slotId : 'pieces-area';
        
        // Parça kutusuna geri alınıyorsa
        if (target.classList.contains('puzzle-pieces') || target.closest('.puzzle-pieces')) {
            piece.style.position = '';
            piece.style.width = '';
            piece.style.height = '';
            target.appendChild(piece);
            this.addToMoveQueue(pieceId, 'pieces-area', true);
            return;
        }

        // Slot'a yerleştirme
        if (target.classList.contains('puzzle-slot')) {
            // Eğer slot doluysa ve içindeki parça kilitli değilse
            if (target.hasChildNodes()) {
                const existingPiece = target.firstChild;
                if (!existingPiece.classList.contains('locked')) {
                    existingPiece.style.position = '';
                    existingPiece.style.width = '';
                    existingPiece.style.height = '';
                    document.querySelector('.puzzle-pieces').appendChild(existingPiece);
                    this.addToMoveQueue(existingPiece.dataset.pieceId, 'pieces-area', true);
                } else {
                    return; // Kilitli parçayı değiştirmeye çalışıyorsa işlemi iptal et
                }
            }

            // Yeni parçayı yerleştir
            target.appendChild(piece);
            
            // Doğru konumda mı kontrol et
            const slotIndex = Array.from(target.parentNode.children).indexOf(target);
            const correctPosition = `${Math.floor(slotIndex / this.puzzleData.gridSize)}_${slotIndex % this.puzzleData.gridSize}`;
            
            if (piece.dataset.pieceId === correctPosition) {
                target.classList.add('correct');
                piece.classList.add('locked');
                this.lockedPieces.add(piece.dataset.pieceId);
            }

            this.checkCompletion();
            this.addToMoveQueue(pieceId, target.dataset.slotId);
        }

        // Tüm slot'ların hover durumunu temizle
        document.querySelectorAll('.puzzle-slot').forEach(slot => {
            slot.classList.remove('hover', 'occupied');
        });
    }
}

// Oyunu başlat
document.addEventListener('DOMContentLoaded', () => {
    if (window.puzzleData) {
        new PuzzleGame(window.puzzleData);
    }
});

// CSS için yeni stil eklemeleri
const style = document.createElement('style');
style.textContent = `
    @media (max-width: 768px) {
        .puzzle-piece {
            touch-action: none;
            user-select: none;
            -webkit-user-select: none;
        }
        
        .puzzle-piece.dragging {
            transition: none !important;
        }
        
        .puzzle-slot {
            min-height: 40px;
            min-width: 40px;
        }
    }
`;
document.head.appendChild(style); 