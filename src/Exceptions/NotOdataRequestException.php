<?php

namespace Lexxsoft\Odata\Exceptions;

class NotOdataRequestException extends \Exception
{
    public function __construct()
    {
        $text = 'Not OData request';
        parent::__construct($text);
    }
}
