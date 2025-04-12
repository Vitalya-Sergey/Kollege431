<?php
require_once __DIR__ . '/../../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        try {
            // Get user by email
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
            $stmt->execute([$email, $password]);
            $user = $stmt->fetch();

            if ($user) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['college_name'] = $user['college_name'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: index.php?page=admin");
                } else {
                    header("Location: index.php?page=college&id=" . $user['id']);
                }
                exit();
            } else {
                $error = 'Неверный email или пароль';
            }
        } catch (PDOException $e) {
            $error = 'Произошла ошибка при входе';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Вход в систему</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Пароль</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Войти
                        </button>
                        <a href="index.php?page=reset_password" class="btn btn-link">
                            Забыли пароль?
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> 