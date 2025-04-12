<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

// Проверяем наличие токена
if (!isset($_GET['token'])) {
    header("Location: index.php?page=login");
    exit();
}

$token = $_GET['token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Пожалуйста, заполните все поля';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        try {
            // Проверяем токен и его срок действия
            $stmt = $db->prepare("
                SELECT pr.user_id, u.email 
                FROM password_resets pr
                JOIN users u ON pr.user_id = u.id
                WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0
            ");
            $stmt->execute([$token]);
            $reset = $stmt->fetch();

            if ($reset) {
                // Обновляем пароль
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$password, $reset['user_id']]);

                // Помечаем токен как использованный
                $stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                $stmt->execute([$token]);

                $_SESSION['message'] = 'Пароль успешно изменен. Теперь вы можете войти с новым паролем.';
                $_SESSION['message_type'] = 'success';
                header("Location: index.php?page=login");
                exit();
            } else {
                $error = 'Недействительная или устаревшая ссылка для сброса пароля';
            }
        } catch (Exception $e) {
            $error = 'Произошла ошибка. Пожалуйста, попробуйте позже';
        }
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Создание нового пароля</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="password" class="form-label">Новый пароль</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Подтвердите пароль</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key me-2"></i>Сохранить новый пароль
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
    // Функция для переключения видимости пароля
    function togglePasswordVisibility(inputId, buttonId) {
        const input = document.getElementById(inputId);
        const button = document.getElementById(buttonId);
        
        button.addEventListener('click', function() {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            button.querySelector('i').classList.toggle('fa-eye');
            button.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }

    // Применяем функцию к обоим полям пароля
    togglePasswordVisibility('password', 'togglePassword');
    togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');
});
</script> 