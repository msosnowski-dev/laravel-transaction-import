<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportRequest;
use App\Http\Resources\ImportResource;
use App\Models\Import;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ImportController extends Controller
{
    public function __construct(protected ImportService $importService) {}

    public function index(): AnonymousResourceCollection
    {
        return ImportResource::collection(Import::latest('id')->paginate(15));
    }

    public function store(ImportRequest $request): JsonResponse
    {
        $result = $this->importService->importUploadedFile($request->file('file'));
        return (new ImportResource($result->import->load('importLogs')))->response()->setStatusCode(201);
    }

    public function show(int $id): ImportResource
    {
        return new ImportResource(Import::with('importLogs')->findOrFail($id));
    }
}
