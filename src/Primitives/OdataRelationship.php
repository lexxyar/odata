<?php


namespace LexxSoft\odata\Primitives;

/**
 * Class OdataRelationship
 * @package LexxSoft\odata\Primitives
 */
class OdataRelationship
{
  /**
   * @var string
   */
  public $name;

  /**
   * @var mixed
   */
  public $type;

  /**
   * @var mixed
   */
  public $model;

  /**
   * @var mixed
   */
  public $foreignKey;

  /**
   * @var mixed
   */
  public $ownerKey;

  /**
   * @var string
   */
  public $fkName;

  /**
   * @var string
   */
  public $snakeName;

  /**
   * OdataRelationship constructor.
   * @param array $relationship
   */
  public function __construct($relationship = [])
  {
    if ($relationship) {
      $this->name = $relationship['name'];
      $this->type = $relationship['type'];
      $this->model = $relationship['model'];
      $this->foreignKey = $relationship['foreignKey'];
      $this->ownerKey = $relationship['ownerKey'];
      $this->fkName = $relationship['fkName'];
      $this->snakeName = $relationship['snakeName'];
    }
  }
}
