# XCore → SymSvel: services, configuration and extension audit

Status: exploratory inventory, **not** an architecture decision. Reviewed 2026-11-04.

## Files inspected

XCore legacy backend:
- `config/services.yaml`, `config/xcore/services_modules.yaml`, `src/Kernel.php`, `config/packages/framework.yaml`
- `src/Core/PasswordReset/Service/{PasswordResetService,PasswordResetSubjectProviderInterface}.php`
- `src/Core/Mail/Template/{OptionalMailTemplateRenderer,MailTemplateRendererInterface}.php`
- `src/Core/Storage/{LocalFirstStorage,StorageOperatorInterface,StorageFallbackInterface,StorageAsyncDispatcherInterface}.php`
- `src/Core/Settings/Service/{UrlConfigurationManager,UrlConfigurationStoreInterface}.php`
- `src/Core/Authentication/Service/SecretCipher.php`

SymSvel:
- back `config/services.yaml`, `src/Kernel.php`, `config/packages/framework.yaml`
- Core `src/SymSvelCoreBundle.php`, `composer.json`
- Tooling `src/SymSvelToolingBundle.php`, `src/Module/ModuleMaker.php`

## Verified findings

1. **Dependency injection:** XCore imports a generated `config/xcore/services_modules.yaml`, while SymSvel back uses Symfony's default autowiring/autoconfiguration. Tooling registers its own services in an `AbstractBundle::loadExtension()`. Do not recreate a generated central service registry without a demonstrated requirement.
2. **Password reset providers:** XCore registers `PasswordResetSubjectProviderInterface` implementations via `_instanceof` tagging and `!tagged_iterator`. The service indexes providers by subject type and rejects duplicates. Preserve the *capability and conflict detection* when designing native identity modules, not necessarily the legacy class structure.
3. **Mail rendering:** XCore uses `#[AsAlias]` and `#[AutowireIterator('xcore.mail.template_renderer')]` to select optional custom renderers, with a default fallback. Investigate whether a Symfony service decorator, tagged collection or explicit provider contract best fits actual mail-module requirements. Do not introduce a generic hook manager.
4. **Storage:** XCore defines a small `StorageOperatorInterface` (exists/read/write/delete), optional fallback and async dispatcher interfaces. `LocalFirstStorage` enforces paired fallback/dispatcher configuration and requires a journal when fallback is enabled. These are useful *domain invariants* for a future storage module, not reasons to put storage into SymSvel Core now.
5. **URL configuration:** XCore's `UrlConfigurationManager` stores component URLs, caches them, validates HTTP(S) URLs, and reacts to backend URL changes. It also carries legacy env-file reading and client rebuild coupling. Preserve validation/invalidation concerns if needed; avoid copying legacy bootstrap and file mutation.
6. **Bootstrap:** XCore Kernel overrides `boot()` to load a setup runtime environment and fall back to SQLite before installation. SymSvel Kernel is standard Symfony. Keep it that way unless a reproducible installation requirement proves otherwise.
7. **Secrets:** XCore `SecretCipher` uses libsodium secretbox with a key derived from `kernel.secret`. This is **not** automatically suitable for long-lived application secrets: key rotation and operational separation must be evaluated before reuse.
8. **Module maker:** SymSvel generates an empty `AbstractBundle`. This is a sound minimal default. Documentation should demonstrate native Symfony service registration, decoration, tags, and events in a separate example rather than impose them on every module.

## Decisions / next checks

- **Keep:** native Symfony DI, module-owned bundles, small interfaces at real extension seams, duplicate-provider detection, explicit optional-dependency invariants.
- **Do not copy:** generated central service registry, custom Kernel setup bootstrap, generic hook system, legacy env-file parsing.
- **Investigate:** actual XCore event subscribers/listeners, tagged-service priorities and fallback ordering, settings persistence/cache invalidation, secret-key lifecycle, and compatibility of module registration/removal with service decoration.
- **Test later:** one concrete provider registration and duplicate rejection; one service decoration; one event subscriber; removal without stale references. These are new extension-focused checks, distinct from the already-exercised TestBack install/migration crash tests.

This inventory does not authorize implementation or change the accepted ADRs. Promote specific conclusions to an ADR only after verifying the corresponding use case and trade-offs.
