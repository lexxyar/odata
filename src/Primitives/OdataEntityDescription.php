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
     */
    private $entityName;

    /**
     * @var string
     */
    private $modelName;

//    /**
//     * @var array
//     */
//    private $fields;

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
//        return $this->oModel->describeFields();
//        return $this->fields;
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
    public function __construct($filePath)
    {
        $this->filepath = $filePath;
        $aPathFile = explode('/', substr($this->filepath, 0, -4));
        $modelNamespace = 'App\\Models\\';
        $sModelFileName = array_pop($aPathFile);
        $this->modelName = $modelNamespace . $sModelFileName;
        $this->entityName = strtolower($sModelFileName);
        $this->oModel = OdataEntity::checkModel($this->modelName);

//        $this->describeFields();
//        $this->fields =
    }

    /**
     * Имя ключевого поля
     * @return string
     */
    public function getKeyField(): string
    {
        return $this->oModel->getKeyName();
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
