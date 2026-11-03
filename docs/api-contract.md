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

## Routing architecture: adopted decision (2026-11-03)

SymSvel keeps **one Symfony Flex-managed route file per module**, installed from that module's recipe at `config/routes/<module>.yaml`. The recipe imports controller attributes from the module bundle. This is intentionally different from XCore's generated central `xcore_routing.yaml`.

Do **not** introduce an aggregate generated routing file or duplicate route imports. The reasons and consequences are recorded in [ADR 0003](adr/0003-routage-flex.md). The XCore comparison and selection criteria are recorded in [ADR 0004](adr/0004-selection-xcore.md).

When resuming module lifecycle work, explicitly crash-test that routes appear after `symsvel require` and disappear after `symsvel remove`, including Flex recipe cleanup. This remains a test requirement, **not an unresolved architectural decision**.

## Scope of the current API work

The HTTP functional tests check both OpenAPI JSON documents and CORS preflight handling for an allowed and a disallowed origin. They do not yet prove that a real business endpoint's documented schemas match its runtime responses, nor that Swagger UI assets render in a browser. Those checks must accompany the first concrete API endpoints and the interface integration work.
