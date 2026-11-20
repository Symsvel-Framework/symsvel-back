<?php

declare(strict_types=1);

namespace App\Setup;

/** Builds a strict origin regex for the local BO, App and public website origins. */
final class LocalCorsOrigins
{
    public function fromDomain(string $domain): string
    {
        (new ProjectMetadata())->validateLocalDomain($domain);
        $domain = strtolower(trim($domain));
        $escaped = preg_quote($domain, '~');

        return '^https?://(?:(?:bo|app|www)\\.)?'.$escaped.'(?::[0-9]+)?$';
    }
}
