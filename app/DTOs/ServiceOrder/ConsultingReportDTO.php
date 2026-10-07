<?php
namespace app\DTOs\ServiceOrder;

use app\Domain\ServiceOrder\Money;

/**
 * Análise, solução e relatórios de uma OS de consultoria/projeto/infraestrutura.
 *
 * Os campos podem ser salvos parcialmente enquanto a OS está em rascunho/diagnóstico;
 * a completude é exigida pelo domínio apenas no envio para aprovação do cliente.
 */
final class ConsultingReportDTO
{
    /** @var string|null */
    private $problemaDiagnosticado;
    /** @var string|null */
    private $solucaoProposta;
    /** @var string|null */
    private $relatorioTecnico;
    /** @var string|null */
    private $relatorioEngenheiro;
    /** @var Money */
    private $valorConsultoria;

    private function __construct()
    {
    }

    /**
     * @param  array<string, mixed> $input chaves: problema_diagnosticado, solucao_proposta, relatorio_tecnico,
     *                                     relatorio_engenheiro, valor_consultoria
     * @throws \app\Domain\ServiceOrder\Exception\ValidationException
     */
    public static function fromArray(array $input): self
    {
        $in = new Input($input);
        $dto = new self();

        $dto->problemaDiagnosticado = $in->longText('problema_diagnosticado', 'o problema diagnosticado', 30000, false);
        $dto->solucaoProposta = $in->longText('solucao_proposta', 'a solução proposta', 30000, false);
        $dto->relatorioTecnico = $in->longText('relatorio_tecnico', 'o relatório técnico', 100000, false);
        $dto->relatorioEngenheiro = $in->longText('relatorio_engenheiro', 'o relatório do engenheiro', 100000, false);
        $dto->valorConsultoria = $in->money('valor_consultoria', 'o valor da consultoria/projeto');

        $in->assertValid();
        return $dto;
    }

    public function problemaDiagnosticado(): ?string
    {
        return $this->problemaDiagnosticado;
    }

    public function solucaoProposta(): ?string
    {
        return $this->solucaoProposta;
    }

    public function relatorioTecnico(): ?string
    {
        return $this->relatorioTecnico;
    }

    public function relatorioEngenheiro(): ?string
    {
        return $this->relatorioEngenheiro;
    }

    public function valorConsultoria(): Money
    {
        return $this->valorConsultoria;
    }
}
