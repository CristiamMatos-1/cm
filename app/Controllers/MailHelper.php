<?php
namespace app\Helpers;

class MailHelper {
    
    /**
     * Envia um e-mail padrão do sistema utilizando as configurações do banco ou o mail() nativo.
     * Sem bibliotecas pesadas (PHPMailer/Composer), usa a função mail() do PHP configurada com HTML.
     */
    public static function enviarEmail($para_email, $para_nome, $assunto, $mensagem_html) {
        $configModel = new \app\Models\ConfigModel();
        $empresa = $configModel->getCompanyInfo();
        
        $nome_empresa = !empty($empresa['razao_social']) ? $empresa['razao_social'] : 'ITSM Pro';
        $email_remetente = !empty($empresa['email_contato']) ? $empresa['email_contato'] : 'naoresponda@seudominio.com.br';

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $nome_empresa . " <" . $email_remetente . ">\r\n";
        $headers .= "Reply-To: " . $email_remetente . "\r\n";

        // Layout básico de E-mail
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>
            <div style='max-w-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>
                <h2 style='color: #1e3a8a; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px;'>" . $nome_empresa . "</h2>
                <p style='font-size: 16px; color: #333;'>Olá <strong>" . $para_nome . "</strong>,</p>
                <div style='margin-top: 20px; font-size: 14px; color: #555; line-height: 1.6;'>
                    " . $mensagem_html . "
                </div>
                <div style='margin-top: 40px; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 10px;'>
                    <p>Esta é uma mensagem automática enviada pelo sistema de gestão. Por favor, não responda diretamente a este e-mail.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Envia usando a função nativa do PHP (No cPanel funciona bem se o sendmail estiver configurado)
        return mail($para_email, $assunto, $body, $headers);
    }
}
