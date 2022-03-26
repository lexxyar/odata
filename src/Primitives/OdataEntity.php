<?php


namespace LexxSoft\odata\Primitives;

use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use LexxSoft\odata\Exceptions\OdataModelIsNotRestableException;
use LexxSoft\odata\Exceptions\OdataModelNotExistException;
use LexxSoft\odata\Exceptions\OdataTryCallControllerException;
use LexxSoft\odata\Http\OdataFilter;
use LexxSoft\odata\Http\OdataOrder;
use LexxSoft\odata\Http\OdataRequest;
use Illuminate\Database\Eloquent\Model;
use LexxSoft\odata\Resources\ODataDefaultResource;
use stdClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
   * Возвращает имя сущности
   * @return string
   */
  public function getEntityName(): string
  {
    return $this->entityName;
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

    // This is metadata
    if ($this->entityName == urlencode('$metadata') || $this->entityName == '$metadata') {
      $this->isMetadata = true;
    } else {
      $modelDescription = OdataService::getEntityModelByEntityName($entityName);
      $this->isList = $key === null;
      $this->key = $key;
      $modelNamespace = $modelDescription->getNamespace() . '\\';
      $pathParts = explode('\\', $modelNamespace);
      array_pop($pathParts); // Сначала удалим последний элемент (он пустой)
      array_pop($pathParts); // Удаляем `Models`
      $controllerNamespace = implode('\\', $pathParts) . '\\Http\\Controllers\\';
      $controllerSubfolder = env('controller_subfolder', '');
      if($controllerSubfolder){
        $controllerNamespace = $controllerNamespace.$controllerSubfolder.'\\';
      }
      $this->modelName = $modelNamespace . ucfirst(strtolower($this->entityName));
      $this->controllerName = $controllerNamespace . ucfirst(strtolower($this->entityName)) . 'Controller';
      $this->methodName = '';

      $data = json_decode(request()->getContent(), true);
      $isMass = $this->isMultidimensional($data);

      // Определяем имя метода относительно типа зароса
      if (request()->getMethod() === 'POST') {
        $this->methodName = $isMass ? 'CreateMassEntity' : 'CreateEntity';
        if (request()->files->count() > 0) {
          $this->methodName = 'UploadFile';
        }
      } elseif (request()->getMethod() === 'PUT') {
        $this->methodName = $isMass ? 'UpdateMassEntity' : 'UpdateEntity';
      } elseif (request()->getMethod() === 'PATCH') {
        $this->methodName = $isMass ? 'PatchMassEntity' : 'PatchEntity';
      } elseif (request()->getMethod() === 'DELETE') {
        $this->methodName = $isMass ? 'DeleteMassEntity' : 'DeleteEntity';
      } elseif (request()->getMethod() === 'GET') {
        $this->methodName = $this->isList ? 'GetEntitySet' : 'GetEntity';
      }
//      $this->oModel = self::checkModel($this->modelName);
    }
  }

  /**
   * Проверяет, имеет ли массив мультивложенность
   * @param $array
   * @return bool
   */
  private function isMultidimensional($array)
  {
    return is_array($array) && isset($array[0]) && is_array($array[0]);
//    return count($array) !== count($array, COUNT_RECURSIVE);
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

    if ($this->methodName == 'CreateMassEntity') {
      return $this->dynamicCreateMassData();
    }

    if ($this->methodName == 'UpdateEntity') {
      return $this->dynamicUpdateData();
    }

    if ($this->methodName == 'UpdateMassEntity') {
      return $this->dynamicUpdateMassData();
    }

    if ($this->methodName == 'PatchEntity') {
      return $this->dynamicPatchData();
    }

    if ($this->methodName == 'PatchMassEntity') {
      return $this->dynamicPatchMassData();
    }

    if ($this->methodName == 'DeleteEntity') {
      return $this->dynamicDeleteData();
    }

    if ($this->methodName == 'DeleteMassEntity') {
      return $this->dynamicDeleteMassData();
    }

    if ($this->methodName == 'UploadFile') {
      return $this->dynamicUploadFile();
    }
    return [];
  }

  /**
   * Динамическое чтение данных
   * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model|Model[]|object|null
   */
  private function dynamicReadData()
  {
    $this->oModel = self::checkModel($this->modelName);
    $queryBuilder = $this->oModel->newModelQuery();

    // Check on SoftDelete
    if (in_array(SoftDeletes::class, class_uses($this->oModel))) {
      if (OdataRequest::getInstance()->force) {
//        $queryBuilder->withTrashed();
      } else {
        $queryBuilder->whereNull('deleted_at');
      }
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

    // select
    if (sizeof(OdataRequest::getInstance()->select) > 0) {
      $queryBuilder->select(OdataRequest::getInstance()->select);
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
   * @param array|null $data
   * @return Model
   * @noinspection PhpUndefinedMethodInspection
   */
  private function dynamicCreateData($data = null)
  {
    if ($data === null) {
      $data = json_decode(request()->getContent(), true);
    }
    $this->oModel = self::checkModel($this->modelName);
    $keyField = $this->oModel->getKeyName();

    if (isset($data->password)) {
      $data->password = Hash::make($data->password);
    }

    // sync data
    $aRelated = $this->extractRelationsFromInputData($data);

    unset($data->$keyField);
    $this->oModel->fill($data);
//    $isValid = $this->oModel->validateObject();

    $isValid = true;
    $aRules = $this->oModel->validationRules ? $this->oModel->validationRules : [];
    if($aRules) {
      $this->substituteValidationParameters($aRules);

      $oValidator = Validator::make($data, $aRules);
      $isValid = $oValidator->errors()->count() == 0;
    }
    if ($isValid) {
      $this->oModel = $this->oModel->create($this->oModel->toArray());

      // Sync pivot table
      $this->syncRelations($this->oModel, $aRelated);
    } else {
      throw new Exception('json:' . $oValidator->errors()->toJson());
    }

    return $this->oModel;
  }

  /**
   * Динамическое создание массива объектов
   *
   * @param null $data Массив данных. Если не указан, то возьмется контент запроса
   * @return array Вернет массив созданных значений
   * @throws Exception
   */
  private function dynamicCreateMassData($data = null)
  {
    if ($data === null) {
      $data = json_decode(request()->getContent(), true);
    }

    $aResponse = [];

    $this->oModel = self::checkModel($this->modelName);

    try {
      foreach ($data as $item) {
        $oModel = $this->dynamicCreateData($item);
        $aResponse[] = $oModel;
      }
    } catch (Exception $exception) {
      throw $exception;
    }

    return $aResponse;
  }

  /**
   * Динамическое обновление данных
   * @param array|null $data
   * @return Model
   * @throws Exception
   */
  private function dynamicUpdateData($data = null)
  {
    if ($data === null) {
      $data = json_decode(request()->getContent(), true);
    }
    $this->oModel = self::checkModel($this->modelName);
    $keyField = $this->oModel->getKeyName();
    $id = array_key_exists($keyField, $data) ? $data[$keyField] : $this->key;
    $find = $this->oModel->findOrFail($id);

    // check relation fields
    $aRelated = $this->extractRelationsFromInputData($data);

    $this->oModel->fill($data);

    $isValid = true;
    $aRules = $this->oModel->validationRules;
    if($aRules) {
      $this->substituteValidationParameters($aRules);

      $oValidator = Validator::make($data, $aRules);
      $isValid = $oValidator->errors()->count() == 0;
    }
    if ($isValid) {
      foreach ($data as $field => $value) {
        if ($field == 'password') {
          $value = Hash::make($value);
        }
        if (in_array($field, array_keys($find->attributesToArray())) && $find->$field !== $value) {
          $find->$field = $value;
        }
      }
      $find->save();

      // Sync pivot table
      $this->syncRelations($find, $aRelated);

      $this->oModel = $find;
    } else {
      throw new Exception('json:' . $oValidator->errors()->toJson());
    }

    return $this->oModel;
  }

  /**
   * Динамическое обновление массива объектов
   *
   * @param null $data Массив данных. Если не указан, то возьмется контент запроса
   * @return array Вернет массив обновленных значений
   * @throws Exception
   */
  private function dynamicUpdateMassData($data = null)
  {
    if ($data === null) {
      $data = json_decode(request()->getContent(), true);
    }

    $aResponse = [];

    try {
      foreach ($data as $item) {
        $oModel = $this->dynamicUpdateData($item);
        $aResponse[] = $oModel;
      }
    } catch (Exception $exception) {
      throw $exception;
    }

    return $aResponse;
  }

  /**
   * Динамическое обновление данных
   * Обновляет только переданныей поля
   *
   * @param array|null $data
   * @return Model
   * @throws Exception
   *
   * @since 0.9.0
   */
  private function dynamicPatchData($data = null)
  {
    if ($data === null) {
      $data = json_decode(request()->getContent(), true);
    }
    $this->oModel = self::checkModel($this->modelName);
    $keyField = $this->oModel->getKeyName();
    $id = array_key_exists($keyField, $data) ? $data[$keyField] : $this->key;
    $find = $this->oModel->findOrFail($id);

    // check relation fields
    $aRelated = $this->extractRelationsFromInputData($data);

    $this->oModel->fill($find->toArray());
    $this->oModel->fill($data);

    $isValid = true;
    $aRules = $this->oModel->validationRules;
    foreach ($aRules as $field=>$foo) {
      if(!array_key_exists($field, $data)){
        unset($aRules[$field]);
      }
    }
    if($aRules) {
      $this->substituteValidationParameters($aRules);

      $oValidator = Validator::make($this->oModel->toArray(), $aRules);
      $isValid = $oValidator->errors()->count() == 0;
    }
    if ($isValid) {
      foreach ($data as $field => $value) {
        if ($field == 'password') {
          $value = Hash::make($value);
        }
        if (in_array($field, array_keys($find->attributesToArray())) && $find->$field !== $value) {
          $find->$field = $value;
        }
      }
      $find->save();

      // Sync pivot table
      $this->syncRelations($find, $aRelated);

      $this->oModel = $find;
    } else {
      throw new Exception('json:' . $oValidator->errors()->toJson());
    }

    return $this->oModel;
  }

  /**
   * Динамическое обновление массива объектов
   * Обновляет только переданныей поля
   *
   * @param null $data Массив данных. Если не указан, то возьмется контент запроса
   * @return array Вернет массив обновленных значений
   * @throws Exception
   *
   * @since 0.9.0
   */
  private function dynamicPatchMassData($data = null)
  {
    if ($data === null) {
      $data = json_decode(request()->getContent(), true);
    }

    $aResponse = [];

    try {
      foreach ($data as $item) {
        $oModel = $this->dynamicPatchData($item);
        $aResponse[] = $oModel;
      }
    } catch (Exception $exception) {
      throw $exception;
    }

    return $aResponse;
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

    $this->oModel = self::checkModel($this->modelName);
    if (OdataRequest::getInstance()->force) {
      $this->oModel->where($this->oModel->getKeyName(), '=', $this->key)->forceDelete();
    } else {
      $this->oModel->where($this->oModel->getKeyName(), '=', $this->key)->delete();
    }

    if (isset($this->oModel->isFile) && $this->oModel->isFile === true) {
      $config = Config::get('odata');
      Storage::delete($config['upload_dir'] . '/' . $this->oModel->getFilename($this->key));
    }

    $res = new stdClass();
    $res->id = $this->key;
    $res->status = 'success';
    return get_object_vars($res);
  }

  /**
   * Динамическое удаление массива объектов
   *
   * @param null $data Массив данных. Если не указан, то возьмется контент запроса
   * @return array Вернет массив значений
   * @throws Exception
   */
  private function dynamicDeleteMassData($data = null)
  {
    if ($data === null) {
      $data = json_decode(request()->getContent(), true);
    }

    $aResponse = [];

    try {
      foreach ($data as $item) {
        $oModel = $this->dynamicUpdateData($item);
        $aResponse[] = $oModel;
      }
    } catch (Exception $exception) {
      throw $exception;
    }

    return $aResponse;
  }


  /**
   * Заменяет параметры вида ${id} в правилах валидации на значение в поделе данных
   *
   * @param array $aRules Правила валидации
   */
  private function substituteValidationParameters(array &$aRules)
  {
    array_walk($aRules, function (&$item, $key) {
      // Разбиваем строку на массив
      $rules = is_array($item) ? $item : explode('|', $item);
      array_walk($rules, function (&$rule, $index) {
        $rp = \Illuminate\Validation\ValidationRuleParser::parse($rule);

        // Проверяем, есть ли условия
        if (isset($rp[1]) && sizeof($rp[1]) > 0) {
          $cond = $rp[1];

          // Ищем параметры и заменяем на значения из модели
          array_walk($cond, function (&$itm, $idx) {
            if (str_starts_with($itm, '${')) {
              $field = substr($itm, 2, strlen($itm) - strlen('${') - 1);
              $itm = $this->oModel[$field];
            }
          });

          // Склеиваем условия в строку через запятую
          $rp[1] = implode(',', $cond);
        }

        if (is_array($rp[1])) {
          // Удалим пустые массивы условий
          if (sizeof($rp[1]) == 0) {
            unset($rp[1]);
          } // Остальные склеим через запятую
          else {
            $rp[1] = implode(',', $cond);
          }
        }

        // Составим строку правила, склеив с условиями через двоеточие
        $rule = implode(':', $rp);
      });
      $item = $rules;
    });
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
    $sql = vsprintf(str_replace('?', "'%s'", $addSlashes), $oBuilder->getBindings());
    dump($oBuilder->toSql(), $oBuilder->getBindings(), $sql);
  }

  /**
   * Синхронизация данных для связей ManyToMany
   * С версии 0.8.0 учитывает параметр запроса `_attach`
   * @param Model $find
   * @param array $aRelated
   */
  public function syncRelations(&$find, array $aRelated)
  {
    // Sync pivot table
    foreach ($aRelated as $key => $relation) {
      $syncField = ucfirst($relation->field);
      if (in_array($relation->field, OdataRequest::getInstance()->attach)) {
        call_user_func([$find, $syncField])->attach($relation->values);
      } else {
        call_user_func([$find, $syncField])->sync($relation->values);
      }
    }
  }

  /**
   * Динамическая загрузка файла
   *
   * @return array
   */
  private function dynamicUploadFile()
  {
    foreach (request()->files->all() as $inputName => $file) {
      if ($file instanceof UploadedFile) {
        $tmp = explode('.', $file->getClientOriginalName());
        array_pop($tmp);
        $fileTitle = implode('.', $tmp);

        $data = [];
        $data['name'] = $fileTitle;
        $data['ext'] = $file->getClientOriginalExtension();;
        $data['mime'] = $file->getClientMimeType();
        $data['size'] = $file->getSize();

        $request = request()->request->all();
        unset($request['name'], $request['ext'], $request['mime'], $request['size']);
        $data = array_merge($data, $request);

        if (!$this->key) {
          $oDbObj = $this->dynamicCreateData($data);
        } else {
          $oDbObj = $this->dynamicUpdateData($data);
        }

        $config = Config::get('odata');
        request()->file($inputName)->storeAs($config['upload_dir'], $oDbObj->isFile ? $oDbObj->getFilename() : 'cust_' . $oDbObj->getTable() . '_' . $oDbObj->id);
      }
    }

    $res = new stdClass();
    $res->status = 'success';
    return get_object_vars($res);
  }
}
