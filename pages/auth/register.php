<?php
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $college_name = $_POST['college_name'];
    $description = $_POST['description'];
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $error = "Пользователь с таким email уже существует";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO users (username, email, password, college_name, description, role) VALUES (?, ?, ?, ?, ?, 'college')");
        if ($stmt->execute([$username, $email, $hashed_password, $college_name, $description])) {
            header("Location: index.php?page=login");
            exit();
        } else {
            $error = "Ошибка при регистрации. Попробуйте позже.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Регистрация учебного заведения</h3>
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Имя пользователя</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Пароль</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="college_name" class="form-label">Название учебного заведения</label>
                        <input type="text" class="form-control" id="college_name" name="college_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Описание</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <a href="index.php?page=login">Уже есть аккаунт? Войти</a>
                </div>
            </div>
        </div>
    </div>
</div> 