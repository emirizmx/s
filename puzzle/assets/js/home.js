document.addEventListener('DOMContentLoaded', function() {
    // Durum değiştirme select elementlerini bul
    const visibilitySelects = document.querySelectorAll('.visibility-select');
    
    // Her select için event listener ekle
    visibilitySelects.forEach(select => {
        select.addEventListener('change', async function() {
            const puzzleId = this.dataset.puzzleId;
            const newVisibility = this.value;
            
            try {
                const response = await fetch('/puzzle/api/update-puzzle-visibility', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        puzzleId: puzzleId,
                        visibility: newVisibility
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Başarılı güncelleme bildirimi
                    alert('Oyun durumu güncellendi!');
                } else {
                    throw new Error(data.error || 'Güncelleme başarısız');
                }
            } catch (error) {
                console.error('Hata:', error);
                alert('Oyun durumu güncellenirken bir hata oluştu!');
                // Hata durumunda önceki değere geri dön
                this.value = this.value === 'public' ? 'private' : 'public';
            }
        });
    });

    // Resim yükleme ve kırpma işlemleri
    let cropper = null;
    const preview = document.getElementById('preview');
    const imageInput = document.getElementById('image');
    const previewContainer = document.querySelector('.image-preview-container');
    const cropDataInput = document.getElementById('cropData');
    const puzzleForm = document.getElementById('puzzleForm');

    // Elementlerin varlığını kontrol et ve sadece varsa event listener ekle
    if (imageInput && preview && puzzleForm) {
        // Resim seçildiğinde
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';
                    
                    // Önceki cropper varsa yok et
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    // Yeni cropper oluştur
                    cropper = new Cropper(preview, {
                        aspectRatio: 1,
                        viewMode: 1,
                        guides: true,
                        autoCropArea: 0.8,
                        responsive: true,
                        crop: function(e) {
                            // Kırpma verilerini sakla
                            cropDataInput.value = JSON.stringify({
                                x: Math.round(e.detail.x),
                                y: Math.round(e.detail.y),
                                width: Math.round(e.detail.width),
                                height: Math.round(e.detail.height),
                                rotate: e.detail.rotate
                            });
                        }
                    });
                };
                
                reader.readAsDataURL(file);
            }
        });

        // Döndürme butonları - sadece varsa event listener ekle
        const rotateLeft = document.getElementById('rotateLeft');
        const rotateRight = document.getElementById('rotateRight');
        const resetCrop = document.getElementById('resetCrop');

        if (rotateLeft) {
            rotateLeft.addEventListener('click', function() {
                if (cropper) cropper.rotate(-90);
            });
        }

        if (rotateRight) {
            rotateRight.addEventListener('click', function() {
                if (cropper) cropper.rotate(90);
            });
        }

        if (resetCrop) {
            resetCrop.addEventListener('click', function() {
                if (cropper) cropper.reset();
            });
        }

        // Form gönderilmeden önce kontrol
        puzzleForm.addEventListener('submit', function(e) {
            if (!cropDataInput.value) {
                e.preventDefault();
                alert('Lütfen bir resim seçin ve kırpın');
            }
        });
    }

    // Oyun sonu mesajı değişikliği için event listener
    const completionMessageInputs = document.querySelectorAll('.completion-message-input');
    if (completionMessageInputs.length > 0) {
        completionMessageInputs.forEach(input => {
            let timeoutId;
            input.addEventListener('input', function() {
                clearTimeout(timeoutId);
                
                timeoutId = setTimeout(async () => {
                    const puzzleId = this.dataset.puzzleId;
                    const newMessage = this.value.trim();
                    
                    try {
                        const response = await fetch('/puzzle/api/update-completion-message', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                puzzleId: puzzleId,
                                message: newMessage
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Başarılı güncelleme bildirimi
                            const originalBackground = this.style.backgroundColor;
                            this.style.backgroundColor = '#e8f5e9';
                            setTimeout(() => {
                                this.style.backgroundColor = originalBackground;
                            }, 500);
                            
                            // Toast bildirimi göster
                            showToast('Oyun sonu mesajınız güncellendi', 'success');
                        } else {
                            throw new Error(data.error || 'Güncelleme başarısız');
                        }
                    } catch (error) {
                        console.error('Hata:', error);
                        showToast('Mesaj güncellenirken bir hata oluştu!', 'error');
                        this.value = this.defaultValue;
                    }
                }, 500);
            });
        });
    }

    // YouTube URL kontrol butonu
    const checkYoutubeUrlBtn = document.getElementById('checkYoutubeUrl');
    const youtubeUrlInput = document.getElementById('youtube_url');
    const youtubePreview = document.getElementById('youtubePreview');

    if (checkYoutubeUrlBtn) {
        checkYoutubeUrlBtn.addEventListener('click', async function() {
            const url = youtubeUrlInput.value.trim();
            if (!url) {
                showToast('Lütfen bir YouTube URL\'si girin', 'error');
                return;
            }

            try {
                const response = await fetch(`/puzzle/api/check-youtube-url?url=${encodeURIComponent(url)}`);
                const data = await response.json();

                if (data.success) {
                    const video = data.data;
                    
                    // Preview alanını güncelle
                    youtubePreview.innerHTML = `
                        <div class="video-info">
                            <img src="${video.thumbnails.medium.url}" alt="${video.title}" class="video-thumbnail">
                            <div class="video-details">
                                <h4 class="video-title">${video.title}</h4>
                                <p class="video-channel">${video.channelTitle}</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" onclick="closeYoutubePreview()">×</button>
                    `;
                    
                    youtubePreview.style.display = 'flex';
                    youtubePreview.classList.remove('error');
                    showToast('Video başarıyla doğrulandı', 'success');
                } else {
                    throw new Error(data.error || 'Video doğrulanamadı');
                }
            } catch (error) {
                youtubePreview.innerHTML = `
                    <div class="video-info">
                        <div class="video-details">
                            <h4 class="video-title">Hata: ${error.message}</h4>
                        </div>
                    </div>
                    <button type="button" class="btn-close" onclick="closeYoutubePreview()">×</button>
                `;
                youtubePreview.style.display = 'flex';
                youtubePreview.classList.add('error');
                showToast('Video doğrulanamadı', 'error');
            }
        });
    }
});

// YouTube preview'ı kapatma fonksiyonu
window.closeYoutubePreview = function() {
    const youtubePreview = document.getElementById('youtubePreview');
    if (youtubePreview) {
        youtubePreview.style.display = 'none';
    }
};

// Toast bildirimi gösterme fonksiyonu
function showToast(message, type = 'success') {
    // Toast container'ı kontrol et veya oluştur
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    // Toast elementini oluştur
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Toast içeriğini oluştur
    toast.innerHTML = `
        <span class="toast-icon">✓</span>
        <span class="toast-message">${message}</span>
    `;

    // Toast'u container'a ekle
    container.appendChild(toast);

    // Animasyon için setTimeout kullan
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    // Toast'u kaldır
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            container.removeChild(toast);
            // Eğer container boşsa onu da kaldır
            if (container.children.length === 0) {
                document.body.removeChild(container);
            }
        }, 300);
    }, 3000);
}