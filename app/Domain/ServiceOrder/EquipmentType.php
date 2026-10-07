<?php
namespace app\Domain\ServiceOrder;

/** Tipos de equipamento atendidos pelo módulo de manutenção. */
final class EquipmentType
{
    public const NOTEBOOK = 'notebook';
    public const DESKTOP = 'desktop';
    public const SERVIDOR = 'servidor';
    public const OUTRO = 'outro';

    private const LABELS = [
        self::NOTEBOOK => 'Notebook',
        self::DESKTOP => 'Desktop',
        self::SERVIDOR => 'Servidor',
        self::OUTRO => 'Outro',
    ];

    private function __construct()
    {
    }

    public static function isValid(string $type): bool
    {
        return isset(self::LABELS[$type]);
    }

    public static function label(string $type): string
    {
        return self::LABELS[$type] ?? $type;
    }

    /** @return array<string, string> valor => rótulo */
    public static function options(): array
    {
        return self::LABELS;
    }
}
