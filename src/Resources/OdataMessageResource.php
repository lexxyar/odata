<?php

namespace Lexxsoft\Odata\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Lexxsoft\Odata\Contracts\OdataMessageType;

class OdataMessageResource extends JsonResource
{
    public static $wrap = null;
    protected array $details = [];

    public function __construct(protected string           $message = '',
                                protected string           $target = '',
                                protected string|int       $code = 0,
                                protected OdataMessageType $wrapper = OdataMessageType::Error)
    {
        parent::__construct([]);
    }

    public function message(string $message = ''): self
    {
        $this->message = $message;
        return $this;
    }

    public function target(string $target = ''): self
    {
        $this->target = $target;
        return $this;
    }

    public function code(string|int $code = 400): self
    {
        $this->code = $code;
        return $this;
    }

    public function fromValidation(array $value): self
    {
        foreach ($value as $field => $messages) {
            foreach ($messages as $message) {
                $this->details[] = ['code' => 0, 'target' => $field, 'message' => $message, 'severity' => OdataMessageType::Error->value];
            }
        }
        return $this;
    }

    public function detailMessage(string $value, OdataMessageType $severity = OdataMessageType::Error, string|int $code = 0, string $target = ''): self
    {
        $this->details[] = ['code' => $code, 'target' => $target, 'message' => $value, 'severity' => $severity->value];
        return $this;
    }

    public function error(string $value, string|int $code = 0, string $target = ''): self
    {
        return $this->detailMessage($value, OdataMessageType::Error, $code, $target);
    }

    public function success(string $value, string|int $code = 0, string $target = ''): self
    {
        return $this->detailMessage($value, OdataMessageType::Success, $code, $target);
    }

    public function warning(string $value, string|int $code = 0, string $target = ''): self
    {
        return $this->detailMessage($value, OdataMessageType::Warning, $code, $target);
    }

    public function asError(): self
    {
        $this->wrapper = OdataMessageType::Error;
        return $this;
    }

    public function asSuccess(): self
    {
        $this->wrapper = OdataMessageType::Success;
        return $this;
    }

    public function asWarning(): self
    {
        $this->wrapper = OdataMessageType::Warning;
        return $this;
    }

    public function asMessage(): self
    {
        $this->wrapper = OdataMessageType::Message;
        return $this;
    }

    public function fromException(\Exception $ex):self
    {
        $this->message($ex->getMessage())
            ->code($ex->getCode())
            ->asError();
        return $this;
    }

    public function toArray(Request $request): array
    {
        $wrap = $this->wrapper->value;
        return [
            "$wrap" => [
                'code' => $this->code,
                'message' => $this->message,
                'target' => $this->target,
                'severity' => $this->wrapper->value,
                "details" => $this->details,
                'innererror' => [
                    'trace' => [],
                    'context' => [],
                ]
            ]
        ];
    }
}
