<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process; // Correct use statement for Laravel Process Facade

class GLBModelOptimizationService
{
    /**
     * Optimizes a GLB model using gltf-transform optimize with Laravel Process Facade.
     *
     * @param string $inputPath Path to the input GLB model file (relative to storage/app/public/models)
     * @return string|null Path to the optimized GLB model file (relative to storage/app/public/optimized-models) or null on failure
     * @throws \Exception if gltf-transform is not installed or optimization fails
     */
    public function optimizeGLBModel(string $inputPath): ?string
    {
        $gltfTransformCommand = 'gltf-transform'; // Consider absolute path if needed

        // Check if gltf-transform is accessible (important initial check)
        $processVersionCheck = Process::run([$gltfTransformCommand, '--version']);
        if ($processVersionCheck->failed()) {
            throw new \Exception('gltf-transform not found or not executable: ' . $processVersionCheck->getErrorOutput());
        }

        // Generate unique output path
        $outputPath = 'optimized-models/' . Str::uuid() . '-optimized.glb';
        $absoluteInputPath = Storage::disk('public')->path($inputPath);
        $absoluteOutputPath = Storage::disk('public')->path($outputPath);

        // Validate input and output paths
        if (!Storage::disk('public')->exists($inputPath)) {
            throw new \Exception("Input file not found: {$inputPath}");
        }
        Storage::disk('public')->makeDirectory(dirname($outputPath));
        if (!is_writable(dirname($absoluteOutputPath))) {
            throw new \Exception("Output directory not writable: " . dirname($absoluteOutputPath));
        }

        // Build command array (recommended for Process facade)
        $command = [
            $gltfTransformCommand,
            'optimize',
            $absoluteInputPath,
            $absoluteOutputPath,
            '--compress', 'draco',
            '--texture-compress', 'webp',
            '--texture-size', '1024',
        ];

        Log::info("Executing Process command: " . implode(' ', $command)); // Log the command

        // Execute the process using Process::run() with output callback and timeout
        $result = Process::timeout(300) // Set a timeout (adjust as needed, but have a reasonable timeout)
            ->run($command, function (string $type, string $output) {
                if ($type === 'err') { // Correctly compare $type to string 'err'
                    Log::info("STDERR > " . $output);
                } else {
                    Log::info("STDOUT > " . $output);
                }
            });

        if ($result->failed()) {
            Log::error('GLB Optimization failed with Process.');
            Log::error('Exit Code: ' . $result->exitCode());
            Log::error('Error Output: ' . $result->errorOutput());
            Log::error('Standard Output: ' . $result->output()); // Log standard output as well for context
            throw new \Exception('GLB Optimization failed using gltf-transform: ' . $result->getErrorOutput() ?: 'Process failed with exit code ' . $result->exitCode());
        }

        Log::info("GLB optimization with Process successful.");
        Log::info("Output from gltf-transform: " . $result->output()); // Log final output for info
        return $outputPath; // Return the path to the optimized model
    }
}
