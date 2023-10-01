<?php


namespace Lexxsoft\Odata\Exceptions;

/**
 * Класс исключения для модели НЕ сущности
 */
class OdataModelIsNotRestableException extends \Exception
{
    public function __construct(string $modelName)
    {
        $text = "Model '" . $modelName . "' is not restable. Add `use Restable` to model class.";
        parent::__construct($text);
    }
}
