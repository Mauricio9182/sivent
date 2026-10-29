<?php
 
namespace App\Exceptions;
 
use RuntimeException;
 
/** Error de negocio al confirmar una venta. Provoca ROLLBACK completo. */
class VentaException extends RuntimeException
{
    public function __construct(
        string $mensaje,
        public int $status = 422,
        public ?string $codigo = null
    ) {
        parent::__construct($mensaje);
    }
}
 