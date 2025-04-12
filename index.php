<?php
session_start();
require_once 'config/database.php';

// Basic routing
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Include header
include 'includes/header.php';

// Route handling
switch($page) {
    case 'home':
        include 'pages/home.php';
        break;
    case 'login':
        include 'pages/auth/login.php';
        break;
    case 'logout':
        include 'pages/auth/logout.php';
        break;
    case 'register':
        include 'pages/auth/register.php';
        break;
    case 'admin':
        if ($action === 'add_college') {
            include 'pages/admin/add_college.php';
        } elseif ($action === 'edit_college') {
            include 'pages/admin/edit_college.php';
        } elseif ($action === 'add_video') {
            include 'pages/admin/add_video.php';
        } elseif ($action === 'edit_video') {
            include 'pages/admin/edit_video.php';
        } else {
            include 'pages/admin/dashboard.php';
        }
        break;
    case 'college':
        include 'pages/college/view.php';
        break;
    case 'colleges':
        include 'pages/colleges/list.php';
        break;
    case 'video':
        include 'pages/video/view.php';
        break;
    case 'videos':
        include 'pages/videos/list.php';
        break;
    default:
        include 'pages/404.php';
        break;
}

// Include footer
include 'includes/footer.php';
?> 