<?php require_once APP_PATH . '/Views/layout/header.php'; ?>
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Serviços Avulsos</h2>
        <a href="<?= BASE_URL ?>/admin/servicoAvulso" class="bg-corpBlue-600 text-white px-4 py-2 rounded">Novo Serviço</a>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-gray-600">Lista de serviços avulsos carregada pelo controller.</p>
    </div>
</div>
<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
