# Responsabilités documentaires des interfaces

| Dépôt | Documentation |
| --- | --- |
| `symsvel-bo` | Mécanismes natifs du BO |
| `symsvel-app` | Mécanismes natifs de l'App |
| `symsvel-front` | Mécanismes natifs du Front |
| Chaque module | Pages, composants, API et comportements qu'il fournit |

Les mécanismes communs sont documentés à leur source, sans duplication dans chaque module. Chaque module publie ses sources dans `docs/dev`, `docs/admin` et/ou `docs/user` en les déclarant dans `module.json.documentation`.

## Assemblage local et accès BO (19/11/2026)

La commande de maintenance `symsvel documentation:sync` délègue à Tooling la collecte des sources des modules Composer installés, ainsi que des documents de développement du Back et des hôtes BO/App/Front disponibles. Elle publie un index JSON déterministe et privé sous `backend/var/symsvel/documentation.json` (hors `public/` et hors builds Svelte), avec contrôle d'intégrité. Ce fichier ne doit pas être servi directement par HTTP.

Le module natif **Admins** porte la lecture HTTP authentifiée : `GET /apibo/admins/documentation` et `GET /apibo/admins/documentation/page?id=...`. Le Bearer BO et l'état du compte sont vérifiés. Un administrateur ordinaire accède aux guides admin/user ; le rôle `ROLE_DEV` peut également consulter les documents techniques dev. Les pages n'ont pas besoin d'être copiées dans le BO statique : la route Svelte `/documentation` les récupère à la demande via `boApi`, et affiche le Markdown sous forme de texte échappé.

**Hors Setup** : aucune installation de Python/pip/MkDocs, aucun venv et aucun build documentaire pendant `create-project` ni le Wizard. La construction éventuelle d'un véritable site MkDocs reste une opération de maintenance distincte et non réalisée à ce stade.

Les CI de chaque dépôt et les tests de permissions existent ; la validation utilisateur sur XT des droits, de la lecture et de l'actualisation du corpus reste nécessaire avant clôture de [l'issue #29](https://github.com/Symsvel-Framework/symsvel-back/issues/29).
