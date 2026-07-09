<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Dashboard' ?> - ITSM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        corpBlue: {
                            50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6',
                            600: '#2563eb', 700: '#1d4ed8', 900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php require_once 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Navbar -->
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 z-10">
            <button id="mobile-menu-btn" class="md:hidden text-gray-500 hover:text-corpBlue-600 focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="text-xl font-semibold text-gray-800 ml-4 md:ml-0">
                <?= $title ?? 'Painel' ?>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600 hidden sm:block">Olá, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário') ?></span>
                <a href="<?= BASE_URL ?>/auth/logout" class="text-red-500 hover:text-red-700 font-medium text-sm transition-colors">
                    <i class="fas fa-sign-out-alt mr-1"></i> Sair
                </a>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
