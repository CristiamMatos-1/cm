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

    private const TICKET_STATUS = [
        'aberto'         => ['label' => 'Aberto',          'class' => 'bg-yellow-100 text-yellow-800 ring-yellow-200', 'icon' => 'fa-folder-open'],
        'andamento'      => ['label' => 'Em andamento',    'class' => 'bg-blue-100 text-blue-800 ring-blue-200',       'icon' => 'fa-spinner'],
        'em_analise'     => ['label' => 'Em análise',      'class' => 'bg-purple-100 text-purple-800 ring-purple-200', 'icon' => 'fa-search'],
        'em_execucao'    => ['label' => 'Em execução',     'class' => 'bg-indigo-100 text-indigo-800 ring-indigo-200', 'icon' => 'fa-cogs'],
        'esperando_peca' => ['label' => 'Aguardando peça', 'class' => 'bg-orange-100 text-orange-800 ring-orange-200', 'icon' => 'fa-box'],
        'finalizado'     => ['label' => 'Finalizado',      'class' => 'bg-green-100 text-green-800 ring-green-200',    'icon' => 'fa-check-circle'],
        'rejeitado'      => ['label' => 'Rejeitado',       'class' => 'bg-red-100 text-red-800 ring-red-200',          'icon' => 'fa-times-circle'],
    ];

    public static function ticketStatusLabel($status) {
        return self::TICKET_STATUS[$status]['label'] ?? ucfirst(str_replace('_', ' ', (string)$status));
    }

    public static function ticketStatusBadge($status) {
        $meta = self::TICKET_STATUS[$status] ?? ['class' => 'bg-gray-100 text-gray-700 ring-gray-200', 'icon' => 'fa-info-circle'];
        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full ring-1 ring-inset '
            . $meta['class'] . '"><i class="fas ' . $meta['icon'] . '"></i> '
            . Security::esc(self::ticketStatusLabel($status)) . '</span>';
    }

    private const LOG_LEVEL = [
        'info'    => ['label' => 'Info',    'dot' => 'bg-blue-500',  'text' => 'text-blue-700'],
        'aviso'   => ['label' => 'Aviso',   'dot' => 'bg-yellow-500', 'text' => 'text-yellow-700'],
        'critico' => ['label' => 'Crítico', 'dot' => 'bg-red-500',   'text' => 'text-red-700'],
    ];

    public static function logLevel($level) {
        return self::LOG_LEVEL[$level] ?? self::LOG_LEVEL['info'];
    }

    /**
     * Texto relativo para um vencimento (ex.: "Hoje", "Amanhã", "Em 3 dias", "Venceu há 2 dias").
     */
    public static function dueLabel($date) {
        $dias = (int)floor((strtotime((string)$date) - strtotime(date('Y-m-d'))) / 86400);
        if ($dias === 0) {
            return 'Hoje';
        }
        if ($dias === 1) {
            return 'Amanhã';
        }
        return $dias > 0 ? "Em {$dias} dias" : 'Venceu há ' . abs($dias) . ($dias === -1 ? ' dia' : ' dias');
    }

    /**
     * Duração em segundos -> "12d 4h", "5h 20min" ou "42min".
     */
    public static function duration($seconds) {
        $seconds = (int)$seconds;
        $d = intdiv($seconds, 86400);
        $h = intdiv($seconds % 86400, 3600);
        $m = intdiv($seconds % 3600, 60);
        if ($d > 0) {
            return "{$d}d {$h}h";
        }
        return $h > 0 ? "{$h}h {$m}min" : "{$m}min";
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
