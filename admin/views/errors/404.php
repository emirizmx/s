<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Sayfa Bulunamadı</title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="error-page">
    <div class="error-container">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1>404</h1>
            <h2>Sayfa Bulunamadı</h2>
            <p>Aradığınız sayfa bulunamadı veya taşınmış olabilir.</p>
            <div class="error-actions">
                <a href="/admin/dashboard" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    Ana Sayfaya Dön
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Geri Git
                </a>
            </div>
        </div>
    </div>

    <style>
    .error-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
    }

    .error-container {
        max-width: 500px;
        width: 90%;
        padding: 2rem;
        text-align: center;
    }

    .error-content {
        background: white;
        padding: 3rem 2rem;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .error-icon {
        font-size: 4rem;
        color: #dc3545;
        margin-bottom: 1.5rem;
    }

    .error-content h1 {
        font-size: 5rem;
        color: #dc3545;
        margin-bottom: 1rem;
        line-height: 1;
    }

    .error-content h2 {
        font-size: 1.5rem;
        color: #343a40;
        margin-bottom: 1rem;
    }

    .error-content p {
        color: #6c757d;
        margin-bottom: 2rem;
    }

    .error-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    @media (max-width: 480px) {
        .error-actions {
            flex-direction: column;
        }
        
        .error-content {
            padding: 2rem 1rem;
        }
        
        .error-content h1 {
            font-size: 4rem;
        }
    }
    </style>
</body>
</html> 