<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        background-color: #f8f9fa;
    }
    
    .navbar {
        background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
        padding: 1rem;
    }
    
    .navbar-brand {
        color: white !important;
        font-weight: bold;
        font-size: 1.5rem;
    }
    
    .nav-link {
        color: rgba(255,255,255,0.9) !important;
        transition: color 0.2s;
    }
    
    .nav-link:hover {
        color: white !important;
    }
    
    .nav-link.active {
        color: white !important;
        font-weight: bold;
    }
    
    .content {
        flex: 1;
        padding: 2rem 0;
    }
    
    .footer {
        margin-top: auto;
        background-color: #343a40;
        color: white;
        padding: 2rem 0;
    }
    
    .btn-outline-light:hover {
        background-color: rgba(255,255,255,0.1);
    }
    
    @media (max-width: 768px) {
        .navbar-nav {
            background-color: rgba(0,0,0,0.1);
            border-radius: 0.5rem;
            padding: 0.5rem;
        }
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'home' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-home me-1"></i>
                            Главная
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'colleges' ? 'active' : ''; ?>" href="index.php?page=colleges">
                            <i class="fas fa-university me-1"></i>
                            Колледжи
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'videos' ? 'active' : ''; ?>" href="index.php?page=videos">
                            <i class="fas fa-video me-1"></i>
                            Видео
                        </a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['college_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                    <a class="dropdown-item" href="index.php?page=admin">
                                        <i class="fas fa-cog me-1"></i>
                                        Панель администратора
                                    </a>
                                    <?php else: ?>
                                    <a class="dropdown-item" href="index.php?page=college&id=<?php echo $_SESSION['user_id']; ?>">
                                        <i class="fas fa-cog me-1"></i>
                                        Профиль
                                    </a>
                                    <?php endif; ?>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="index.php?page=logout">
                                        <i class="fas fa-sign-out-alt me-1"></i>
                                        Выйти
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link" href="index.php?page=login">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            Войти
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="content">
        <div class="container">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 