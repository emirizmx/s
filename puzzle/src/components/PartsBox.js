import React, { useState } from 'react';

function PartsBox() {
  const [currentPage, setCurrentPage] = useState(1);
  const partsPerPage = 6; // Her sayfada gösterilecek parça sayısı
  
  // Toplam sayfa sayısını hesapla
  const totalPages = Math.ceil(parts.length / partsPerPage);
  
  // Mevcut sayfada gösterilecek parçaları hesapla
  const indexOfLastPart = currentPage * partsPerPage;
  const indexOfFirstPart = indexOfLastPart - partsPerPage;
  const currentParts = parts.slice(indexOfFirstPart, indexOfLastPart);

  return (
    <div className="parts-box">
      <h2>Parçalar</h2>
      <div className="parts-grid">
        {currentParts.map((part) => (
          // ... existing part rendering code ...
        ))}
      </div>
      
      {/* Sayfalandırma butonları */}
      <div className="pagination">
        <button 
          onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
          disabled={currentPage === 1}
          className="pagination-button"
        >
          Önceki
        </button>
        <span className="page-info">{currentPage} / {totalPages}</span>
        <button 
          onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
          disabled={currentPage === totalPages}
          className="pagination-button"
        >
          Sonraki
        </button>
      </div>
    </div>
  );
}

export default PartsBox; 