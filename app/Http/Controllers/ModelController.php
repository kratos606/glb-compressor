<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; // Ensure you have this use statement
use Illuminate\Http\Request;
use App\Models\GLBModel;
use Illuminate\Support\Facades\Storage;
use App\Services\GLBModelOptimizationService; // Import the service

class ModelController extends Controller
{
    /**
     * @var GLBModelOptimizationService
     */
    protected $glbOptimizationService; // Correct property name and typehint

    public function __construct(GLBModelOptimizationService $glbOptimizationService) // Correct constructor injection
    {
        $this->glbOptimizationService = $glbOptimizationService; // Assign injected service to the property
    }

    public function upload(Request $request)
    {
        $request->validate([
            'modelFile' => 'required|file|max:51200', // 50MB max
        ]);

        $uploadedFile = $request->file('modelFile');
        $originalPath = $uploadedFile->store('public/models');

        $optimizedPath = null;
        try {
            $optimizedPath = $this->glbOptimizationService->optimizeGLBModel($originalPath); // Use the correct property name (lowercase 'g')
        } catch (\Exception $e) {
            // Handle optimization error (log, return error response, etc.)
            // For now, we'll just log the error and proceed with the original path
            \Log::error('GLB Optimization Error: ' . $e->getMessage());
            $optimizedPath = $originalPath; // Fallback to original path if optimization fails
        }

        $model = GLBModel::create([
            'name' => $uploadedFile->getClientOriginalName(),
            'path' => $optimizedPath,
            'size' => $uploadedFile->getSize(),
            'type' => $uploadedFile->getMimeType(),
        ]);

        return response()->json([
            'message' => 'Model uploaded successfully',
            'model' => $model,
            'optimized_path' => $optimizedPath, // Return optimized path in response
        ], 201);
    }

    public function download($id)
    {
        $model = GLBModel::findOrFail($id);
        return Storage::download($model->path, $model->name);
    }
}