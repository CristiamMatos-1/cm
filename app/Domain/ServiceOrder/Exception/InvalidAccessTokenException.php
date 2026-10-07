<?php
namespace app\Domain\ServiceOrder\Exception;

/**
 * Link do portal do cliente inexistente, expirado ou revogado.
 * A mensagem é propositalmente genérica para não revelar qual das situações ocorreu.
 */
class InvalidAccessTokenException extends ServiceOrderException
{
    public function __construct()
    {
        parent::__construct('Link inválido ou expirado. Solicite um novo link à equipe.');
    }
}
