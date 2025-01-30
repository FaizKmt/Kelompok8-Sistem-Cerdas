<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Analisis Emosi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }

        .dashboard-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }

        .dashboard-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 2em;
        }

        .task-title {
            font-size: 1.5em;
            color: #34495e;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .members-list {
            margin-bottom: 30px;
            text-align: left;
        }

        .members-list h5 {
            font-size: 1.2em;
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .members-list ul {
            list-style: none;
            padding: 0;
        }

        .members-list ul li {
            font-size: 1em;
            color: #505c6e;
            margin-bottom: 8px;
        }

        .btn-analysis {
            background-color: #4a90e2;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 1.1em;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-analysis:hover {
            background-color: #357abd;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74,144,226,0.3);
        }

        .sidebar {
            height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content {
            padding: 20px;
        }
        .active {
            background-color: #495057;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <h3 class="text-white text-center mb-4">Menu</h3>
                <a href="?page=dashboard" class="<?php echo (!isset($_GET['page']) || $_GET['page'] == 'dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-home me-2"></i> Dashboard
                </a>
                <a href="?page=analisis" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'analisis') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar me-2"></i> Analisis
                </a>
                <a href="?page=tentang" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'tentang') ? 'active' : ''; ?>">
                    <i class="fas fa-users me-2"></i> Tentang Kami
                </a>
            </div>

            <!-- Content -->
            <div class="col-md-10 content">
                <?php
                $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
                include "pages/$page.php";
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
