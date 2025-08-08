<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to KITSDB</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, var(--background) 0%, #1a0b22 50%, var(--surface) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Montserrat', sans-serif;
        }
        
        .welcome-container {
            text-align: center;
            max-width: 600px;
            padding: 2rem;
        }
        
        .brand-logo {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 4rem;
            font-weight: 700;
            color: var(--highlight-yellow);
            text-transform: uppercase;
            letter-spacing: 0.2em;
            margin-bottom: 1rem;
            text-shadow: 0 4px 20px rgba(220, 247, 99, 0.3);
        }
        
        .welcome-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--primary-text);
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        
        .welcome-subtitle {
            color: var(--secondary-text);
            font-size: 1.2rem;
            margin-bottom: 3rem;
            line-height: 1.6;
        }
        
        .action-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .welcome-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            min-width: 160px;
            border: 2px solid;
        }
        
        .btn-primary {
            background: var(--action-red);
            color: white;
            border-color: var(--action-red);
            box-shadow: 0 4px 15px rgba(222, 60, 75, 0.3);
        }
        
        .btn-primary:hover {
            background: #c23842;
            border-color: #c23842;
            box-shadow: 0 6px 25px rgba(222, 60, 75, 0.5);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--highlight-yellow);
            border-color: var(--highlight-yellow);
        }
        
        .btn-secondary:hover {
            background: var(--highlight-yellow);
            color: var(--background);
            box-shadow: 0 4px 15px rgba(220, 247, 99, 0.3);
            transform: translateY(-2px);
        }
        
        .kit-preview {
            margin: 2rem auto;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0.7;
        }
        
        .kit-grid-preview {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 2rem 0;
            opacity: 0.6;
        }
        
        .mini-kit {
            width: 60px;
            height: 60px;
            border-radius: 0.375rem;
            background: var(--surface);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .mini-kit:hover {
            transform: scale(1.1);
            opacity: 1;
        }
        
        .features {
            margin: 3rem 0 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .feature {
            color: var(--secondary-text);
            font-size: 0.9rem;
            padding: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .brand-logo {
                font-size: 2.5rem;
            }
            
            .welcome-title {
                font-size: 1.8rem;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .welcome-btn {
                width: 100%;
                max-width: 280px;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="brand-logo">KITSDB</div>
        <h1 class="welcome-title">Welcome to Kits Database</h1>
        <p class="welcome-subtitle">
            Your ultimate collection of football jerseys.<br>
            Discover, organize, and showcase your passion for the beautiful game.
        </p>
        
        
        <div class="action-buttons">
            <a href="login.php" class="welcome-btn btn-primary">
                Login
            </a>
            <a href="kits_browse.php" class="welcome-btn btn-secondary">
                View Kits
            </a>
        </div>
    </div>
</body>
</html>