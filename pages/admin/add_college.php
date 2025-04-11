<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $college_name = trim($_POST['college_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($college_name) || empty($username) || empty($email) || empty($password)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Пользователь с таким логином или email уже существует';
            } else {
                // Create new college
                $stmt = $db->prepare("INSERT INTO users (username, email, password, college_name, description, role) VALUES (?, ?, ?, ?, ?, 'college')");
                $stmt->execute([$username, $email, $password, $college_name, $description]);
                $success = 'Колледж успешно добавлен';
                
                // Redirect to admin dashboard
                header("Location: index.php?page=admin");
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Произошла ошибка при добавлении колледжа';
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-none d-md-block bg-light sidebar py-3">
            <div class="sidebar-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin#colleges">
                            <i class="fas fa-university me-2"></i>
                            Колледжи
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin#videos">
                            <i class="fas fa-video me-2"></i>
                            Видео
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1>Добавление колледжа</h1>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                            <?php endif; ?>

                            <form method="POST" action="index.php?page=admin&action=add_college">
                                <div class="mb-3">
                                    <label for="college_name" class="form-label">Название колледжа</label>
                                    <input type="text" class="form-control" id="college_name" name="college_name" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Описание</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">Логин</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Пароль</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Добавить колледж
                                    </button>
                                    <a href="index.php?page=admin" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Назад
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar-sticky {
    position: relative;
    top: 0;
    height: calc(100vh - 48px);
    padding-top: .5rem;
    overflow-x: hidden;
    overflow-y: auto;
}

.nav-link {
    font-weight: 500;
    color: #333;
}

.nav-link:hover {
    color: #007bff;
}

main {
    margin-top: 48px;
}

@media (max-width: 767.98px) {
    .sidebar {
        position: static;
        padding-top: 0;
    }
    main {
        margin-top: 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
});
</script> 