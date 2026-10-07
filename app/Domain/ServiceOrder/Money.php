<?php
namespace app\Domain\ServiceOrder;

use InvalidArgumentException;

/**
 * Value Object monetário (BRL) imutável.
 *
 * Armazena centavos como inteiro para evitar erros de ponto flutuante
 * (0.1 + 0.2 != 0.3) na soma de peças + mão de obra + software.
 */
final class Money
{
    /** @var int */
    private $cents;

    private function __construct(int $cents)
    {
        $this->cents = $cents;
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    /**
     * Cria a partir de um decimal canônico (ponto como separador), como o MySQL devolve DECIMAL.
     * Para texto digitado pelo usuário ("1.234,56") use Security::parseMoney() antes.
     *
     * @param  string|int|float $value ex.: "1234.56", 1234.5, 10
     * @throws InvalidArgumentException se não for numérico ou tiver mais de 2 casas decimais
     */
    public static function fromDecimal($value): self
    {
        if (is_float($value)) {
            $value = number_format($value, 2, '.', '');
        }
        $value = trim((string)$value);
        if (!preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', $value, $m)) {
            throw new InvalidArgumentException('Valor monetário inválido.');
        }
        $cents = ((int)$m[2]) * 100 + (int)str_pad($m[3] ?? '0', 2, '0');
        return new self($m[1] === '-' ? -$cents : $cents);
    }

    public function add(Money $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function equals(Money $other): bool
    {
        return $this->cents === $other->cents;
    }

    /** Formato para persistência (DECIMAL): "1234.56". */
    public function toDecimal(): string
    {
        $abs = abs($this->cents);
        return ($this->cents < 0 ? '-' : '') . intdiv($abs, 100) . '.' . str_pad((string)($abs % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Formato para exibição: "R$ 1.234,56". */
    public function format(): string
    {
        $abs = abs($this->cents);
        return ($this->cents < 0 ? '-' : '') . 'R$ ' . number_format(intdiv($abs, 100), 0, ',', '.') . ',' . str_pad((string)($abs % 100), 2, '0', STR_PAD_LEFT);
    }
}
