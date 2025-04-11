<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$college_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get college data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'college'");
$stmt->execute([$college_id]);
$college = $stmt->fetch();

if (!$college) {
    header("Location: index.php?page=admin");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $college_name = trim($_POST['college_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');

    if (empty($college_name) || empty($username) || empty($email)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } else {
        try {
            // Check if username or email already exists (excluding current college)
            $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $college_id]);
            if ($stmt->fetch()) {
                $error = 'Пользователь с таким логином или email уже существует';
            } else {
                // Update college data
                if (!empty($new_password)) {
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, password = ?, college_name = ?, description = ?
                        WHERE id = ? AND role = 'college'
                    ");
                    $stmt->execute([$username, $email, $new_password, $college_name, $description, $college_id]);
                } else {
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, college_name = ?, description = ?
                        WHERE id = ? AND role = 'college'
                    ");
                    $stmt->execute([$username, $email, $college_name, $description, $college_id]);
                }
                
                $success = 'Информация о колледже успешно обновлена';
                
                // Refresh college data
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'college'");
                $stmt->execute([$college_id]);
                $college = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = 'Произошла ошибка при обновлении информации';
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
                <h1>Редактирование колледжа</h1>
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

                            <form method="POST">
                                <div class="mb-3">
                                    <label for="college_name" class="form-label">Название колледжа</label>
                                    <input type="text" class="form-control" id="college_name" name="college_name" 
                                           value="<?php echo htmlspecialchars($college['college_name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Описание</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"
                                    ><?php echo htmlspecialchars($college['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">Логин</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($college['username']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($college['email']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Новый пароль</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new_password" name="new_password"
                                               placeholder="Оставьте пустым, чтобы не менять">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Оставьте поле пустым, если не хотите менять пароль</div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="index.php?page=college&id=<?php echo $college_id; ?>" class="btn btn-secondary">
                                        <i class="fas fa-eye me-2"></i>Просмотр
                                    </a>
                                    <div class="btn-group">
                                        <a href="index.php?page=admin" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Назад
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Сохранить
                                        </button>
                                    </div>
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
    const password = document.querySelector('#new_password');

    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
});
</script> 