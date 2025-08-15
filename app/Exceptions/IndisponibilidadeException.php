<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class IndisponibilidadeException extends HttpException
{
    /**
     * @param string $nomeRecurso
     */
    public function __construct(string $nomeRecurso = 'Recurso')
    {
        parent::__construct(422, "{$nomeRecurso} está indisponível para o período informado.");
    }
}
