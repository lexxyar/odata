<?php

namespace Lexxsoft\Odata;

class OdataExpand
{
    private bool $_hasOptions = false;
    private string $_expandEntity = '';
    private bool $_count = false;
    private int $_top = -1;

    public function entity(): string
    {
        return $this->_expandEntity;
    }

    public function withCount(): bool
    {
        return $this->_count;
    }

    public function top(): int
    {
        return $this->_top;
    }

    public function __construct(string $rawValue)
    {
        $this->_hasOptions = $rawValue[-1] === ')';

        if ($this->_hasOptions) {
            $this->_expandEntity = explode('(', $rawValue)[0];

            $re = '/\((?<options>.*)\)/m';
            preg_match_all($re, $rawValue, $matches, PREG_SET_ORDER, 0);
            $optionsString = $matches[0]['options'];
            if (!empty(trim($optionsString))) {
                $optionsParts = explode(';', $optionsString);
                foreach ($optionsParts as $keyValue) {
                    if (empty(trim($keyValue))) continue;

                    [$key, $value] = explode('=', $keyValue, 2);

                    if (strtolower($key) === '$count' && strtolower($value) === 'true') {
                        $this->_count = true;
                    }
                    if (strtolower($key) === '$top') {
                        $this->_top = intval(strtolower($value));
                    }
                }
            }

        } else {
            $this->_expandEntity = $rawValue;
        }
    }

    public function __toString(): string
    {
        return $this->entity();
    }
}
