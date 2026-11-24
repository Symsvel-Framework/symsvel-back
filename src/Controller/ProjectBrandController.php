<?php

declare(strict_types=1);

namespace App\Controller;

use App\Setup\ProjectMetadata;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use SymSvel\Core\Http\ApiResponse;

/** Public branding limited to the display name saved during local Setup. */
final readonly class ProjectBrandController
{
    public function __construct(
        private ProjectMetadata $metadata,
        #[Autowire('%kernel.project_dir%')] private string $backend,
        #[Autowire('%env(SYMSVEL_PROJECT_NAME_B64)%')] private string $runtimeName,
    ) {
    }

    #[Route('/apiopen/project', name: 'symsvel_public_project', methods: ['GET'], format: 'json')]
    public function __invoke(): JsonResponse
    {
        // The local HTTP worker need not be able to read private Setup metadata.
        // Base64 safely transports arbitrary Unicode names through Symfony Dotenv.
        $name = 'SymSvel';
        if ($this->runtimeName === '') {
            // Compatibility with existing deployments created before projection.
            $name = $this->metadata->publicName(dirname($this->backend));
        } else {
            $decoded = base64_decode($this->runtimeName, true);
            if ($decoded !== false) {
                try {
                    $this->metadata->validate($decoded);
                    $name = trim($decoded);
                } catch (\InvalidArgumentException) {
                    // Invalid runtime configuration uses the public fallback.
                }
            }
        }
        $response = ApiResponse::success([
            'name' => $name,
        ]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
