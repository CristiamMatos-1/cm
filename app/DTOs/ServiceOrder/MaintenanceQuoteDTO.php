<?php
namespace app\DTOs\ServiceOrder;

use app\Domain\ServiceOrder\Money;

/**
 * Diagnóstico técnico + orçamento discriminado de uma OS de manutenção.
 *
 * Os três valores são OBRIGATÓRIOS e separados (peças, mão de obra, software): o técnico precisa
 * preencher cada um explicitamente, usando 0,00 quando não houver. O total NÃO é recebido do
 * formulário; é sempre calculado (Money::add / coluna gerada no banco).
 */
final class MaintenanceQuoteDTO
{
    /** @var string|null */
    private $diagnosticoTecnico;
    /** @var Money */
    private $valorPecas;
    /** @var Money */
    private $valorMaoDeObra;
    /** @var Money */
    private $valorSoftware;

    private function __construct()
    {
    }

    /**
     * @param  array<string, mixed> $input chaves: diagnostico_tecnico, valor_pecas, valor_mao_de_obra, valor_software
     * @throws \app\Domain\ServiceOrder\Exception\ValidationException
     */
    public static function fromArray(array $input): self
    {
        $in = new Input($input);
        $dto = new self();

        $dto->diagnosticoTecnico = $in->longText('diagnostico_tecnico', 'o diagnóstico técnico', 20000, false);
        $dto->valorPecas = $in->money('valor_pecas', 'o valor de peças');
        $dto->valorMaoDeObra = $in->money('valor_mao_de_obra', 'o valor de mão de obra');
        $dto->valorSoftware = $in->money('valor_software', 'o valor de software/licenças');

        $in->assertValid();
        return $dto;
    }

    public function diagnosticoTecnico(): ?string
    {
        return $this->diagnosticoTecnico;
    }

    public function valorPecas(): Money
    {
        return $this->valorPecas;
    }

    public function valorMaoDeObra(): Money
    {
        return $this->valorMaoDeObra;
    }

    public function valorSoftware(): Money
    {
        return $this->valorSoftware;
    }
}
