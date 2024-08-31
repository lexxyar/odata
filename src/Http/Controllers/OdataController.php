<?php

namespace Lexxsoft\Odata\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Lexxsoft\Odata\Services\Builders\OdataDeleteBatchBuilder;
use Lexxsoft\Odata\Services\Builders\OdataDeleteBuilder;
use Lexxsoft\Odata\Services\Builders\OdataGetBuilder;
use Lexxsoft\Odata\Services\Builders\OdataPostBuilder;
use Lexxsoft\Odata\Services\Builders\OdataPutBuilder;

class OdataController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected string $odataModel = '';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        return OdataGetBuilder::make()
            ->model($this->odataModel)
            ->get();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): \Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        return OdataGetBuilder::make()
            ->model($this->odataModel)
            ->id($id)
            ->get();
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        return OdataPostBuilder::make()
            ->model($this->odataModel)
            ->post();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        return OdataPutBuilder::make()
            ->model($this->odataModel)
            ->id($id)
            ->put();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        return OdataDeleteBuilder::make()
            ->model($this->odataModel)
            ->id($id)
            ->delete();
    }

    public function destroyBatch(Request $request): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        return OdataDeleteBatchBuilder::make()
            ->model($this->odataModel)
            ->validationRules(['ids' => 'required|array'])
            ->delete();
    }
}
