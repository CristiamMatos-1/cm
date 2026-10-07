<?php
namespace app\Domain\ServiceOrder;

/**
 * Status da OS e máquina de estados do workflow.
 *
 *   rascunho ─▶ aguardando_diagnostico ─▶ aguardando_aprovacao_cliente ─┬▶ aprovado ─▶ em_execucao ─▶ concluido
 *                      ▲                                                 │
 *                      └──────────────── (revisão) ◀─────────────────────┴▶ rejeitado
 *
 * Observações:
 *  - Cliente só decide (aprovado/rejeitado) a partir de "aguardando_aprovacao_cliente".
 *  - A equipe pode retirar a OS da aprovação (volta a "aguardando_diagnostico") para corrigir valores;
 *    OS rejeitada também volta a "aguardando_diagnostico" para gerar nova proposta.
 *  - "concluido" é estado final.
 */
final class ServiceOrderStatus
{
    public const RASCUNHO = 'rascunho';
    public const AGUARDANDO_DIAGNOSTICO = 'aguardando_diagnostico';
    public const AGUARDANDO_APROVACAO_CLIENTE = 'aguardando_aprovacao_cliente';
    public const APROVADO = 'aprovado';
    public const REJEITADO = 'rejeitado';
    public const EM_EXECUCAO = 'em_execucao';
    public const CONCLUIDO = 'concluido';

    private const LABELS = [
        self::RASCUNHO => 'Rascunho',
        self::AGUARDANDO_DIAGNOSTICO => 'Aguardando Diagnóstico',
        self::AGUARDANDO_APROVACAO_CLIENTE => 'Aguardando Aprovação do Cliente',
        self::APROVADO => 'Aprovado',
        self::REJEITADO => 'Rejeitado',
        self::EM_EXECUCAO => 'Em Execução',
        self::CONCLUIDO => 'Concluído',
    ];

    private const TRANSITIONS = [
        self::RASCUNHO => [self::AGUARDANDO_DIAGNOSTICO],
        self::AGUARDANDO_DIAGNOSTICO => [self::AGUARDANDO_APROVACAO_CLIENTE],
        self::AGUARDANDO_APROVACAO_CLIENTE => [self::APROVADO, self::REJEITADO, self::AGUARDANDO_DIAGNOSTICO],
        self::APROVADO => [self::EM_EXECUCAO],
        self::REJEITADO => [self::AGUARDANDO_DIAGNOSTICO],
        self::EM_EXECUCAO => [self::CONCLUIDO],
        self::CONCLUIDO => [],
    ];

    /** Status em que diagnóstico, relatórios e valores ainda podem ser editados. */
    private const EDITABLE = [self::RASCUNHO, self::AGUARDANDO_DIAGNOSTICO];

    private function __construct()
    {
    }

    /** @return string[] */
    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    public static function isValid(string $status): bool
    {
        return isset(self::LABELS[$status]);
    }

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? $status;
    }

    /** @return array<string, string> valor => rótulo */
    public static function options(): array
    {
        return self::LABELS;
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /** @return string[] próximos status permitidos a partir de $from */
    public static function allowedFrom(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public static function isEditable(string $status): bool
    {
        return in_array($status, self::EDITABLE, true);
    }

    /** A partir de "aguardando aprovação" a OS pode ser exibida ao cliente (antes disso é rascunho interno). */
    public static function isVisibleToClient(string $status): bool
    {
        return self::isValid($status) && !self::isEditable($status);
    }

    public static function isFinal(string $status): bool
    {
        return (self::TRANSITIONS[$status] ?? []) === [];
    }
}
