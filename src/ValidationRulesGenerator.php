<?php

namespace Lexxsoft\Odata;

use Illuminate\Validation\Rule;

class ValidationRulesGenerator
{
    /**
     * @param array<OdataFieldDescription> $fieldDescriptionList
     * @param bool $sometimes
     * @param mixed|null $ignoreId
     * @return array
     */
    public static function generate(array $fieldDescriptionList, bool $sometimes = false, mixed $ignoreId = null): array
    {
        $rules = [];
        $primaryKeyFieldName = 'id';
        /** @var OdataFieldDescription $fieldDesc */
        foreach ($fieldDescriptionList as $fieldDesc) {
            if ($fieldDesc->isPrimary()) {
                $primaryKeyFieldName = $fieldDesc->getName();
            }
        }
        /** @var OdataFieldDescription $fieldDesc */
        foreach ($fieldDescriptionList as $fieldDesc) {
            $rule = [];
            if (!$fieldDesc->isNullable()) $rule[] = 'required';
            if ($fieldDesc->getEdmType() == 'Edm.String') {
                if ($fieldDesc->getSize() > 0) $rule[] = 'max:' . $fieldDesc->getSize();
                if (!$fieldDesc->isNullable()) $rule[] = 'min:1';
            }
            if ($fieldDesc->isUnique()) {
                $uniqueRule = Rule::unique($fieldDesc->getTable(), $fieldDesc->getName());
                if (!$ignoreId) {
                    $uniqueRule->ignore($primaryKeyFieldName, $ignoreId);
                }
                $rule[] = $uniqueRule;
            }
            if ($sometimes) {
                $rule[] = 'sometimes';
            }
            if (sizeof($rule) > 0) $rules[$fieldDesc->getName()] = $rule;
        }
        return $rules;
    }
}
