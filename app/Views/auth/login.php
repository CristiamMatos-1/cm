<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ITSM & Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Configuração de cores personalizadas do Tailwind para azul corporativo -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        corpBlue: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .form-section {
            transition: all 0.3s ease-in-out;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-md bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Header / Logo -->
        <div class="bg-corpBlue-700 text-white p-6 text-center">
            <h1 class="text-2xl font-bold tracking-wider">ITSM Helpdesk</h1>
            <p class="text-corpBlue-100 text-sm mt-1">Gestão de Serviços e Assistência Técnica</p>
        </div>

        <div class="p-6 sm:p-8">
            <!-- Mensagem de Erro -->
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Tabs: Login / Cadastro -->
            <div class="flex border-b border-gray-200 mb-6">
                <button id="tab-login" class="flex-1 pb-3 text-center text-corpBlue-600 border-b-2 border-corpBlue-600 font-medium transition-colors" onclick="switchTab('login')">Entrar</button>
                <button id="tab-register" class="flex-1 pb-3 text-center text-gray-500 hover:text-corpBlue-600 font-medium transition-colors" onclick="switchTab('register')">Cadastrar</button>
            </div>

            <!-- Login Form -->
            <form id="form-login" action="<?= BASE_URL ?>/auth/login" method="POST" class="form-section">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                
                <div class="mb-4">
                    <label for="login_cpf_cnpj" class="block text-sm font-medium text-gray-700 mb-1">CPF ou CNPJ</label>
                    <input type="text" id="login_cpf_cnpj" name="cpf_cnpj" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-corpBlue-500 focus:border-corpBlue-500 outline-none transition-shadow"
                        placeholder="Digite seu CPF ou CNPJ" oninput="maskCpfCnpj(this)">
                </div>
                
                <div class="mb-6">
                    <label for="login_senha" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input type="password" id="login_senha" name="senha" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-corpBlue-500 focus:border-corpBlue-500 outline-none transition-shadow"
                        placeholder="Sua senha">
                </div>

                <button type="submit" class="w-full bg-corpBlue-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-corpBlue-700 focus:ring-4 focus:ring-corpBlue-100 transition-all">
                    Acessar o Sistema
                </button>
            </form>

            <!-- Register Form -->
            <form id="form-register" action="<?= BASE_URL ?>/auth/register" method="POST" class="form-section hidden">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

                <div class="mb-4">
                    <label for="reg_cpf_cnpj" class="block text-sm font-medium text-gray-700 mb-1">CPF ou CNPJ *</label>
                    <input type="text" id="reg_cpf_cnpj" name="reg_cpf_cnpj" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-corpBlue-500 focus:border-corpBlue-500 outline-none transition-shadow"
                        placeholder="Apenas números" oninput="maskCpfCnpj(this)">
                </div>

                <div class="mb-4">
                    <label for="reg_nome" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo / Razão Social *</label>
                    <input type="text" id="reg_nome" name="reg_nome" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-corpBlue-500 focus:border-corpBlue-500 outline-none transition-shadow"
                        placeholder="Como deseja ser chamado">
                </div>

                <div class="mb-4">
                    <label for="reg_email" class="block text-sm font-medium text-gray-700 mb-1">E-mail *</label>
                    <input type="email" id="reg_email" name="reg_email" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-corpBlue-500 focus:border-corpBlue-500 outline-none transition-shadow"
                        placeholder="seu@email.com">
                </div>

                <div class="mb-4">
                    <label for="reg_telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone / WhatsApp</label>
                    <input type="tel" id="reg_telefone" name="reg_telefone"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-corpBlue-500 focus:border-corpBlue-500 outline-none transition-shadow"
                        placeholder="(00) 00000-0000" oninput="maskPhone(this)">
                </div>

                <div class="mb-6">
                    <label for="reg_senha" class="block text-sm font-medium text-gray-700 mb-1">Senha *</label>
                    <input type="password" id="reg_senha" name="reg_senha" required minlength="6"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-corpBlue-500 focus:border-corpBlue-500 outline-none transition-shadow"
                        placeholder="Mínimo 6 caracteres">
                </div>

                <button type="submit" class="w-full bg-gray-800 text-white font-semibold py-2 px-4 rounded-lg hover:bg-gray-900 focus:ring-4 focus:ring-gray-200 transition-all">
                    Criar Minha Conta
                </button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const loginForm = document.getElementById('form-login');
            const registerForm = document.getElementById('form-register');
            const loginTab = document.getElementById('tab-login');
            const registerTab = document.getElementById('tab-register');

            if (tab === 'login') {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                
                loginTab.classList.add('text-corpBlue-600', 'border-b-2', 'border-corpBlue-600');
                loginTab.classList.remove('text-gray-500');
                
                registerTab.classList.remove('text-corpBlue-600', 'border-b-2', 'border-corpBlue-600');
                registerTab.classList.add('text-gray-500');
            } else {
                registerForm.classList.remove('hidden');
                loginForm.classList.add('hidden');
                
                registerTab.classList.add('text-corpBlue-600', 'border-b-2', 'border-corpBlue-600');
                registerTab.classList.remove('text-gray-500');
                
                loginTab.classList.remove('text-corpBlue-600', 'border-b-2', 'border-corpBlue-600');
                loginTab.classList.add('text-gray-500');
            }
        }

        function maskCpfCnpj(input) {
            let v = input.value.replace(/\D/g, "");
            if (v.length <= 11) {
                // CPF
                v = v.replace(/(\d{3})(\d)/, "$1.$2");
                v = v.replace(/(\d{3})(\d)/, "$1.$2");
                v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
            } else {
                // CNPJ
                v = v.replace(/^(\d{2})(\d)/, "$1.$2");
                v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
                v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");
                v = v.replace(/(\d{4})(\d)/, "$1-$2");
            }
            input.value = v.substring(0, 18);
        }

        function maskPhone(input) {
            let v = input.value.replace(/\D/g, "");
            v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
            v = v.replace(/(\d)(\d{4})$/, "$1-$2");
            input.value = v.substring(0, 15);
        }
    </script>
</body>
</html>