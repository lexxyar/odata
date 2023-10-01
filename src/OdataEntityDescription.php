<?php


namespace Lexxsoft\Odata;


use Lexxsoft\Odata\Exceptions\OdataModelIsNotRestableException;
use Lexxsoft\Odata\Exceptions\OdataModelNotExistException;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OdataEntityDescription
 */
class OdataEntityDescription
{
    private string $filepath;
    private string $namespace;
    private string $entityName;
    private string $modelName;
    private Model $oModel;

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getFields(): array
    {
        return $this->oModel->getFields();
    }

    public function getModel(): \Illuminate\Database\Eloquent\Model
    {
        return $this->oModel;
    }

    /**
     * OdataEntityDescription constructor.
     * @throws OdataModelIsNotRestableException
     * @throws OdataModelNotExistException
     */
    public function __construct(string $filePath, string $namespace = '')
    {
        $this->filepath = $filePath;
        $aPathFile = explode(DIRECTORY_SEPARATOR, substr($this->filepath, 0, -4));
        $this->namespace = ($namespace ? $namespace : 'App') . '\\Models';
        $modelNamespace = $this->namespace . '\\';
        $sModelFileName = array_pop($aPathFile);
        $this->modelName = $modelNamespace . $sModelFileName;
        $this->entityName = strtolower($sModelFileName);
        $this->oModel = $this->checkModel($this->modelName);
    }

    /**
     * Имя ключевого поля
     */
    public function getKeyField(): string
    {
        return $this->oModel->getKeyName();
    }

    /**
     * Путь к файлу
     */
    public function getFilepath(): string
    {
        return $this->filepath;
    }

    /**
     * Нэймспэйс
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

    protected function checkModel($modelName): Model
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
}
