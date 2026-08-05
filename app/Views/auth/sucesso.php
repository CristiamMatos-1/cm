<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?></title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md mx-auto px-4">
        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-8 text-center">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-white text-5xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white"><?= htmlspecialchars($titulo) ?></h1>
            </div>

            <!-- Message -->
            <div class="p-8 text-center">
                <p class="text-gray-700 text-lg mb-6"><?= htmlspecialchars($mensagem) ?></p>
                
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded text-left mb-6">
                    <p class="text-sm text-green-800">
                        ✓ Sua resposta foi registrada com sucesso.
                    </p>
                </div>

                <a href="<?= BASE_URL ?>/auth" class="inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i> Voltar
                </a>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>
