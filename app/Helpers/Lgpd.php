<?php
namespace app\Helpers;

/**
 * Constantes e rótulos do módulo de privacidade (Lei 13.709/2018 - LGPD).
 */
class Lgpd {

    /** Altere a versão sempre que a Política de Privacidade mudar de forma relevante. */
    public const VERSAO_POLITICA = '2026-10-07';

    public const TIPOS_SOLICITACAO = [
        'acesso'                  => 'Confirmação e acesso aos meus dados',
        'correcao'                => 'Correção de dados incompletos ou desatualizados',
        'portabilidade'           => 'Portabilidade (cópia dos dados em formato estruturado)',
        'anonimizacao'            => 'Anonimização / eliminação dos meus dados',
        'informacao'              => 'Informação sobre compartilhamento dos meus dados',
        'revogacao_consentimento' => 'Revogação do consentimento',
        'outro'                   => 'Outro assunto de privacidade',
    ];

    public const STATUS_SOLICITACAO = [
        'aberta'   => ['label' => 'Em análise', 'class' => 'bg-yellow-100 text-yellow-800'],
        'atendida' => ['label' => 'Atendida',   'class' => 'bg-green-100 text-green-800'],
        'negada'   => ['label' => 'Negada',     'class' => 'bg-red-100 text-red-800'],
    ];

    public static function tipoLabel($tipo) {
        return self::TIPOS_SOLICITACAO[$tipo] ?? ucfirst((string)$tipo);
    }

    public static function statusLabel($status) {
        return self::STATUS_SOLICITACAO[$status]['label'] ?? ucfirst((string)$status);
    }

    public static function statusClass($status) {
        return self::STATUS_SOLICITACAO[$status]['class'] ?? 'bg-gray-100 text-gray-800';
    }
}
