<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Puzzle Bulunamadı</title>
    <style>
        :root {
            --primary-color: #3498db;
            --warning-color: #f1c40f;
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

        .invalid-puzzle-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .invalid-puzzle-box {
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
            position: relative;
        }

        .puzzle-icon {
            font-size: 64px;
            color: var(--warning-color);
        }

        .question-mark {
            position: absolute;
            top: -10px;
            right: 35%;
            font-size: 32px;
            color: var(--text-color);
            background: white;
            border-radius: 50%;
            padding: 5px;
        }

        h1 {
            color: var(--warning-color);
            margin-bottom: 15px;
            font-size: 28px;
        }

        .message {
            color: var(--text-color);
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .suggestions {
            text-align: left;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .suggestions h2 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .suggestions ul {
            list-style-type: none;
            padding-left: 0;
        }

        .suggestions li {
            margin-bottom: 8px;
            padding-left: 24px;
            position: relative;
        }

        .suggestions li:before {
            content: "•";
            position: absolute;
            left: 8px;
            color: var(--warning-color);
        }

        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 25px;
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

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 480px) {
            .invalid-puzzle-box {
                padding: 30px 20px;
            }

            h1 {
                font-size: 24px;
            }

            .puzzle-icon {
                font-size: 48px;
            }

            .question-mark {
                font-size: 24px;
                right: 38%;
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
    <div class="invalid-puzzle-container">
        <div class="invalid-puzzle-box">
            <div class="icon-container">
                <i class="fas fa-puzzle-piece puzzle-icon"></i>
                <i class="fas fa-question question-mark"></i>
            </div>
            <h1>Puzzle Bulunamadı</h1>
            <p class="message">
                <?php echo $message ?? 'Aradığınız puzzle bulunamadı veya artık mevcut değil.'; ?>
            </p>
            <div class="suggestions">
                <h2>Olası nedenler:</h2>
                <ul>
                    <li>Puzzle bağlantısı yanlış veya eksik olabilir</li>
                    <li>Puzzle silinmiş olabilir</li>
                    <li>Puzzle süresi dolmuş olabilir</li>
                    <li>Bağlantıyı yanlış kopyalamış olabilirsiniz</li>
                </ul>
            </div>
            <div class="buttons">
                <a href="/puzzle" class="btn btn-primary">Puzzle Ana Sayfası</a>
            </div>
        </div>
    </div>
</body>
</html> 