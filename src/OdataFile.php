<?php


namespace LexxSoft\odata;


use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;
use LexxSoft\odata\Http\OdataRequest;
use LexxSoft\odata\Primitives\OdataEntity;
use LexxSoft\odata\Resources\ODataDefaultResource;
use LexxSoft\odata\Resources\ODataErrorResource;

class OdataFile
{
  private $filename;
  private $clietnFilename;

  /**
   * @var Exception|null
   */
  private $error = null;


  public function __construct()
  {
//    $entityValue = OdataRequest::getInstance()->requestPathParts[0];
//
//    // Достаем имя сущности и ключ
//    $re = '/(?<entity>.[^(]+)(?<containKey>\(?(?<key>.+)\))?/m';
//    preg_match_all($re, $entityValue, $matches, PREG_SET_ORDER, 0);
//    $entityKey = isset($matches[0]['key']) ? $matches[0]['key'] : null;

    $entityName = OdataRequest::getInstance()->getEntityName();
    $entityKey = OdataRequest::getInstance()->getEntityKey();

    try {
      // Создаем экземпляр REST сущности
      $this->restEntity = new OdataEntity($entityName, $entityKey);
    } catch (\Exception $e) {
      $this->error = $e;
      throw $e;
    }

    $obj = $this->restEntity->callDynamic();

    if (isset($obj->isFile) && $obj->isFile === true) {
      $this->filename = $obj->getFilename($entityKey);
    }

    $config = \Illuminate\Support\Facades\Config::get('odata');
    if (!Storage::exists($config['upload_dir'].'/'.$this->filename)) {
      $this->error = 'File not found';
      throw new \Exception('File not found');
    }

    $this->clietnFilename = implode('.',[$obj->name, $obj->ext]);

  }

  /**
   * Ответ: скачивание файла
   *
   * @return ODataErrorResource|stream
   */
  public function response()
  {
    if ($this->error !== null) {
      return new ODataErrorResource($this->error);
    }

    $config = \Illuminate\Support\Facades\Config::get('odata');
    return Storage::download($config['upload_dir'].'/'.$this->filename, $this->clietnFilename);
  }

  /**
   * Ответ: Файл в кодировке Base64
   *
   * @return ODataErrorResource|stream
   */
  public function response64()
  {
    if ($this->error !== null) {
      return new ODataErrorResource($this->error);
    }

    $config = \Illuminate\Support\Facades\Config::get('odata');
    return base64_encode(Storage::get($config['upload_dir'].'/'.$this->filename, $this->clietnFilename));
  }
}
