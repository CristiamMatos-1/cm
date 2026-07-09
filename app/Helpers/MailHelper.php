<?php
namespace app\Helpers;

use app\Models\ConfigModel;

class MailHelper {
    
    /**
     * Envia um e-mail padrão do sistema utilizando a função mail() nativa do PHP.
     */
    public static function enviarEmail($para_email, $para_nome, $assunto, $mensagem_html) {
        $configModel = new ConfigModel();
        $empresa = $configModel->getCompanyInfo();
        
        $remetente_email = !empty($empresa['email_contato']) ? $empresa['email_contato'] : 'no-reply@seudominio.com.br';
        $remetente_nome = !empty($empresa['razao_social']) ? $empresa['razao_social'] : 'Sistema ITSM';
        
        // Cabeçalhos obrigatórios para e-mail HTML
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        
        // Remetente
        $headers .= "From: " . $remetente_nome . " <" . $remetente_email . ">\r\n";
        $headers .= "Reply-To: " . $remetente_email . "\r\n";
        
        // Estrutura básica do e-mail com a logo da empresa
        $logo = !empty($empresa['logo_url']) ? "<img src='" . BASE_URL . "/uploads/company/" . $empresa['logo_url'] . "' alt='Logo' style='max-height: 80px;'>" : "<h1>" . $remetente_nome . "</h1>";
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .container { background-color: #ffffff; max-width: 600px; margin: 0 auto; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .header { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 20px; }
                .content { color: #333333; line-height: 1.6; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #777777; border-top: 1px solid #eeeeee; padding-top: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    " . $logo . "
                </div>
                <div class='content'>
                    <p>Olá, <strong>" . htmlspecialchars($para_nome) . "</strong>!</p>
                    " . $mensagem_html . "
                </div>
                <div class='footer'>
                    <p>Este é um e-mail automático enviado por <strong>" . $remetente_nome . "</strong>. Por favor, não responda diretamente a este e-mail.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Dispara o e-mail
        return mail($para_email, $assunto, $body, $headers);
    }
}
