# CORS et OpenAPI

`SYMSVEL_CORS_ALLOW_ORIGIN` configure les origines autorisées. En développement, localhost et 127.0.0.1 peuvent être acceptés avec ports optionnels ; en production, configurer explicitement les origines attendues.

CORS concerne les espaces `/api` et `/apibo`, y compris les requêtes préliminaires `OPTIONS`. Ne pas ouvrir toutes les origines sans contrôle.

Avant livraison d'un endpoint, vérifier OpenAPI JSON et Swagger UI, les schémas des réponses, les préflights autorisés/refusés et les tests HTTP.

Lors du Setup local, le domaine saisi (par exemple `xt.local`) produit automatiquement dans `backend/.env.local` une expression régulière pour `http`/`https` pour le domaine racine et les sous-domaines `www`, `bo`, `app` (ports de développement optionnels). Exemple : `^https?://(?:(?:bo|app|www)\\.)?xt\\.local(?::[0-9]+)?# CORS et OpenAPI

`SYMSVEL_CORS_ALLOW_ORIGIN` configure les origines autorisées. En développement, localhost et 127.0.0.1 peuvent être acceptés avec ports optionnels ; en production, configurer explicitement les origines attendues.

CORS concerne les espaces `/api` et `/apibo`, y compris les requêtes préliminaires `OPTIONS`. Ne pas ouvrir toutes les origines sans contrôle.

Avant livraison d'un endpoint, vérifier OpenAPI JSON et Swagger UI, les schémas des réponses, les préflights autorisés/refusés et les tests HTTP.

Lors du Setup local, le domaine saisi (par exemple `xt.local`) produit automatiquement dans `backend/.env.local` une expression régulière pour `http`/`https` pour le domaine racine et les sous-domaines `www`, `bo`, `app` (ports de développement optionnels). . Les origines étrangères, notamment `backend` et les sous-domaines arbitraires, restent interdites. La configuration de production reste explicite et indépendante.

## Cookie de renouvellement BO (16/11/2026)

Le transport navigateur BO utilise `POST /apibo/auth/browser/{login,refresh,logout}` sur le domaine backend HTTPS avec `credentials: include`. Le cookie refresh `__Host-symsvel-bo-refresh` est `Secure`, `HttpOnly`, `SameSite=Strict`, `Path=/`, sans Domain ; l'access token reste uniquement en mémoire et les contrôleurs CRUD continuent de recevoir un Bearer. L'origine BO exacte est vérifiée côté module Auth depuis `.symsvel/project.json` enregistré au Setup (`https://bo.<local_domain>`) ou depuis l'override explicite `SYMSVEL_BO_ORIGIN` pour l'hébergement. Les autres endpoints `/apibo` et `/api` gardent `allow_credentials: false` ; seul le chemin `^/apibo/auth/browser(?:/|$)` active les credentials sur les POST et préflights. Les origines générales permises par `SYMSVEL_CORS_ALLOW_ORIGIN` sont un filtrage CORS additionnel, pas un substitut à la validation stricte `Origin` d'Auth. Aucune opération sensible ne doit se baser uniquement sur la présence du cookie.

CT requis : navigateur sur HTTPS, réponse `Access-Control-Allow-Credentials: true` exclusivement pour le nouveau chemin, en-tête `Origin` imposé, authentification/rechargement/rotation/logout, comportement des cookies tiers (BO et backend doivent être same-site) et refus des origines étrangères. Les tests automatisés de configuration ne remplacent pas ces vérifications.


### Exception de développement local (16/11/2026)

Lorsque le BO et le backend sont tous les deux en HTTP local et que le Setup a enregistré un domaine réservé (*.localhost, *.local ou *.test), le module Auth accepte exclusivement l'origine http://bo.<local_domain> en environnement Symfony dev (port de développement facultatif). Le cookie de renouvellement est alors `symsvel-bo-refresh-local`, `HttpOnly`, `SameSite=Strict`, sans attribut `Secure` ni `Domain`. En HTTPS, le cookie normal `__Host-symsvel-bo-refresh` demeure `Secure` et host-only. Cette exception n'existe **jamais** en production ni sur les domaines publics, et ne change pas la restriction CORS qui demeure propre à `/apibo/auth/browser`.
