<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'CapivaraLearn - CRUD' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
        .alert {
            border: none;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>CapivaraLearn
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'universities.php' ? 'active' : '' ?>" 
                           href="universities.php">
                            <i class="fas fa-university me-1"></i>Universidades
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : '' ?>" 
                           href="courses.php">
                            <i class="fas fa-book me-1"></i>Cursos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'modules.php' ? 'active' : '' ?>" 
                           href="modules.php">
                            <i class="fas fa-folder me-1"></i>Módulos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'topics.php' ? 'active' : '' ?>" 
                           href="topics.php">
                            <i class="fas fa-file-alt me-1"></i>Tópicos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'enrollments.php' ? 'active' : '' ?>" 
                           href="enrollments.php">
                            <i class="fas fa-user-graduate me-1"></i>Matrículas
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
