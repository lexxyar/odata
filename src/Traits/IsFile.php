<?php

namespace LexxSoft\odata\Primitives;

/**
 * Trait IsFile
 *
 * Применяется к модели для обозначения ее, как модель с файлами
 *
 * @package LexxSoft\odata\Primitives
 */
trait IsFile
{

  /**
   * @var bool
   */
  public $isFile = true;

  /**
   * Функция генерации имени файла
   *
   * Генерирует имя файла на основании ключа. Если ключ не передан,
   * то он будет взят из ключевого поля таблицы
   *
   * @param null $key Ключ
   * @return string Имя файла
   */
  public function getFilename($key = null)
  {
    if ($key === null) {
      $keyField = $this->getKeyName();
      $key = $this->$keyField;
    }

    return 'cust_' . md5($this->getTable().'_'.$key);
  }
}
