<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erişim Engellendi - Puzzle</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #e74c3c;
            --background-color: #f5f6fa;
            --text-color: #2c3e50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .access-denied-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .access-denied-box {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .icon-container {
            margin-bottom: 20px;
        }

        .lock-icon {
            font-size: 64px;
            color: var(--secondary-color);
        }

        h1 {
            color: var(--secondary-color);
            margin-bottom: 15px;
            font-size: 28px;
        }

        .message {
            color: var(--text-color);
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 480px) {
            .access-denied-box {
                padding: 30px 20px;
            }

            h1 {
                font-size: 24px;
            }

            .lock-icon {
                font-size: 48px;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="access-denied-container">
        <div class="access-denied-box">
            <div class="icon-container">
                <i class="fas fa-lock lock-icon"></i>
            </div>
            <h1>Erişim Engellendi</h1>
            <p class="message">
                <?php echo $message ?? 'Bu puzzle\'a erişim yetkiniz bulunmuyor. Puzzle sahibi tarafından özel olarak ayarlanmış.'; ?>
            </p>
            <div class="buttons">
                <a href="/puzzle" class="btn btn-primary">Ana Sayfaya Dön</a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo MAIN_SITE_URL; ?>/login" class="btn btn-secondary">Giriş Yap</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 