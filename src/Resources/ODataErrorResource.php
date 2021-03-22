<?php

namespace LexxSoft\odata\Resources;

use Exception;
use Illuminate\Http\Resources\Json\JsonResource;

class ODataErrorResource extends JsonResource
{
    public static $wrap = 'error';
    /**
     * @var Exception
     */
    private $error;

    /**
     * ODataErrorResource constructor.
     * @param Exception $error
     */
    public function __construct(Exception $error)
    {
        $this->error = $error;
        parent::__construct([]);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request = null)
    {
        $a['code'] = $this->error->getCode();
        $a['message'] = $this->error->getMessage();
        return $a;
    }
}
