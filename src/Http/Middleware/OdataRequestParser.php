<?php

namespace Lexxsoft\Odata\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Lexxsoft\Odata\OdataRequest;

class OdataRequestParser
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse|JsonResponse
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $odataRequest = OdataRequest::getInstance();
        $request->oData = $odataRequest;
        return $next($request);
    }
}
