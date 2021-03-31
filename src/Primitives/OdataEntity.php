<?php


namespace LexxSoft\odata\Primitives;

use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use LexxSoft\odata\Exceptions\OdataModelIsNotRestableException;
use LexxSoft\odata\Exceptions\OdataModelNotExistException;
use LexxSoft\odata\Exceptions\OdataTryCallControllerException;
use LexxSoft\odata\Http\OdataFilter;
use LexxSoft\odata\Http\OdataOrder;
use LexxSoft\odata\Http\OdataRequest;
use Illuminate\Database\Eloquent\Model;
use stdClass;

/**
 * Class OdataEntity
 * @package LexxSoft\odata\Primitives
 */
class OdataEntity
{
  /**
   * @var string
   */
  private $entityName;

  /**
   * @var bool
   */
  private $isList = false;

  /**
   * @var string
   */
  private $modelName;

  /**
   * @var string
   */
  private $controllerName;

  /**
   * @var string
   */
  private $methodName;

  /**
   * @var mixed|null
   */
  private $key;

  /**
   * @var bool
   */
  private $isMetadata = false;

  /**
   * @var Model
   */
  private $oModel;

  /**
   * Возвращает экземпляр класса модели
   * @return Model
   */
  public function getModel(): Model
  {
    return $this->oModel;
  }

  /**
   * OdataEntity constructor.
   * @param string $entityName
   * @param mixed $key
   * @throws OdataModelIsNotRestableException
   * @throws OdataModelNotExistException
   */
  public function __construct($entityName, $key = null)
  {
    $this->entityName = $entityName;
    $this->isList = $key === null;
    $this->key = $key;
    $modelNamespace = 'App\\Models\\';
    $controllerNamespace = 'App\\Http\\Controllers\\Api\\';
    $this->modelName = $modelNamespace . ucfirst(strtolower($this->entityName));
    $this->controllerName = $controllerNamespace . ucfirst(strtolower($this->entityName)) . 'Controller';
    $this->methodName = '';

    // Определяем имя метода относительно типа зароса
    if (request()->getMethod() === 'POST') {
      $this->methodName = 'CreateEntity';
    } elseif (request()->getMethod() === 'PUT') {
      $this->methodName = 'UpdateEntity';
    } elseif (request()->getMethod() === 'DELETE') {
      $this->methodName = 'DeleteEntity';
    } elseif (request()->getMethod() === 'GET') {
      $this->methodName = $this->isList ? 'GetEntitySet' : 'GetEntity';
    }

    // This is metadata
    if ($this->entityName == urlencode('$metadata')) {
      $this->isMetadata = true;
    } else {
      $this->oModel = self::checkModel($this->modelName);
    }
  }

  /**
   * Если запрос метаданных
   * @return bool
   */
  public function isMetadata(): bool
  {
    return $this->isMetadata;
  }

  /**
   * Проверка модели на REST сущность
   * @throws OdataModelIsNotRestableException
   * @throws OdataModelNotExistException
   */
  public static function checkModel($modelName): Model
  {
    if (!class_exists($modelName)) {
      throw new OdataModelNotExistException($modelName);
    }

    $model = new $modelName;
    if (!property_exists($model, 'isRestModel')) {
      throw new OdataModelIsNotRestableException($modelName);
    }

    if (!$model->isRestModel) {
      throw new OdataModelIsNotRestableException($modelName);
    }

    return $model;
  }

  /**
   * Проверка существования контроллера и наличия в нем соответствующего метода
   * @return bool
   */
  public function isController(): bool
  {
    if (!class_exists($this->controllerName)) {
      return false;
    }

    $controller = new $this->controllerName;
    if (!method_exists($controller, $this->methodName)) {
      return false;
    }

    return true;
  }

  /**
   * Вызов метода контроллера
   * @return mixed
   * @throws OdataTryCallControllerException
   */
  public function callControllerMethod()
  {
    if (!$this->isController()) {
      throw new OdataTryCallControllerException($this->controllerName, $this->methodName);
    }

    $controller = new $this->controllerName;
    return call_user_func_array([$controller, $this->methodName], []);
  }

  /**
   * Динамический вызов цепочки работы с данными
   * @return array|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model|Model[]|object|null
   * @throws Exception
   */
  public function callDynamic()
  {
    if ($this->methodName == 'GetEntitySet' || $this->methodName == 'GetEntity') {
      return $this->dynamicReadData();
    }

    if ($this->methodName == 'CreateEntity') {
      return $this->dynamicCreateData();
    }

    if ($this->methodName == 'UpdateEntity') {
      return $this->dynamicUpdateData();
    }

    if ($this->methodName == 'DeleteEntity') {
      return $this->dynamicDeleteData();
    }
    return [];
  }

