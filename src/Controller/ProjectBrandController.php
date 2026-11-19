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
    ) {
    }

    #[Route('/apiopen/project', name: 'symsvel_public_project', methods: ['GET'], format: 'json')]
    public function __invoke(): JsonResponse
    {
        $response = ApiResponse::success([
            'name' => $this->metadata->publicName(dirname($this->backend)),
        ]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
