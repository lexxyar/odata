<?php

namespace Lexxsoft\Odata\Contracts;

enum OdataMessageType: string
{
    case Warning = 'warning';
    case Error = 'error';
    case Success = 'success';
    case Message = 'message';
}
