<?php

namespace LexxSoft\odata\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    public static $wrap = 'error';
    /**
     * @var \Exception
     */
    private $error;

    /**
     * ErrorResource constructor.
     * @param \Exception $error
     */
    public function __construct(\Exception $error)
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
