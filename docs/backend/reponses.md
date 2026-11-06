# Contrat des réponses API

Le Core fournit `SymSvel\Core\Http\ApiResponse`.

```php
use SymSvel\Core\Http\ApiResponse;

return ApiResponse::success(['id' => 1], 200);
return ApiResponse::noContent();
return ApiResponse::error('not_found', 'Ressource introuvable', 404);
return ApiResponse::error('invalid', 'Données invalides', 422, ['email' => 'Format invalide']);
```

Succès avec contenu : `{"data":{"id":1}}`.

Erreur : `{"error":{"code":"not_found","message":"Ressource introuvable"}}`.

Erreur 422 : `{"error":{"code":"invalid","message":"Données invalides","violations":{"email":"Format invalide"}}}`.

`noContent()` renvoie HTTP 204 sans corps. `success()` exige un code 2xx avec contenu et `error()` un code 4xx/5xx, avec code et message non vides. Les violations sont réservées au 422, où elles sont obligatoires.

Chaque endpoint documente le schéma réel de `data`.
