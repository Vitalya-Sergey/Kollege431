<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit();
}

// Get college ID from URL
$college_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get college data
$stmt = $db->prepare("SELECT * FROM colleges WHERE id = ?");
$stmt->execute([$college_id]);
$college = $stmt->fetch();

// Check if college exists and user has permission to edit
if (!$college || $college['user_id'] != $_SESSION['user_id']) {
    header("Location: index.php?page=colleges");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $website = trim($_POST['website']);
    
    // Handle logo upload
    $logo = $college['logo']; // Keep existing logo by default
    if (isset($_FILES['logo']) && $_FILES['logo']['size'] > 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($_FILES['logo']['type'], $allowed_types) && $_FILES['logo']['size'] <= $max_size) {
            $upload_dir = 'uploads/colleges/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('college_') . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo = $upload_path;
            }
        }
    }
    
    // Update college data
    $stmt = $db->prepare("
        UPDATE colleges 
        SET name = ?, description = ?, address = ?, 
            phone = ?, email = ?, website = ?, logo = ?
        WHERE id = ? AND user_id = ?
    ");
    
    try {
        $stmt->execute([
            $name, $description, $address,
            $phone, $email, $website, $logo,
            $college_id, $_SESSION['user_id']
        ]);
        $success_message = "Профиль колледжа успешно обновлен!";
        
        // Refresh college data
        $stmt = $db->prepare("SELECT * FROM colleges WHERE id = ?");
        $stmt->execute([$college_id]);
        $college = $stmt->fetch();
    } catch (PDOException $e) {
        $error_message = "Ошибка при обновлении профиля колледжа.";
    }
}
?>

<div class="content-wrapper">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h4 mb-0">Редактирование профиля колледжа</h1>
                    </div>
                    
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" id="college-form" class="needs-validation" novalidate>
                            <div class="text-center mb-4">
                                <div class="college-logo-container">
                                    <img src="<?php echo $college['logo'] ?: 'assets/images/default-college.png'; ?>" 
                                         alt="College Logo" 
                                         class="rounded-circle img-thumbnail"
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                    <div class="logo-overlay">
                                        <label for="logo" class="btn btn-sm btn-light rounded-circle">
                                            <i class="fas fa-camera"></i>
                                        </label>
                                    </div>
                                </div>
                                <input type="file" id="logo" name="logo" class="d-none" accept="image/*">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Название колледжа</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo htmlspecialchars($college['name']); ?>" required>
                                <div class="invalid-feedback">
                                    Пожалуйста, введите название колледжа
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Описание</label>
                                <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($college['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Адрес</label>
                                <input type="text" class="form-control" name="address" 
                                       value="<?php echo htmlspecialchars($college['address'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Телефон</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($college['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($college['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Веб-сайт</label>
                                <input type="url" class="form-control" name="website" 
                                       value="<?php echo htmlspecialchars($college['website'] ?? ''); ?>">
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php?page=college&id=<?php echo $college_id; ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Назад
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Сохранить изменения
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.content-wrapper {
    background-color: #f8f9fa;
    min-height: calc(100vh - 60px);
}

.card {
    border: none;
    border-radius: 1rem;
}

.card-header {
    border-radius: 1rem 1rem 0 0 !important;
    padding: 1rem 1.5rem;
}

.college-logo-container {
    position: relative;
    display: inline-block;
}

.logo-overlay {
    position: absolute;
    bottom: 0;
    right: 0;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.college-logo-container:hover .logo-overlay {
    opacity: 1;
}

.form-control {
    border-radius: 0.5rem;
    border: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
    transition: all 0.2s ease;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.alert {
    border-radius: 0.5rem;
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle logo preview
    const logoInput = document.getElementById('logo');
    const logoImage = document.querySelector('.college-logo-container img');
    
    logoInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                logoImage.src = e.target.result;
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // Form validation
    const form = document.getElementById('college-form');
    form.addEventListener('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script> 