<?php
namespace app\Helpers;

class UI {

    private const BUDGET_STATUS = [
        'pendente'  => ['label' => 'Pendente',  'class' => 'bg-yellow-100 text-yellow-800 ring-yellow-200', 'icon' => 'fa-clock'],
        'aprovado'  => ['label' => 'Aprovado',  'class' => 'bg-green-100 text-green-800 ring-green-200',   'icon' => 'fa-check-circle'],
        'rejeitado' => ['label' => 'Rejeitado', 'class' => 'bg-red-100 text-red-800 ring-red-200',         'icon' => 'fa-times-circle'],
        'expirado'  => ['label' => 'Expirado',  'class' => 'bg-gray-100 text-gray-700 ring-gray-200',      'icon' => 'fa-hourglass-end'],
    ];

    public static function budgetStatusLabel($status) {
        return self::BUDGET_STATUS[$status]['label'] ?? ucfirst(str_replace('_', ' ', (string)$status));
    }

    public static function budgetStatusClass($status) {
        return self::BUDGET_STATUS[$status]['class'] ?? 'bg-blue-100 text-blue-800 ring-blue-200';
    }

    public static function budgetStatusBadge($status, $attrs = '') {
        $icon = self::BUDGET_STATUS[$status]['icon'] ?? 'fa-info-circle';
        return '<span ' . ($attrs !== '' ? $attrs . ' ' : '') . 'class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full ring-1 ring-inset '
            . self::budgetStatusClass($status) . '"><i class="fas ' . $icon . '"></i> '
            . Security::esc(self::budgetStatusLabel($status)) . '</span>';
    }

    public static function money($value) {
        return 'R$ ' . number_format((float)$value, 2, ',', '.');
    }

    public static function date($value, $withTime = false) {
        if (empty($value) || strpos((string)$value, '0000-00-00') === 0) {
            return '-';
        }
        $ts = strtotime($value);
        return $ts ? date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $ts) : '-';
    }

    /**
     * Resumo da decisão tomada (aprovação/rejeição) de um orçamento.
     */
    public static function budgetDecisionText($budget) {
        if (($budget['status'] ?? '') === 'aprovado') {
            return 'Aprovado em ' . self::date($budget['data_autorizacao'] ?? null, true);
        }
        if (($budget['status'] ?? '') === 'rejeitado') {
            return 'Rejeitado em ' . self::date($budget['data_rejeicao'] ?? null, true);
        }
        return '';
    }

    private const HISTORY_ACTIONS = [
        'criado'      => ['label' => 'Orçamento criado',            'icon' => 'fa-plus-circle',  'color' => 'text-gray-500'],
        'aprovado'    => ['label' => 'Aprovado',                    'icon' => 'fa-check-circle', 'color' => 'text-green-600'],
        'rejeitado'   => ['label' => 'Rejeitado',                   'icon' => 'fa-times-circle', 'color' => 'text-red-600'],
        'reaberto'    => ['label' => 'Reaberto pelo administrador', 'icon' => 'fa-unlock',       'color' => 'text-blue-600'],
        'reativado'   => ['label' => 'Reativado após expirar',      'icon' => 'fa-redo',         'color' => 'text-blue-600'],
        'expirado'    => ['label' => 'Expirado',                    'icon' => 'fa-hourglass-end','color' => 'text-gray-500'],
        'nova_versao' => ['label' => 'Nova versão gerada',          'icon' => 'fa-copy',         'color' => 'text-indigo-600'],
    ];

    private const HISTORY_ORIGINS = [
        'admin' => 'Administrador', 'tecnico' => 'Técnico', 'cliente' => 'Cliente (área logada)',
        'link_publico' => 'Cliente (link público)', 'sistema' => 'Sistema',
    ];

    public static function historyAction($acao) {
        return self::HISTORY_ACTIONS[$acao] ?? ['label' => ucfirst((string)$acao), 'icon' => 'fa-circle', 'color' => 'text-gray-500'];
    }

    public static function historyOrigin($origem) {
        return self::HISTORY_ORIGINS[$origem] ?? ucfirst((string)$origem);
    }

    /**
     * Minimização de dados em páginas públicas: "Maria da Silva Souza" -> "Maria S."
     */
    public static function maskName($name) {
        $parts = preg_split('/\s+/u', trim((string)$name), -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return '';
        }
        if (count($parts) === 1) {
            return $parts[0];
        }
        $last = end($parts);
        $initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($last, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($last, 0, 1));
        return $parts[0] . ' ' . $initial . '.';
    }
}
