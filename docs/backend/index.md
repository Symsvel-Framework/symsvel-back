# Backend et API

Le Back est un projet Symfony. Le Core fournit les services techniques ; les contrôleurs métier appartiennent aux modules.

| Espace | Swagger UI | OpenAPI JSON |
| --- | --- | --- |
| `/api` | `/api/doc` | `/api/doc.json` |
| `/apibo` | `/apibo/doc` | `/apibo/doc.json` |

Les endpoints doivent documenter méthode, chemin, authentification, paramètres, requêtes et réponses. NelmioApiDocBundle porte la documentation OpenAPI.

Les routes des modules sont importées via les fichiers Flex individuels sous `config/routes/`, sans générateur d'agrégateur.

Voir [réponses API](reponses.md) et [CORS](cors.md).

## Branding public du projet (19/11/2026)

Le Setup persiste le nom du projet dans le document privé
`<workspace>/.symsvel/project.json`, avec le domaine local et le canal
de provenance. Le Back expose **uniquement le nom validé** au navigateur
via `GET /apiopen/project` : `{"data":{"name":"Nom public"}}`.
L'API ne retourne ni `channel`, ni `local_domain`, ni secrets,
ni métadonnées de Setup. Si le fichier privé n'est pas lisible ou
est invalide, la marque de repli reste `SymSvel`. Les données sont
sans cache pour qu'un changement validé soit visible sans relancer
les builds Svelte, tandis que la configuration des domaines reste
sous le contrôle du Setup/Tooling.

Le contrôleur est un adaptateur technique limité au projet Back ;
il ne crée ni module branding générique ni nouvelle configuration
de runtime dans Core. Le BO et l'App consomment ce seul endpoint via
leur client public. Tests de projection et HTTP ajoutés, CI et CT
de lecture sous PHP-FPM après Setup requis. L'interface ne doit pas
lire elle-même `.symsvel/project.json`.
