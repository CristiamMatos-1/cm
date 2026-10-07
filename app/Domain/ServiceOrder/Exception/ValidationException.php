<?php
namespace app\Domain\ServiceOrder\Exception;

/**
 * Dados de entrada ou da OS inválidos/incompletos.
 * Carrega todos os erros de uma vez (campo => mensagem) para o formulário.
 */
class ValidationException extends ServiceOrderException
{
    /** @var array<string, string> */
    private $errors;

    /**
     * @param array<string, string> $errors campo => mensagem
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct(implode(' ', array_values($errors)));
    }

    /**
     * @return array<string, string> campo => mensagem
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
