<?php
namespace app\Helpers;

use Exception;

class UploadHelper {
    
    private static $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp'];
    private static $allowedVideoTypes = ['video/mp4', 'video/webm'];
    private static $allowedDocTypes = ['application/pdf', 'text/xml', 'application/xml'];
    private static $maxSize = 20 * 1024 * 1024; // 20 MB

    /**
     * Processa upload de arquivo único (ex: PDF ou XML da Nota Fiscal)
     */
    public static function processInvoiceUpload($file) {
        $uploadDir = __DIR__ . '/../../uploads/invoices/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => "Erro no upload do arquivo."];
        }

        if ($file['size'] > self::$maxSize) {
            return ['error' => "Arquivo excede o limite de 20MB."];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::$allowedDocTypes)) {
            return ['error' => "Apenas arquivos PDF ou XML são permitidos."];
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeName = uniqid('nf_', true) . '.' . strtolower($extension);
        $destination = $uploadDir . $safeName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => 'uploads/invoices/' . $safeName];
        }

        return ['error' => "Falha ao salvar o arquivo no servidor."];
    }

    /**
     * Processa o upload múltiplo de arquivos de mídia (imagens e vídeos)
     * Retorna array de arquivos salvos com sucesso e mensagens de erro.
     */
    public static function processTicketMedia($files) {
        $uploadDir = __DIR__ . '/../../uploads/tickets/';
        $savedFiles = [];
        $errors = [];

        // Normaliza o array de arquivos do $_FILES (quando é múltiplo)
        $fileArray = self::reArrayFiles($files);

        foreach ($fileArray as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Erro no upload do arquivo " . $file['name'];
                }
                continue;
            }

            // Valida tamanho
            if ($file['size'] > self::$maxSize) {
                $errors[] = "Arquivo " . $file['name'] . " excede o limite de 20MB.";
                continue;
            }

            // Valida Mime Type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $isImage = in_array($mimeType, self::$allowedImageTypes);
            $isVideo = in_array($mimeType, self::$allowedVideoTypes);

            if (!$isImage && !$isVideo) {
                $errors[] = "Arquivo " . $file['name'] . " possui formato não suportado.";
                continue;
            }

            // Gera nome seguro
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName = uniqid('media_', true) . '.' . strtolower($extension);
            $destination = $uploadDir . $safeName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $savedFiles[] = [
                    'url' => 'uploads/tickets/' . $safeName,
                    'tipo' => $isImage ? 'imagem' : 'video'
                ];
            } else {
                $errors[] = "Falha ao salvar o arquivo " . $file['name'];
            }
        }

        return ['success' => $savedFiles, 'errors' => $errors];
    }

    /**
     * Helper para reorganizar o array do $_FILES quando multiple="multiple"
     */
    private static function reArrayFiles($file_post) {
        $file_ary = [];
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);

        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }

        return $file_ary;
    }
}
