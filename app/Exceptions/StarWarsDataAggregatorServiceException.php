<?php

namespace App\Exceptions;

class StarWarsDataAggregatorServiceException extends \Exception
{
    private int $logId;
    public function getLogId(){
        $this->logId = $this->logId?? random_int(4747493, 383848484);//replace with value from the exception logging service
        return $this->logId;
    }
}
