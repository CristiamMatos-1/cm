<?php
namespace app\Domain\ServiceOrder\Exception;

use app\Domain\ServiceOrder\ServiceOrderStatus;

/** A OS não pode ir do status atual para o status solicitado. */
class InvalidStatusTransitionException extends ServiceOrderException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(sprintf(
            'Não é possível mudar a OS de "%s" para "%s".',
            ServiceOrderStatus::label($from),
            ServiceOrderStatus::label($to)
        ));
    }
}
