<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CapivaraLearn - Gerenciamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">CapivaraLearn</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'manage_universities.php' ? 'active' : ''; ?>" 
                       href="manage_universities.php">Universidades</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'manage_courses.php' ? 'active' : ''; ?>" 
                       href="manage_courses.php">Cursos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'manage_modules.php' ? 'active' : ''; ?>" 
                       href="manage_modules.php">Módulos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'manage_topics.php' ? 'active' : ''; ?>" 
                       href="manage_topics.php">Tópicos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'manage_enrollments.php' ? 'active' : ''; ?>" 
                       href="manage_enrollments.php">Matrículas</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
