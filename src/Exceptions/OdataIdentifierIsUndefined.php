<?php

namespace Lexxsoft\Odata\Exceptions;

class OdataIdentifierIsUndefined extends \Exception
{
    public function __construct()
    {
        $text = 'Identifier is undefined';
        parent::__construct($text);
    }
}
