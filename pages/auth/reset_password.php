<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Пожалуйста, введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Пожалуйста, введите корректный email';
    } else {
        try {
            // Проверяем существование пользователя
            $stmt = $db->prepare("SELECT id, college_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Генерируем токен для сброса пароля
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Сохраняем токен в базе данных
                $stmt = $db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $token, $expires]);

                // Формируем ссылку для сброса пароля
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?page=new_password&token=" . $token;

                // Отправляем email
                $to = $email;
                $subject = "Восстановление пароля - Коллеги";
                $message = "Здравствуйте, " . htmlspecialchars($user['college_name']) . "!\n\n";
                $message .= "Вы запросили восстановление пароля. Для создания нового пароля перейдите по ссылке:\n";
                $message .= $reset_link . "\n\n";
                $message .= "Ссылка действительна в течение 1 часа.\n";
                $message .= "Если вы не запрашивали восстановление пароля, проигнорируйте это письмо.\n\n";
                $message .= "С уважением,\nКоманда Коллеги";

                $headers = "From: noreply@kollege.ru\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                if (mail($to, $subject, $message, $headers)) {
                    $success = 'Инструкции по восстановлению пароля отправлены на ваш email';
                } else {
                    $error = 'Ошибка при отправке email. Пожалуйста, попробуйте позже';
                }
            } else {
                // Для безопасности показываем то же сообщение, что и при успехе
                $success = 'Если указанный email зарегистрирован в системе, инструкции по восстановлению пароля будут отправлены';
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
                    <h2 class="card-title text-center mb-4">Восстановление пароля</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?page=reset_password">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Отправить инструкции
                            </button>
                            <a href="index.php?page=login" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Вернуться к входу
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> 