# SymSvel API contract

The backend exposes two API areas: `/api` and `/apibo`.

| Area | Swagger UI | OpenAPI JSON |
| --- | --- | --- |
| API | `/api/doc` | `/api/doc.json` |
| Back office | `/apibo/doc` | `/apibo/doc.json` |

## Endpoint requirements

Every published endpoint must describe its HTTP method, path, authentication requirements, parameters, request body, success responses and error responses in OpenAPI. An undocumented endpoint is not considered finished.

The SymSvel core response envelope is:

- Successful response with a body: `{"data": ...}`
- Error: `{"error":{"code":"...","message":"..."}}`
- Validation error (HTTP 422): `{"error":{"code":"...","message":"...","violations":{"field":"reason"}}}`
- HTTP 204: no body

Implementations must use `SymSvel\Core\Http\ApiResponse` rather than inventing incompatible response envelopes. Document the precise shape of `data` for each operation, not just the envelope.

## CORS

`SYMSVEL_CORS_ALLOW_ORIGIN` is a regular expression for explicitly authorized browser origins. The committed development default allows localhost and 127.0.0.1 with optional ports; deployment environments must configure their actual origins. Do not use an unrestricted wildcard in production. CORS is configured for `/api` and `/apibo`, including preflight `OPTIONS` requests.

## Quality gates

Before merging API changes, verify that:

1. Both Swagger UIs and JSON documents load.
2. The documented request/response schemas match real endpoint behavior, including errors.
3. Preflight and ordinary cross-origin requests behave as expected for both allowed and disallowed origins.
4. Symfony container lint, automated tests and Composer lock validation pass.

## Legacy audit

XCore imported generated module routes via `config/routes/xcore_routing.yaml`. Its Nelmio documentation defined separate `default` and `apibo` areas. SymSvel should retain the useful contracts, but generated module routing must be reconciled with the existing module synchronization implementation before introducing or replacing a generator.

## Routing audit findings (2026-11-03)

- The legacy backend's `config/routes.yaml` explicitly imported `routes/xcore_routing.yaml`. That generated file contains a warning not to edit it manually and is published by `xcore:module:sync`.
- The current SymSvel backend's `config/routes.yaml` imports `routing.controllers` only. There is no `config/routes/symsvel_routing.yaml` in the current branch.
- The current `symsvel:module:sync` command in Tooling publishes Composer/local inventories and bundle maps, and synchronizes Svelte files. Its implementation does not itself write a generated Symfony routes file.
- The backend Composer post-install/post-update script runs `symsvel:module:sync --source=composer`. Any future route materialization must fit this lifecycle, preserve module ownership and not bypass validation/rollback guarantees.

**Decision pending implementation:** audit the installed-module route manifests and Symfony Flex route imports before introducing a generated route file. Do not assume that adding an empty generated file would solve module routing.

## Flex recipes versus generated routing

The current Tooling `ModuleMaker` produces a `recipe/recipe.json` template containing a Symfony Flex `config/routes/<module>.yaml` file. This route file imports controller attributes from the installed module bundle. Thus, the current SymSvel contract uses **Flex-managed per-module route imports**, unlike XCore's single generated aggregate file.

Do not add a second aggregate route import without a migration design: doing so could register the same controller routes twice. First verify route presence after `symsvel require` and removal after `symsvel remove`, including Flex recipe cleanup. If tests reveal a gap, fix the actual lifecycle rather than introducing an unneeded duplicate routing system.
