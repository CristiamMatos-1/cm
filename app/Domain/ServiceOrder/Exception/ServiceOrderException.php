<?php
namespace app\Domain\ServiceOrder\Exception;

use DomainException;

/**
 * Base de todas as violações de regra de negócio do módulo de OS.
 * Controllers podem capturar esta classe para exibir a mensagem ao usuário
 * (as mensagens são sempre seguras para exibição, em português).
 */
class ServiceOrderException extends DomainException
{
}
