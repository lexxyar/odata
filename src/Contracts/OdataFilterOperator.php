<?php

namespace Lexxsoft\Odata\Contracts;

enum OdataFilterOperator: string
{
    case EQ = 'EQ';
    case GT = 'GT';
    case GE = 'GE';
    case LT = 'LT';
    case LE = 'LE';
    case CP = 'LIKE';
    case NE = 'NE';
    case SUBSTRINGOF = 'substringof';
    case CONTAINS = 'contains';
    case ENDSWITH = 'endswith';
    case STARTSWITH = 'startswith';
}
