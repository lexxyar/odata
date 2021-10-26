<?php


namespace LexxSoft\odata\Primitives;


/**
 * Class OdataEntityDescription
 * @package LexxSoft\odata\Primitives
 */
class OdataEntityDescription
{
  /**
   * @var string
   */
  private $filepath;

  /**
   * @var string
   *
   * @since 0.7.0
   */
  private $namespace;

  /**
   * @var string
   */
  private $entityName;

  /**
   * @var string
   */
  private $modelName;

  /**
   * @var \Illuminate\Database\Eloquent\Model
   */
  private $oModel;

  /**
   * @return string
   */
  public function getEntityName(): string
  {
    return $this->entityName;
  }

  /**
   * @return array
   */
  public function getFields(): array
  {
    return $this->oModel->getFields();
  }

  /**
   * @return \Illuminate\Database\Eloquent\Model
   */
  public function getModel(): \Illuminate\Database\Eloquent\Model
  {
    return $this->oModel;
  }

  /**
   * OdataEntityDescription constructor.
   * @param string $filePath
   * @throws \LexxSoft\odata\Exceptions\OdataModelIsNotRestableException
   * @throws \LexxSoft\odata\Exceptions\OdataModelNotExistException
   */
  public function __construct($filePath, $namespace = '')
  {
    $this->filepath = $filePath;
    $aPathFile = explode(DIRECTORY_SEPARATOR, substr($this->filepath, 0, -4));
    $this->namespace = ($namespace ? $namespace : 'App') . '\\Models';
    $modelNamespace = $this->namespace . '\\';
    $sModelFileName = array_pop($aPathFile);
    $this->modelName = $modelNamespace . $sModelFileName;
    $this->entityName = strtolower($sModelFileName);
    $this->oModel = OdataEntity::checkModel($this->modelName);
  }

  /**
   * Имя ключевого поля
   * @return string
   */
  public function getKeyField(): string
  {
    return $this->oModel->getKeyName();
  }

  /**
   * Путь к файлу
   * @return string
   * @since 0.7.0
   */
  public function getFilepath(): string
  {
    return $this->filepath;
  }

  /**
   * Нэймспэйс
   * @return string
   * @since 0.7.0
   */
  public function getNamespace(): string
  {
    return $this->namespace;
  }



//    /**
//     * Составляет описание полей на основе БД
//     */
//    private function describeFields()
//    {
//        $raw = DB::select(DB::raw("DESCRIBE " . $this->oModel->getTable()));
//        $this->fields = [];
//        foreach ($raw as $field){
//            $this->fields[] = new OdataFieldDescription($field);
//        }
//    }
}
