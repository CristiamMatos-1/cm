<?php
namespace app\Domain\ServiceOrder;

use app\Domain\ServiceOrder\Exception\ValidationException;
use app\DTOs\ServiceOrder\MaintenanceQuoteDTO;

/**
 * Detalhes de uma OS de manutenção (Value Object imutável).
 *
 * Total = peças + mão de obra + software, sempre calculado aqui (e também pela coluna gerada do banco).
 */
final class MaintenanceDetails
{
    /** @var string */
    private $equipmentType;
    /** @var string|null */
    private $equipmentDescription;
    /** @var string|null */
    private $serialNumber;
    /** @var string */
    private $customerComplaint;
    /** @var string|null */
    private $technicalDiagnosis;
    /** @var Money */
    private $partsCost;
    /** @var Money */
    private $laborCost;
    /** @var Money */
    private $softwareCost;

    public function __construct(
        string $equipmentType,
        ?string $equipmentDescription,
        ?string $serialNumber,
        string $customerComplaint,
        ?string $technicalDiagnosis = null,
        ?Money $partsCost = null,
        ?Money $laborCost = null,
        ?Money $softwareCost = null
    ) {
        $errors = [];
        if (!EquipmentType::isValid($equipmentType)) {
            $errors['equipamento_tipo'] = 'Tipo de equipamento inválido.';
        }
        if (trim($customerComplaint) === '') {
            $errors['relato_defeito_cliente'] = 'Informe o relato do defeito.';
        }
        foreach (['valor_pecas' => $partsCost, 'valor_mao_de_obra' => $laborCost, 'valor_software' => $softwareCost] as $field => $cost) {
            if ($cost !== null && $cost->isNegative()) {
                $errors[$field] = 'Os valores não podem ser negativos.';
            }
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->equipmentType = $equipmentType;
        $this->equipmentDescription = $equipmentDescription;
        $this->serialNumber = $serialNumber;
        $this->customerComplaint = $customerComplaint;
        $this->technicalDiagnosis = $technicalDiagnosis;
        $this->partsCost = $partsCost ?? Money::zero();
        $this->laborCost = $laborCost ?? Money::zero();
        $this->softwareCost = $softwareCost ?? Money::zero();
    }

    /**
     * Devolve uma nova instância com diagnóstico e valores do DTO (a atual não muda).
     */
    public function withQuote(MaintenanceQuoteDTO $quote): self
    {
        return new self(
            $this->equipmentType,
            $this->equipmentDescription,
            $this->serialNumber,
            $this->customerComplaint,
            $quote->diagnosticoTecnico(),
            $quote->valorPecas(),
            $quote->valorMaoDeObra(),
            $quote->valorSoftware()
        );
    }

    public function total(): Money
    {
        return $this->partsCost->add($this->laborCost)->add($this->softwareCost);
    }

    public function equipmentType(): string
    {
        return $this->equipmentType;
    }

    public function equipmentDescription(): ?string
    {
        return $this->equipmentDescription;
    }

    public function serialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function customerComplaint(): string
    {
        return $this->customerComplaint;
    }

    public function technicalDiagnosis(): ?string
    {
        return $this->technicalDiagnosis;
    }

    public function partsCost(): Money
    {
        return $this->partsCost;
    }

    public function laborCost(): Money
    {
        return $this->laborCost;
    }

    public function softwareCost(): Money
    {
        return $this->softwareCost;
    }

    /**
     * @return array<string, string> erros de completude para enviar ao cliente (campo => mensagem)
     */
    public function missingForClientApproval(): array
    {
        $errors = [];
        if ($this->technicalDiagnosis === null || trim($this->technicalDiagnosis) === '') {
            $errors['diagnostico_tecnico'] = 'Preencha o diagnóstico técnico antes de enviar ao cliente.';
        }
        if ($this->total()->isZero()) {
            $errors['valor_total'] = 'Informe ao menos um valor (peças, mão de obra ou software) antes de enviar ao cliente.';
        }
        return $errors;
    }
}
