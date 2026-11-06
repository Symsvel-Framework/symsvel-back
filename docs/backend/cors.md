# CORS et OpenAPI

`SYMSVEL_CORS_ALLOW_ORIGIN` configure les origines autorisées. En développement, localhost et 127.0.0.1 peuvent être acceptés avec ports optionnels ; en production, configurer explicitement les origines attendues.

CORS concerne les espaces `/api` et `/apibo`, y compris les requêtes préliminaires `OPTIONS`. Ne pas ouvrir toutes les origines sans contrôle.

Avant livraison d'un endpoint, vérifier OpenAPI JSON et Swagger UI, les schémas des réponses, les préflights autorisés/refusés et les tests HTTP.
