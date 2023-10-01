<?php


namespace Lexxsoft\Odata\Exceptions;

/**
 * Класс исключения для попытки вызова контроллера
 */
class OdataTryCallControllerException extends \Exception
{
    public function __construct(string $controllerName, string $methodName)
    {
        $text = "Fail to call controller '" . $controllerName . "' with method '" . $methodName . "'";
        parent::__construct($text);
    }
}
