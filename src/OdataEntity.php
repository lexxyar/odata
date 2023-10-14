<?php

namespace Lexxsoft\Odata;

use Error;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use LexxSoft\odata\Exceptions\OdataTryCallControllerException;

class OdataEntity
{
    private string $_entityName = '';
    private string $_methodName = '';
    private string $_controllerName = '';
    private string $_modelName = '';


    public function __construct(string $entityName)
    {
        $this->_entityName = $entityName;
        $this->_controllerName = $this->buildControllerClassName();
        $this->_methodName = $this->detectControllerMethodName();
    }

    public function getModel(): \Illuminate\Database\Eloquent\Model
    {
        $modelClassname = $this->buildModelClassName();

        if (strtolower($this->_entityName) !== Str::plural(strtolower($this->_entityName))) {

            if (class_exists($modelClassname)) {
                throw new Error('Entity set "' . $this->_entityName . '" is not defined. Try to use "' . Str::plural(strtolower($this->_entityName)) . '"');
            } else {
                throw new Error('Entity set "' . $this->_entityName . '" is not defined.');
            }
        }

        if (!class_exists($modelClassname)) {
            throw new Error("Model class $modelClassname not found");
        }
        return App::make($modelClassname);
    }

    private function buildModelClassName(): string
    {
        return config('odata.namespace.models', "\\App\\Models") . "\\" . ucfirst(Str::singular($this->_entityName));
    }

    private function buildControllerClassName(): string
    {
        return config('odata.namespace.controllers', "\\App\\Http\\Controllers") . "\\" . ucfirst(Str::singular($this->_entityName) . 'Controller');
    }

    /**
     * Определяем имя метода относительно типа запроса
     */
    public function detectControllerMethodName(): string
    {
        $methodName = '';
        if (request()->getMethod() === 'GET') {
            $methodName = 'index';
        } elseif (request()->getMethod() === 'DELETE') {
            $methodName = 'destroy';
        } elseif (request()->getMethod() === 'POST') {
            $methodName = 'store';
        } elseif (in_array(request()->getMethod(), ['PUT', 'PATCH'])) {
            $methodName = 'update';
        }
        return $methodName;
    }

    /**
     * Проверка существования контроллера и наличия в нем соответствующего метода
     */
    public function hasController(): bool
    {
        if (!class_exists($this->_controllerName)) {
            return false;
        }

        $controller = new $this->_controllerName;
        if (!method_exists($controller, $this->_methodName)) {
            return false;
        }

        return true;
    }

    /**
     * Вызов метода контроллера
     * @throws OdataTryCallControllerException
     */
    public function callController(): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        if (!$this->hasController()) {
            throw new OdataTryCallControllerException($this->_controllerName, $this->_methodName);
        }

        $controller = new $this->_controllerName;
        return call_user_func_array([$controller, $this->_methodName], []);
    }
}
