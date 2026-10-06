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
}
