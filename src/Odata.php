<?php


namespace LexxSoft\odata;


use Exception;
use LexxSoft\odata\Http\OdataRequest;
use LexxSoft\odata\Resources\ODataDefaultResource;
use LexxSoft\odata\Resources\ODataErrorResource;
use LexxSoft\odata\Primitives\OdataEntity;

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
   * @var Exception|null
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
   */
  public function __construct()
  {
    $entityValue = OdataRequest::getInstance()->requestPathParts[0]; // имя сущности или набора

    // Достаем имя сущности и ключ
    $re = '/(?<entity>.[^(]+)(?<containKey>\(?(?<key>.+)\))?/m';
    preg_match_all($re, $entityValue, $matches, PREG_SET_ORDER, 0);
    $entityKey = isset($matches[0]['key']) ? $matches[0]['key'] : null;

    try {
      // Создаем экземпляр REST сущности
      $this->restEntity = new OdataEntity($matches[0]['entity'], $entityKey);
    } catch (Exception $e) {
      $this->error = $e;
      throw $e;
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
    } catch (Exception $e) {
      $this->error = $e;
//      throw $e;
    }
  }

  /**
   * Ответ
   * @return ODataDefaultResource|ODataErrorResource|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
   */
  public function response()
  {
    if ($this->error !== null) {
      return new ODataErrorResource($this->error);
    }

    if ($this->responseContent == 'xml') {
      return response($this->response, 200, [
        'Content-Type' => 'application/xml'
      ]);
    } else {
      if (OdataRequest::getInstance()->isCountRequested()) {
        return $this->response;
      } else {
        return new ODataDefaultResource($this->response);
      }
    }
  }
}
