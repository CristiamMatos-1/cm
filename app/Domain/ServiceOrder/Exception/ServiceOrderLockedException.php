<?php
namespace app\Domain\ServiceOrder\Exception;

/** Tentativa de editar diagnóstico/valores depois que a OS foi enviada ao cliente. */
class ServiceOrderLockedException extends ServiceOrderException
{
}
