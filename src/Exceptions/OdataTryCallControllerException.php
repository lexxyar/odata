<?php


namespace LexxSoft\odata\Exceptions;

/**
 * Class OdataTryCallControllerException
 *
 * Класс исключения для попытки вызова контроллера
 *
 * @package LexxSoft\odata\Exceptions
 */
class OdataTryCallControllerException extends \Exception
{
    /**
     * OdataTryCallControllerException constructor.
     * @param $controllerName
     * @param $methodName
     */
    public function __construct($controllerName, $methodName)
    {
        $text = "Fail to call controller '" . $controllerName . "' with method '" . $methodName . "'";
        parent::__construct($text);
    }
}
