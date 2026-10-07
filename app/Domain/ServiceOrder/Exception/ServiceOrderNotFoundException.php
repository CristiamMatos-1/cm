<?php
namespace app\Domain\ServiceOrder\Exception;

class ServiceOrderNotFoundException extends ServiceOrderException
{
    public function __construct(string $message = 'Ordem de serviço não encontrada.')
    {
        parent::__construct($message);
    }
}
