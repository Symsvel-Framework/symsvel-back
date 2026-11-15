<?php

declare(strict_types=1);

namespace App\Setup;

/** Builds a strict origin regex for the three local Svelte entry points. */
final class LocalCorsOrigins
{
    public function fromDomain(string $domain): string
    {
        (new ProjectMetadata())->validateLocalDomain($domain);
        $domain = strtolower(trim($domain));
        $escaped = preg_quote($domain, '~');

        return '^https?://(?:bo|app|front)\\.'.$escaped.'(?::[0-9]+)?$';
    }
}
