<?php
$flashMessages = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);
foreach (['success', 'error'] as $legacyType) {
    if (!empty($_SESSION[$legacyType])) {
        $flashMessages[] = ['type' => $legacyType, 'message' => $_SESSION[$legacyType]];
        unset($_SESSION[$legacyType]);
    }
}
$pageTitle = $title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(\app\Helpers\Security::generateCsrfToken()) ?>">
    <title><?= htmlspecialchars($pageTitle) ?> - ITSM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        corpBlue: {
                            50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6',
                            600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden text-gray-800">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-4 md:px-6 z-10 shrink-0">
            <div class="flex items-center min-w-0">
                <button id="mobile-menu-btn" type="button" aria-label="Abrir menu" class="md:hidden text-gray-500 hover:text-corpBlue-600 focus:outline-none mr-3">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="text-lg md:text-xl font-semibold text-gray-800 truncate">
                    <?= htmlspecialchars($pageTitle) ?>
                </div>
            </div>
            <div class="flex items-center space-x-4 shrink-0">
                <span class="text-sm text-gray-600 hidden sm:block">Olá, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário') ?></span>
                <a href="<?= BASE_URL ?>/auth/logout" class="text-red-500 hover:text-red-700 font-medium text-sm transition-colors">
                    <i class="fas fa-sign-out-alt mr-1"></i> Sair
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-6">
        <script>window.__FLASH__ = <?= json_encode($flashMessages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;</script>
