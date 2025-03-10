<?php require_once __DIR__ . '/../../../../includes/header.php'; ?>

<div class="container" style="margin-top: var(--header-height); padding: 2rem;">
    <div class="story-creator" style="max-width: 800px; margin: 0 auto;">
        <!-- Başlık ve Hikayelerim Butonu -->
        <div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        ">
            <h1 style="color: var(--heading-color);">Hikaye Oluşturucu</h1>
            <a href="/story/list" style="
                padding: 0.8rem 1.5rem;
                background: var(--surface-color);
                color: var(--text-color);
                text-decoration: none;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                border: 1px solid var(--border-color);
                transition: all 0.3s ease;
            ">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Hikayelerim
            </a>
        </div>

        <form id="storyForm" style="
            background: var(--surface-color);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
        ">
            <!-- Hikaye Türü -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-color);">
                    Hikaye Türü
                </label>
                <select id="storyType" name="storyType" required style="
                    width: 100%;
                    padding: 0.8rem;
                    border: 1px solid var(--border-color);
                    border-radius: 10px;
                    background: var(--background-color);
                    color: var(--text-color);
                ">
                    <option value="fairytale">Masal</option>
                    <option value="love">Aşk Hikayesi</option>
                    <option value="adventure">Macera</option>
                    <option value="memory">Anı/Hatıra</option>
                    <option value="fantasy">Fantastik</option>
                    <option value="comedy">Komedi</option>
                </select>
            </div>

            <!-- Anlatım Şekli -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-color);">
                    Anlatım Şekli
                </label>
                <select id="narrativeStyle" name="narrativeStyle" required style="
                    width: 100%;
                    padding: 0.8rem;
                    border: 1px solid var(--border-color);
                    border-radius: 10px;
                    background: var(--background-color);
                    color: var(--text-color);
                ">
                    <option value="classic">Klasik Anlatım</option>
                    <option value="first_person">Birinci Tekil Şahıs (Ben Dili)</option>
                    <option value="third_person">Üçüncü Tekil Şahıs (O Dili)</option>
                    <option value="letter">Mektup Formatı</option>
                    <option value="diary">Günlük Formatı</option>
                </select>
            </div>

            <!-- Oluşturma Yöntemi -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-color);">
                    Oluşturma Yöntemi
                </label>
                <select id="creationMethod" name="creationMethod" required style="
                    width: 100%;
                    padding: 0.8rem;
                    border: 1px solid var(--border-color);
                    border-radius: 10px;
                    background: var(--background-color);
                    color: var(--text-color);
                ">
                    <option value="ai">Yapay Zeka Oluştursun</option>
                    <option value="manual">Kendi Hikayemi Yazacağım</option>
                    <option value="hybrid">Karma (Ana Hatları Ben Vereyim)</option>
                </select>
            </div>

            <!-- Başlık -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-color);">
                    Hikaye Başlığı
                </label>
                <input type="text" id="title" name="title" required style="
                    width: 100%;
                    padding: 0.8rem;
                    border: 1px solid var(--border-color);
                    border-radius: 10px;
                    background: var(--background-color);
                    color: var(--text-color);
                ">
            </div>

            <!-- Karakterler -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-color);">
                    Karakterler
                </label>
                <div id="characters">
                    <div class="character" style="
                        display: grid;
                        grid-template-columns: 1fr 1fr auto;
                        gap: 1rem;
                        margin-bottom: 1rem;
                    ">
                        <input type="text" name="characters[0][name]" placeholder="Karakter Adı" required style="
                            padding: 0.8rem;
                            border: 1px solid var(--border-color);
                            border-radius: 10px;
                            background: var(--background-color);
                            color: var(--text-color);
                        ">
                        <input type="text" name="characters[0][type]" placeholder="Karakter Özelliği" required style="
                            padding: 0.8rem;
                            border: 1px solid var(--border-color);
                            border-radius: 10px;
                            background: var(--background-color);
                            color: var(--text-color);
                        ">
                        <button type="button" class="remove-character" style="display: none;">❌</button>
                    </div>
                </div>
                <button type="button" id="addCharacter" style="
                    padding: 0.5rem 1rem;
                    background: var(--accent-color);
                    color: white;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                ">+ Karakter Ekle</button>
            </div>

            <!-- Hikaye Detayları -->
            <div id="storyDetails" style="margin-bottom: 2rem;">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-color);">
                        Mekan ve Zaman
                    </label>
                    <input type="text" name="setting" placeholder="Örn: Modern İstanbul, Ortaçağ Kalesi..." style="
                        width: 100%;
                        padding: 0.8rem;
                        border: 1px solid var(--border-color);
                        border-radius: 10px;
                        background: var(--background-color);
                        color: var(--text-color);
                    ">
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-color);">
                        Ana Tema/Duygu
                    </label>
                    <input type="text" name="theme" placeholder="Örn: Aşk, Dostluk, Macera..." style="
                        width: 100%;
                        padding: 0.8rem;
                        border: 1px solid var(--border-color);
                        border-radius: 10px;
                        background: var(--background-color);
                        color: var(--text-color);
                    ">
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-color);">
                        Özel Detaylar
                    </label>
                    <textarea name="details" placeholder="Anılar, önemli tarihler, özel istekler..." style="
                        width: 100%;
                        padding: 0.8rem;
                        border: 1px solid var(--border-color);
                        border-radius: 10px;
                        background: var(--background-color);
                        color: var(--text-color);
                        min-height: 100px;
                    "></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-color);">
                        Hedef Kitle
                    </label>
                    <select name="audience" style="
                        width: 100%;
                        padding: 0.8rem;
                        border: 1px solid var(--border-color);
                        border-radius: 10px;
                        background: var(--background-color);
                        color: var(--text-color);
                    ">
                        <option value="children">Çocuklar</option>
                        <option value="young">Gençler</option>
                        <option value="adult">Yetişkinler</option>
                    </select>
                </div>

                <div class="form-group" style="display: none;">
                    <input type="hidden" name="length" value="500 kelime">
                </div>
            </div>

            <!-- Kendi Hikayesi -->
            <div id="manualStoryContent" style="display: none; margin-bottom: 2rem;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-color);">
                        Hikayenizi Yazın
                    </label>
                    <textarea name="content" style="
                        width: 100%;
                        padding: 0.8rem;
                        border: 1px solid var(--border-color);
                        border-radius: 10px;
                        background: var(--background-color);
                        color: var(--text-color);
                        min-height: 300px;
                    "></textarea>
                </div>
            </div>

            <?php
            // Hikaye oluşturma için gereken kredi miktarını veritabanından al
            $stmtStory = $this->db->prepare("
                SELECT credits FROM dh_games 
                WHERE route = 'story/list'
            ");
            $stmtStory->execute();
            $gameStory = $stmtStory->fetch(PDO::FETCH_ASSOC);
            $storyCredits = $gameStory && isset($gameStory['credits']) ? (int)$gameStory['credits'] : 100;
            ?>

            <button type="submit" style="
                width: 100%;
                padding: 1rem;
                background: var(--primary-color);
                color: white;
                border: none;
                border-radius: 10px;
                font-size: 1.1rem;
                cursor: pointer;
            ">Hikaye Oluştur (<?php echo $storyCredits; ?> Kredi)</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('storyForm');
    const charactersDiv = document.getElementById('characters');
    const addCharacterBtn = document.getElementById('addCharacter');
    const creationMethodSelect = document.getElementById('creationMethod');
    const storyDetails = document.getElementById('storyDetails');
    const manualStoryContent = document.getElementById('manualStoryContent');

    // Oluşturma yöntemi değiştiğinde
    creationMethodSelect.addEventListener('change', function() {
        if (this.value === 'manual') {
            storyDetails.style.display = 'none';
            manualStoryContent.style.display = 'block';
        } else {
            storyDetails.style.display = 'block';
            manualStoryContent.style.display = 'none';
        }
    });

    // Karakter ekleme
    let characterCount = 1;
    addCharacterBtn.addEventListener('click', function() {
        const characterDiv = document.createElement('div');
        characterDiv.className = 'character';
        characterDiv.style.cssText = `
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            margin-bottom: 1rem;
        `;

        characterDiv.innerHTML = `
            <input type="text" name="characters[${characterCount}][name]" placeholder="Karakter Adı" required style="
                padding: 0.8rem;
                border: 1px solid var(--border-color);
                border-radius: 10px;
                background: var(--background-color);
                color: var(--text-color);
            ">
            <input type="text" name="characters[${characterCount}][type]" placeholder="Karakter Özelliği" required style="
                padding: 0.8rem;
                border: 1px solid var(--border-color);
                border-radius: 10px;
                background: var(--background-color);
                color: var(--text-color);
            ">
            <button type="button" class="remove-character" style="
                padding: 0.5rem;
                background: var(--error-color);
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            ">❌</button>
        `;

        charactersDiv.appendChild(characterDiv);
        characterCount++;
    });

    // Karakter silme
    charactersDiv.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-character')) {
            e.target.parentElement.remove();
        }
    });

    // Form gönderimi
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        
        try {
            submitButton.disabled = true;
            submitButton.textContent = 'Hikaye Oluşturuluyor...';
            
            const response = await fetch('index.php?action=create', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.error) {
                throw new Error(result.error);
            }
            
            window.location.href = `index.php?action=view&id=${result.story_id}`;
            
        } catch (error) {
            alert(error.message);
            submitButton.disabled = false;
            submitButton.textContent = 'Hikaye Oluştur (<?php echo $storyCredits; ?> Kredi)';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../../../includes/footer.php'; ?>