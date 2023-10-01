<?php


namespace Lexxsoft\Odata;

/**
 * Class OdataRelationship
 */
class OdataRelationship
{
    public string $name;
    public mixed $type;
    public mixed $model;
    public mixed $foreignKey;
    public mixed $ownerKey;
    public string $fkName;
    public string $snakeName;

    public function __construct(array $relationship = [])
    {
        if ($relationship) {
            $fields = ['name', 'type', 'model', 'foreignKey', 'ownerKey', 'fkName', 'snakeName'];
            foreach ($fields as $field) {
                $this->$field = $relationship[$field];
            }
        }
    }
}
