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
    $entityValue = OdataRequest::getInstance()->requestPathParts[0];

    // Достаем имя сущности и ключ
    $re = '/(?<entity>.[^(]+)(?<containKey>\(?(?<key>.+)\))?/m';
    preg_match_all($re, $entityValue, $matches, PREG_SET_ORDER, 0);
    $entityKey = isset($matches[0]['key']) ? $matches[0]['key'] : null;

    try {
      // Создаем экземпляр REST сущности
      $restEntity = new OdataEntity($matches[0]['entity'], $entityKey);
    } catch (\Exception $e) {
      $this->error = $e;
      throw $e;
    }

    $obj = $restEntity->callDynamic();

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
}
