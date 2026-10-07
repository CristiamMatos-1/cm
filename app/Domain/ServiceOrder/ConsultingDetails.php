<?php
namespace app\Domain\ServiceOrder;

use app\Domain\ServiceOrder\Exception\ValidationException;
use app\DTOs\ServiceOrder\ConsultingReportDTO;

/**
 * Detalhes de uma OS de consultoria/projeto/infraestrutura (Value Object imutável).
 */
final class ConsultingDetails
{
    /** @var string */
    private $presentedProblem;
    /** @var string|null */
    private $diagnosedProblem;
    /** @var string|null */
    private $proposedSolution;
    /** @var string|null */
    private $technicalReport;
    /** @var string|null */
    private $engineerReport;
    /** @var Money */
    private $consultingFee;

    public function __construct(
        string $presentedProblem,
        ?string $diagnosedProblem = null,
        ?string $proposedSolution = null,
        ?string $technicalReport = null,
        ?string $engineerReport = null,
        ?Money $consultingFee = null
    ) {
        if (trim($presentedProblem) === '') {
            throw new ValidationException(['problema_apresentado' => 'Informe o problema apresentado / escopo inicial.']);
        }
        if ($consultingFee !== null && $consultingFee->isNegative()) {
            throw new ValidationException(['valor_consultoria' => 'O valor não pode ser negativo.']);
        }

        $this->presentedProblem = $presentedProblem;
        $this->diagnosedProblem = $diagnosedProblem;
        $this->proposedSolution = $proposedSolution;
        $this->technicalReport = $technicalReport;
        $this->engineerReport = $engineerReport;
        $this->consultingFee = $consultingFee ?? Money::zero();
    }

    /**
     * Devolve uma nova instância com os relatórios e o valor do DTO (a atual não muda).
     */
    public function withReport(ConsultingReportDTO $report): self
    {
        return new self(
            $this->presentedProblem,
            $report->problemaDiagnosticado(),
            $report->solucaoProposta(),
            $report->relatorioTecnico(),
            $report->relatorioEngenheiro(),
            $report->valorConsultoria()
        );
    }

    public function total(): Money
    {
        return $this->consultingFee;
    }

    public function presentedProblem(): string
    {
        return $this->presentedProblem;
    }

    public function diagnosedProblem(): ?string
    {
        return $this->diagnosedProblem;
    }

    public function proposedSolution(): ?string
    {
        return $this->proposedSolution;
    }

    public function technicalReport(): ?string
    {
        return $this->technicalReport;
    }

    public function engineerReport(): ?string
    {
        return $this->engineerReport;
    }

    public function consultingFee(): Money
    {
        return $this->consultingFee;
    }

    /**
     * @return array<string, string> erros de completude para enviar ao cliente (campo => mensagem)
     */
    public function missingForClientApproval(): array
    {
        $required = [
            'problema_diagnosticado' => [$this->diagnosedProblem, 'o problema diagnosticado'],
            'solucao_proposta' => [$this->proposedSolution, 'a solução proposta'],
            'relatorio_tecnico' => [$this->technicalReport, 'o relatório técnico'],
            'relatorio_engenheiro' => [$this->engineerReport, 'o relatório do engenheiro'],
        ];
        $errors = [];
        foreach ($required as $field => [$value, $label]) {
            if ($value === null || trim($value) === '') {
                $errors[$field] = 'Preencha ' . $label . ' antes de enviar ao cliente.';
            }
        }
        if ($this->consultingFee->isZero()) {
            $errors['valor_consultoria'] = 'Informe o valor da consultoria/projeto antes de enviar ao cliente.';
        }
        return $errors;
    }
}
