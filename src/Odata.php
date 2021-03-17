<?php


namespace LexxSoft\odata;


use LexxSoft\odata\Resources\DefaultResource;
use LexxSoft\odata\Resources\ErrorResource;
use LexxSoft\odata\Primitives\OdataEntity;
use Illuminate\Http\Request;

/**
 * Class Odata
 * @package LexxSoft\odata
 */
class Odata
{
    /**
     * @var OdataEntity
     */
    private $restEntity;

    /**
     * @var \Exception|null
     */
    private $error = null;

    /**
     * @var array|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Model[]|mixed|object|string|null
     */
    private $response;

    /**
     * @var string
     */
    private $responseContent = 'json';

    /**
     * Odata constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $parts = explode('/', $request->getPathInfo());
        array_shift($parts); // Убираем пустой
        array_shift($parts); // убираем 'api'
        $entityValue = array_shift($parts); // имя сущности или набора

        // Достаем имя сущности и ключ
//        $re = '/^(?<entity>.+?(?=set)?)(?<isset>set)?$/mi';
        $re = '/(?<entity>.[^(]+)(?<containKey>\(?(?<key>.+)\))?/m';
        preg_match_all($re, $entityValue, $matches, PREG_SET_ORDER, 0);
        $entityKey = isset($matches[0]['key']) ? $matches[0]['key'] : null;

        try {
            // Создаем экземпляр REST сущности
            $this->restEntity = new OdataEntity($matches[0]['entity'], $entityKey);
        } catch (\Exception $e) {
            $this->error = $e;
//            throw SyslemLog::Exception($e->getMessage(), $e->getCode());
            return;
        }

        try {
            if ($this->restEntity->isMetadata()) {
                // Если запрос метаданных, то меняем тип контента ответа на XML и генерируем метаданные
                $this->responseContent = 'xml';
                $this->response = $this->restEntity->generateMetadata();
            } elseif ($this->restEntity->isController()) {
                // Если к сущности привязан контроллер, пытаемся вызвать его
                $this->response = $this->restEntity->callControllerMethod();
            } else {
                // Если нет контроллера или не описаны соответствующие методы,
                // то вызываем динамический процесс работы с данными
                $this->response = $this->restEntity->callDynamic();
            }
        } catch (\Exception $e) {
            $this->error = $e;
//            throw SyslemLog::Exception($e->getMessage(), $e->getCode());
            return;
        }
    }

    /**
     * Ответ
     * @return DefaultResource|ErrorResource|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function response()
    {
        if ($this->error !== null) {
            return new ErrorResource($this->error);
        }

        if ($this->responseContent == 'xml') {
            return response($this->response, 200, [
                'Content-Type' => 'application/xml'
            ]);
        } else {
            return new DefaultResource($this->response);
        }
    }
}
