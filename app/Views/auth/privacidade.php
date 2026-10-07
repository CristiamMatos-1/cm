<?php
use app\Helpers\Security;

$razao = trim((string)($empresa['razao_social'] ?? ''));
$cnpj = trim((string)($empresa['cnpj'] ?? ''));
$encarregado = trim((string)($empresa['encarregado_nome'] ?? ''));
$emailPrivacidade = trim((string)($empresa['email_privacidade'] ?? ''));
$controlador = $razao !== '' ? $razao : 'a empresa responsável pelo sistema';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade (LGPD)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { corpBlue: { 50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' } } } } };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 text-gray-800">
    <main class="max-w-3xl mx-auto px-4 py-8 sm:py-12">
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <header class="bg-gradient-to-r from-corpBlue-800 to-indigo-600 px-6 py-8">
                <p class="text-blue-100 text-xs uppercase tracking-wide"><i class="fas fa-shield-alt mr-1"></i> Lei Geral de Proteção de Dados (Lei nº 13.709/2018)</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-white mt-1">Política de Privacidade</h1>
                <p class="text-blue-100 text-sm mt-2">Versão <?= Security::esc($versao) ?></p>
            </header>

            <article class="p-6 sm:p-8 space-y-8 text-sm leading-relaxed">

                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-2">1. Quem é o responsável pelos seus dados</h2>
                    <p>O controlador dos dados pessoais tratados neste sistema é <strong><?= Security::esc($controlador) ?></strong><?= $cnpj !== '' ? ', CNPJ ' . Security::esc($cnpj) : '' ?>.</p>
                    <p class="mt-2">
                        Encarregado pelo tratamento de dados (art. 41 da LGPD):
                        <strong><?= $encarregado !== '' ? Security::esc($encarregado) : 'canal de atendimento da empresa' ?></strong>.
                        Contato para assuntos de privacidade:
                        <?php if ($emailPrivacidade !== ''): ?>
                            <a class="text-corpBlue-600 underline" href="mailto:<?= Security::esc($emailPrivacidade) ?>"><?= Security::esc($emailPrivacidade) ?></a>
                        <?php else: ?>
                            os canais de atendimento informados no seu contrato ou a área <em>Privacidade (LGPD)</em> do sistema.
                        <?php endif; ?>
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-2">2. Quais dados coletamos</h2>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>Cadastro:</strong> nome ou razão social, CPF/CNPJ, e-mail, telefone/WhatsApp, nome do responsável e endereço (quando informado).</li>
                        <li><strong>Chamados de suporte:</strong> descrição do problema, tipo de atendimento, fotos e vídeos que você anexar e o relatório técnico do atendimento.</li>
                        <li><strong>Orçamentos:</strong> escopo, valores, sua decisão (aprovar ou rejeitar), a justificativa que você informar, data e hora, <strong>endereço IP e navegador</strong> usados na resposta.</li>
                        <li><strong>Contratos e notas fiscais:</strong> valores, datas, condições e os arquivos das notas fiscais.</li>
                        <li><strong>Patrimônio:</strong> equipamentos vinculados a você (nome, número de série, datas de compra e garantia).</li>
                        <li><strong>Acesso e segurança:</strong> sua senha (guardada apenas de forma criptografada, em hash), cookie de sessão, endereço IP e registros de auditoria de ações sensíveis.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-2">3. Para que usamos e em qual base legal (art. 7º)</h2>
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full text-xs sm:text-sm">
                            <thead class="bg-gray-50 text-left text-gray-600">
                                <tr><th class="px-3 py-2">Finalidade</th><th class="px-3 py-2">Base legal</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr><td class="px-3 py-2">Prestar os serviços contratados, abrir e atender chamados, elaborar e enviar orçamentos</td><td class="px-3 py-2">Execução de contrato e procedimentos preliminares (inciso V)</td></tr>
                                <tr><td class="px-3 py-2">Emitir e guardar notas fiscais, contratos e registros contábeis</td><td class="px-3 py-2">Cumprimento de obrigação legal ou regulatória (inciso II)</td></tr>
                                <tr><td class="px-3 py-2">Manter o histórico das decisões sobre orçamentos (quem, quando, de onde) como prova</td><td class="px-3 py-2">Exercício regular de direitos em processo judicial, administrativo ou arbitral (inciso VI)</td></tr>
                                <tr><td class="px-3 py-2">Proteger o sistema, prevenir fraudes e investigar acessos indevidos (IP e logs)</td><td class="px-3 py-2">Legítimo interesse (inciso IX) e segurança (art. 46)</td></tr>
                                <tr><td class="px-3 py-2">Criar sua conta de acesso pelo autocadastro</td><td class="px-3 py-2">Consentimento (inciso I), registrado com data, versão desta política e IP</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2">Não vendemos seus dados e não os usamos para publicidade ou perfilamento.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-2">4. Com quem os dados podem ser compartilhados</h2>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>Hospedagem do sistema</strong> (servidor/cPanel) e <strong>serviço de e-mail</strong>, usados para operar o sistema e enviar mensagens como o link do orçamento.</li>
                        <li><strong>WhatsApp:</strong> quando a equipe compartilha o link de um orçamento, a mensagem trafega pela plataforma do WhatsApp, sujeita aos termos dela.</li>
                        <li><strong>Google Gemini (inteligência artificial):</strong> o texto da descrição de um chamado pode ser enviado a esse serviço, apenas por técnicos autorizados, para apoiar o diagnóstico. Isso pode implicar transferência internacional de dados (art. 33). Nossa equipe é orientada a não incluir dados pessoais nesses textos.</li>
                        <li><strong>ViaCEP:</strong> ao digitar um CEP em formulários de endereço, o número é consultado nesse serviço público.</li>
                        <li><strong>Bibliotecas de interface</strong> (Tailwind, Font Awesome, Chart.js) carregadas de redes de distribuição de conteúdo (CDN); esses provedores recebem o endereço IP de quem acessa a página.</li>
                        <li><strong>Autoridades</strong>, quando houver obrigação legal ou ordem judicial.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-2">5. Aprovação e rejeição de orçamentos</h2>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Ao aprovar ou rejeitar um orçamento, registramos a decisão, a data e hora, o endereço IP, o navegador e a justificativa (se houver).</li>
                        <li>Depois de decidido, o link do orçamento apenas <strong>exibe o resultado</strong> (aprovado ou rejeitado) e não aceita nova resposta.</li>
                        <li>Somente um <strong>administrador</strong> pode reabrir o orçamento para uma nova resposta. A decisão anterior continua registrada no histórico, mesmo após a reabertura.</li>
                        <li>Orçamentos já decididos não são excluídos; para um novo valor ou escopo, criamos uma nova versão do orçamento.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-2">6. Por quanto tempo guardamos</h2>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>Notas fiscais, contratos e registros contábeis:</strong> pelo prazo exigido pela legislação fiscal e contratual (em regra, 5 anos).</li>
                        <li><strong>Histórico de decisões de orçamentos:</strong> pelo prazo de prescrição aplicável à relação contratual.</li>
                        <li><strong>Cadastro e chamados:</strong> enquanto durar a relação com você e pelo prazo necessário às finalidades acima.</li>
                        <li>Encerrados os prazos, os dados são eliminados ou anonimizados (art. 16).</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-2">7. Seus direitos (art. 18)</h2>
                    <p>Você pode, a qualquer momento: confirmar se tratamos seus dados; acessá-los; corrigi-los; pedir anonimização, bloqueio ou eliminação de dados desnecessários; solicitar portabilidade; saber com quem compartilhamos; ser informado sobre a possibilidade de não consentir e revogar o consentimento.</p>
                    <p class="mt-2">
                        <strong>Como exercer:</strong> acesse <em>Privacidade (LGPD)</em> no menu da sua conta para baixar seus dados e abrir uma solicitação
                        <?= $emailPrivacidade !== '' ? ' ou escreva para <a class="text-corpBlue-600 underline" href="mailto:' . Security::esc($emailPrivacidade) . '">' . Security::esc($emailPrivacidade) . '</a>' : '' ?>.
                        Respondemos em até 15 dias.
                    </p>
                    <p class="mt-2">Dados que a lei nos obriga a manter (por exemplo, fiscais) não são apagados, mas ficam sem identificação pessoal quando você pede a anonimização e o prazo legal permite. Você também pode reclamar à Autoridade Nacional de Proteção de Dados (ANPD).</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-2">8. Como protegemos seus dados</h2>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Senhas armazenadas com hash; nunca em texto legível.</li>
                        <li>Controle de acesso por perfil (cliente, técnico e administrador): você só vê os seus próprios registros.</li>
                        <li>Notas fiscais e anexos de chamados só são entregues a quem tem permissão; não há link público para esses arquivos.</li>
                        <li>Links de orçamento usam um código aleatório e longo, com acesso restrito ao que o orçamento exibe (nome parcial, itens e valores).</li>
                        <li>Proteção contra falsificação de requisições (CSRF), cookies de sessão protegidos e registro de auditoria de ações sensíveis.</li>
                        <li>Em caso de incidente de segurança com risco relevante, comunicaremos você e a ANPD (art. 48).</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-2">9. Cookies</h2>
                    <p>Usamos apenas o cookie de sessão, essencial para manter você conectado com segurança. Não usamos cookies de publicidade ou de rastreamento.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-2">10. Alterações desta política</h2>
                    <p>Podemos atualizar este documento para refletir mudanças legais ou do sistema. A versão vigente e sua data aparecem no topo desta página.</p>
                </section>
            </article>

            <footer class="bg-gray-50 px-6 py-4 text-center text-xs text-gray-500">
                <a href="<?= BASE_URL ?>/<?= !empty($logado) ? '' : 'auth' ?>" class="text-corpBlue-600 hover:underline"><i class="fas fa-arrow-left mr-1"></i> Voltar ao sistema</a>
            </footer>
        </div>
    </main>
</body>
</html>
