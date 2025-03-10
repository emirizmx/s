<form method="POST" enctype="multipart/form-data">
    <!-- Mevcut alanlar... -->
    
    <div class="form-group">
        <label for="completion_message">Oyun Sonu Mesajı:</label>
        <input type="text" 
               name="completion_message" 
               id="completion_message" 
               class="form-control"
               value="Tebrikler! Puzzle'ı tamamladınız!"
               maxlength="255">
    </div>
    
    <div class="form-group">
        <label for="visibility">Oyun Durumu:</label>
        <select name="visibility" id="visibility" class="form-control">
            <option value="public">Herkese Açık</option>
            <option value="private">Özel</option>
        </select>
    </div>
    
    <!-- Diğer form alanları... -->
</form> 