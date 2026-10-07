<?php
namespace app\Domain\ServiceOrder\Exception;

/**
 * O cliente (ou outra sessão) já respondeu a esta proposta; a decisão não pode ser alterada.
 * Controllers devem mapear para HTTP 409.
 */
class AlreadyDecidedException extends ServiceOrderException
{
}
