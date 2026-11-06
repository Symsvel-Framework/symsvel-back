# Backend et API

Le Back est un projet Symfony. Le Core fournit les services techniques ; les contrôleurs métier appartiennent aux modules.

| Espace | Swagger UI | OpenAPI JSON |
| --- | --- | --- |
| `/api` | `/api/doc` | `/api/doc.json` |
| `/apibo` | `/apibo/doc` | `/apibo/doc.json` |

Les endpoints doivent documenter méthode, chemin, authentification, paramètres, requêtes et réponses. NelmioApiDocBundle porte la documentation OpenAPI.

Les routes des modules sont importées via les fichiers Flex individuels sous `config/routes/`, sans générateur d'agrégateur.

Voir [réponses API](reponses.md) et [CORS](cors.md).
