<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hata - Dijital Hediye</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="error-container">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h1 class="error-title">Oops!</h1>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <a href="/" class="back-button">
                <i class="fas fa-home"></i> Ana Sayfaya Dön
            </a>
        </div>
    </div>

    <style>
        :root {
            --primary-color: #ff4767;
            --primary-dark: #d13d59;
            --text-color: #333;
            --surface-color: #fff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-container {
            width: 90%;
            max-width: 500px;
            padding: 2rem;
        }

        .error-content {
            background: var(--surface-color);
            padding: 2.5rem 2rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .error-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }

        .error-title {
            font-size: 2rem;
            color: var(--text-color);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .error-message {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .back-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        @media (max-width: 480px) {
            .error-container {
                padding: 1rem;
            }

            .error-content {
                padding: 2rem 1.5rem;
            }

            .error-icon {
                font-size: 3rem;
            }

            .error-title {
                font-size: 1.75rem;
            }

            .error-message {
                font-size: 1rem;
            }
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: #1a1a1a;
            }

            .error-content {
                background: #2d2d2d;
            }

            .error-title {
                color: #fff;
            }

            .error-message {
                color: #bbb;
            }
        }
    </style>
</body>
</html> 