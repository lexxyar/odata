<?php


namespace LexxSoft\odata\Exceptions;

use Exception;

/**
 * Class OdataModelNotExistException
 *
 * Класс исключения для отсутствующей модели
 *
 * @package LexxSoft\odata\Exceptions
 */
class OdataModelNotExistException extends Exception
{
    /**
     * OdataModelNotExistException constructor.
     * @param $modelName
     */
    public function __construct($modelName)
    {
        $text = "Model '" . $modelName . "' does not exist";
        parent::__construct($text);
    }

}
