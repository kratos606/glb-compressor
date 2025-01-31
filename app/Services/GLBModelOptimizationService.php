<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GLBModelOptimizationService
{
    /**
     * Optimizes a GLB model using gltf-transform optimize.
     *
     * @param string $inputPath Path to the input GLB model file (relative to storage/app/public/models or absolute path if needed)
     * @return string|null Path to the optimized GLB model file (relative to storage/app/public/optimized-models) or null on failure
     * @throws \Exception if gltf-transform is not installed or optimization fails
     */
    public function optimizeGLBModel(string $inputPath): ?string
    {
        $gltfTransformCommand = 'gltf-transform';

        // Check if gltf-transform is accessible (directly call)
        $process = new Process([$gltfTransformCommand, '--version']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('gltf-transform is not installed or not in your PATH. Please ensure it is installed and accessible. Error: ' . $process->getErrorOutput() . ' Output: ' . $process->getOutput());
        }

        // Generate a unique output path in the 'optimized-models' directory
        $outputPath = 'optimized-models/' . Str::uuid() . '-optimized.glb';
	$absoluteInputPath = Storage::disk('public')->path($inputPath); // Adjusted line

	\Log::info("Resolved input path: {$absoluteInputPath}"); // Debug log

	$absoluteOutputPath = Storage::disk('public')->path($outputPath);

	// Ensure directory exists
        Storage::disk('public')->makeDirectory(dirname($outputPath));

        \Log::info("Output path: {$absoluteOutputPath}");

        // Build the gltf-transform optimize command (directly call)
        $command = [
            $gltfTransformCommand,
            'optimize',
            $absoluteInputPath,
            $absoluteOutputPath,
            '--compress', 'draco',
            '--texture-compress', 'webp',
            '--texture-size', '1024',
        ];

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('GLB model optimization failed: ' . $process->getErrorOutput() . ' ' . $process->getOutput());
        }

        return $outputPath;
    }
}
