<?php
namespace app\Domain\ServiceOrder;

/**
 * Tipos de serviço (enum de domínio; classe com constantes por compatibilidade com PHP < 8.1).
 *
 * Cada tipo usa exatamente um "módulo de detalhes":
 *  - MANUTENCAO_HARDWARE                      -> MaintenanceDetails (defeito, diagnóstico, peças/mão de obra/software)
 *  - INFRAESTRUTURA_REDES, PROJETO, CONSULTORIA -> ConsultingDetails (problema, solução, relatórios, valor)
 */
final class ServiceOrderType
{
    public const MANUTENCAO_HARDWARE = 'manutencao_hardware';
    public const INFRAESTRUTURA_REDES = 'infraestrutura_redes';
    public const PROJETO = 'projeto';
    public const CONSULTORIA = 'consultoria';

    private const LABELS = [
        self::MANUTENCAO_HARDWARE => 'Manutenção de Hardware (Notebook/Desktop)',
        self::INFRAESTRUTURA_REDES => 'Infraestrutura / Redes',
        self::PROJETO => 'Projeto',
        self::CONSULTORIA => 'Consultoria',
    ];

    private function __construct()
    {
    }

    /** @return string[] */
    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    public static function isValid(string $type): bool
    {
        return isset(self::LABELS[$type]);
    }

    public static function label(string $type): string
    {
        return self::LABELS[$type] ?? $type;
    }

    /** @return array<string, string> valor => rótulo (para <select>) */
    public static function options(): array
    {
        return self::LABELS;
    }

    public static function usesMaintenanceModule(string $type): bool
    {
        return $type === self::MANUTENCAO_HARDWARE;
    }

    public static function usesConsultingModule(string $type): bool
    {
        return in_array($type, [self::INFRAESTRUTURA_REDES, self::PROJETO, self::CONSULTORIA], true);
    }
}
