<?php


namespace LexxSoft\odata\Exceptions;

/**
 * Class OdataModelIsNotRestableException
 *
 * Класс исключения для модели НЕ сущности
 *
 * @package LexxSoft\odata\Exceptions
 */
class OdataModelIsNotRestableException extends \Exception
{
    /**
     * OdataModelIsNotRestableException constructor.
     * @param $modelName
     */
    public function __construct($modelName)
    {
        $text = "Model '" . $modelName . "' is not restable";
        parent::__construct($text);
    }
}
