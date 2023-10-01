<?php


namespace Lexxsoft\Odata\Exceptions;

use Exception;

/**
 * Класс исключения для отсутствующей модели
 */
class OdataModelNotExistException extends Exception
{
    public function __construct(string $modelName)
    {
        $text = "Model '" . $modelName . "' does not exist";
        parent::__construct($text);
    }

}