  /**
   * Динамическое чтение данных
   * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model|Model[]|object|null
   */
  private function dynamicReadData()
  {
    $queryBuilder = $this->oModel->newModelQuery();

    // Check on SoftDelete
    if (in_array(SoftDeletes::class, class_uses($this->oModel))) {
      $queryBuilder->whereNull('deleted_at');
    }

    if ($this->key !== null) {
      $queryBuilder->where($this->oModel->getKeyName(), '=', $this->key);
    } else {
      if (sizeOf(OdataRequest::getInstance()->filter) > 0) {
        $aFilterParts = [];
        foreach (OdataRequest::getInstance()->filter as $oFilter) {
          if ($oFilter instanceof OdataFilter) {
            if ($oFilter->sValue !== 'null') {
              $aFilterParts[] = $oFilter->toArray();
            }
          }
        }
        $queryBuilder->where($aFilterParts);
      }
    }

    if (sizeof(OdataRequest::getInstance()->order) > 0) {
      foreach (OdataRequest::getInstance()->order as $order) {
        if ($order instanceof OdataOrder) {
          $queryBuilder->orderBy($order->sFieldname, $order->sDirection);
        }
      }
    }

    if (sizeof(OdataRequest::getInstance()->expand) > 0) {
      $queryBuilder->with(OdataRequest::getInstance()->expand);
    }

    if ($this->key !== null) {
      $result = $queryBuilder->first();
    } else {
      // $count
      if (OdataRequest::getInstance()->isCountRequested()) {
        $result = $queryBuilder->count();
      } else {
        // limit
        if (OdataRequest::getInstance()->limit > 0) {
          $queryBuilder->limit(OdataRequest::getInstance()->limit);
        }

        // offset
        if (OdataRequest::getInstance()->offset > 0) {
          $queryBuilder->offset(OdataRequest::getInstance()->offset);
        }

        $result = $queryBuilder->get();
      }
    }

    return $result;
  }

  /**
   * Динамическое создание данных
   * @return Model
   * @noinspection PhpUndefinedMethodInspection
   */
  private function dynamicCreateData()
  {
    $data = json_decode(request()->getContent(), true);
    $keyField = $this->oModel->getKeyName();

    // sync data
    $aRelated = $this->extractRelationsFromInputData($data);

    unset($data->$keyField);
    $this->oModel->fill($data);
    $isValid = $this->oModel->validateObject();
    if ($isValid) {
      $this->oModel = $this->oModel->create($this->oModel->toArray());

      // Sync pivot table
      $this->syncRelations($this->oModel, $aRelated);
    }

    return $this->oModel;
  }

  /**
   * Динамическое обновление данных
   * @return Model
   * @noinspection PhpUndefinedMethodInspection
   */
  private function dynamicUpdateData()
  {
    $data = json_decode(request()->getContent(), true);
    $keyField = $this->oModel->getKeyName();
    $id = array_key_exists($keyField, $data)?$data[$keyField]:$this->key;
    $find = $this->oModel->findOrFail($id);

    // check relation fields
    $aRelated = $this->extractRelationsFromInputData($data);


    $this->oModel->fill($data);
    $isValid = $this->oModel->validateObject();
    if ($isValid) {
      foreach ($data as $field => $value) {
        if (isset($find->$field) && $find->$field !== $value) {
          $find->$field = $value;
        }
      }
      $find->save();

      // Sync pivot table
      $this->syncRelations($find, $aRelated);

      $this->oModel = $find;
    }

    return $this->oModel;
  }

  /**
   * Динамическое удаление данных
   * @return array
   * @throws Exception
   * @noinspection PhpUndefinedMethodInspection
   */
  private function dynamicDeleteData()
  {
    if ($this->key === null) {
      throw new Exception('Key not set');
    }
    $this->oModel->where($this->oModel->getKeyName(), '=', $this->key)->delete();

    $res = new stdClass();
    $res->status = 'success';
    return get_object_vars($res);
  }

  /**
   * Генерация метаданных
   * @return string
   */
  public function generateMetadata()
  {
    return OdataService::createMetadataXml();
  }

  /**
   * Извлечение связей из данных
   * @param $data
   * @param Model|null $model
   * @return array
   * @noinspection PhpUndefinedMethodInspection
   */
  public function extractRelationsFromInputData(&$data, $model = null): array
  {
    $oModel = $model ? $model : $this->oModel;

    // check relation fields
    $aRelationship = $oModel->relationships();

    $aRelated = [];
    foreach ($aRelationship as $relationship) {
      if (!$relationship instanceof OdataRelationship) continue;
      if (array_key_exists($relationship->snakeName, $data)) {
        $related = new stdClass();
        $related->field = $relationship->name;
        $related->values = [];
        foreach ($data[$relationship->snakeName] as $datum) {
          $idSync = 0;
          $pivotSync = [];
          if (is_array($datum)) {
            foreach ($datum as $k => $v) {
              if ($k == $oModel->getKeyName()) {
                $idSync = $v;
              } else {
                $pivotSync[$k] = $v;
              }
            }
          } else {
            $idSync = $datum;
          }
          $related->values[$idSync] = $pivotSync;
        }
        unset($data[$relationship->snakeName]);
        $aRelated[$relationship->name] = $related;
      }
    }
    return $aRelated;
  }

  /**
   * Дамп SQL запроса
   * @param $oBuilder
   */
  public static function dumpSql($oBuilder)
  {
    $addSlashes = $oBuilder->toSql();
    $sql = vsprintf(str_replace(' ? ', ' % s', $addSlashes), $oBuilder->getBindings());
    dump($oBuilder->toSql(), $oBuilder->getBindings(), $sql);
  }

  /**
   * Синхронизация данных для связей ManyToMany
   * @param Model $find
   * @param array $aRelated
   */
  public function syncRelations(&$find, array $aRelated)
  {
    // Sync pivot table
    foreach ($aRelated as $key => $relation) {
      $syncField = ucfirst($relation->field);
      call_user_func([$find, $syncField])->sync($relation->values);
    }
  }
}
