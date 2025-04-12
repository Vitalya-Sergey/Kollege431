<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin access before any output
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
                if ($stmt->execute([$username, $email, $password, $college_name, $description])) {
                    $_SESSION['message'] = 'Колледж успешно добавлен';
                    $_SESSION['message_type'] = 'success';
                    header("Location: index.php?page=admin");
                    exit();
                } else {
                    $error = 'Произошла ошибка при добавлении колледжа';
                }
            }
        } catch (PDOException $e) {
            $error = 'Произошла ошибка при добавлении колледжа';
        }
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Добавление колледжа</h1>
                <a href="index.php?page=admin" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Назад
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
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

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Добавить колледж
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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