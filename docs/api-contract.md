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
