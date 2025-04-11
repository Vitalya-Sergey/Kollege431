<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Handle actions
if (isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'delete_college':
            if (isset($_GET['id'])) {
                $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'college'");
                $stmt->execute([(int)$_GET['id']]);
            }
            break;
        case 'delete_video':
            if (isset($_GET['id'])) {
                $stmt = $db->prepare("DELETE FROM videos WHERE id = ?");
                $stmt->execute([(int)$_GET['id']]);
            }
            break;
    }
}

// Get all colleges
$stmt = $db->query("SELECT * FROM users WHERE role = 'college' ORDER BY created_at DESC");
$colleges = $stmt->fetchAll();

// Get all videos
$stmt = $db->query("SELECT v.*, u.college_name 
                    FROM videos v 
                    JOIN users u ON v.college_id = u.id 
                    ORDER BY v.created_at DESC");
$videos = $stmt->fetchAll();
?>

<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar" class="bg-light collapsed">
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#colleges">
                        <i class="fas fa-university me-2"></i>
                        Колледжи
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#videos">
                        <i class="fas fa-video me-2"></i>
                        Видео
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white py-3 shadow-sm">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-light">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="h3 mb-0 ms-3">Панель администратора</h1>
            </div>
        </nav>

        <!-- Main content -->
        <div class="container-fluid py-4">
            <!-- Colleges Section -->
            <section id="colleges" class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Колледжи</h2>
                    <a href="index.php?page=admin&action=add_college" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Добавить колледж
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Название колледжа</th>
                                        <th>Логин</th>
                                        <th>Email</th>
                                        <th>Дата создания</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($colleges as $college): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($college['college_name']); ?></td>
                                            <td><?php echo htmlspecialchars($college['username']); ?></td>
                                            <td><?php echo htmlspecialchars($college['email']); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($college['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="index.php?page=college&id=<?php echo $college['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="index.php?page=admin&action=delete_college&id=<?php echo $college['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Вы уверены, что хотите удалить этот колледж?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Videos Section -->
            <section id="videos">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Видео</h2>
                    <a href="index.php?page=admin&action=add_video" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Добавить видео
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th>Колледж</th>
                                        <th>Дата</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($videos as $video): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($video['title']); ?></td>
                                            <td><?php echo htmlspecialchars($video['college_name']); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($video['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="index.php?page=video&id=<?php echo $video['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="index.php?page=admin&action=edit_video&id=<?php echo $video['id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="index.php?page=admin&action=delete_video&id=<?php echo $video['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Вы уверены, что хотите удалить это видео?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<style>
.wrapper {
    display: flex;
    width: 100%;
    align-items: stretch;
    position: relative;
}

#sidebar {
    min-width: 250px;
    max-width: 250px;
    min-height: 100vh;
    position: fixed;
    top: 0;
    left: -250px;
    z-index: 1000;
    transition: all 0.3s;
    box-shadow: 3px 0 10px rgba(0,0,0,0.1);
}

#sidebar.active {
    left: 0;
}

#content {
    width: 100%;
    min-height: 100vh;
    transition: all 0.3s;
    margin-left: 0;
}

#content.sidebar-active {
    margin-left: 250px;
}

.sidebar-sticky {
    position: sticky;
    top: 0;
    height: 100vh;
    padding-top: 1rem;
    overflow-x: hidden;
    overflow-y: auto;
}

.nav-link {
    font-weight: 500;
    color: #333;
    padding: 0.5rem 1rem;
}

.nav-link:hover {
    color: #007bff;
}

.nav-link.active {
    color: #007bff;
}

.btn-group {
    gap: 5px;
}

@media (max-width: 768px) {
    #sidebar {
        left: -250px;
    }
    #sidebar.active {
        left: 0;
    }
    #content.sidebar-active {
        margin-left: 0;
    }
}

#sidebarCollapse {
    width: 40px;
    height: 40px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1001;
}

.table td {
    vertical-align: middle;
}

/* Overlay when sidebar is active on mobile */
.sidebar-overlay {
    display: none;
    position: fixed;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    transition: all 0.5s ease-in-out;
}

.sidebar-overlay.active {
    display: block;
    opacity: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    let overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    // Toggle sidebar
    sidebarCollapse.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        content.classList.toggle('sidebar-active');
        overlay.classList.toggle('active');
    });

    // Close sidebar when clicking overlay
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        content.classList.remove('sidebar-active');
        overlay.classList.remove('active');
    });

    // Smooth scroll for sidebar links
    document.querySelectorAll('.sidebar a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
            // Close sidebar on mobile after clicking a link
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('active');
                content.classList.remove('sidebar-active');
                overlay.classList.remove('active');
            }
        });
    });

    // Update active link on scroll
    window.addEventListener('scroll', function() {
        let current = '';
        document.querySelectorAll('section').forEach(section => {
            const sectionTop = section.offsetTop;
            if (pageYOffset >= sectionTop - 60) {
                current = section.getAttribute('id');
            }
        });

        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').substring(1) === current) {
                link.classList.add('active');
            }
        });
    });

    // Close sidebar when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            content.classList.remove('sidebar-active');
            overlay.classList.remove('active');
        }
    });
});
</script> 