# ADR 0001 : Frontière framework et modules

- Statut : acceptée
- Date : 2026-11-03

## Contexte

SymSvel fournit une distribution opérationnelle à l'installation, tout en permettant à des développeurs de réutiliser et adapter son socle.

## Décision

Le framework comprend la CLI (point d'entrée), le Tooling (outils de développement et opérations) et le Core (runtime technique léger). Les fonctionnalités applicatives sont portées par des modules, y compris les modules natifs nécessaires à la distribution standard (par exemple Users, Roles, ACL, Admins).

La distribution standard peut assumer des dépendances et couplages obligatoires avec ses modules natifs. Ces points doivent rester identifiables et documentés, notamment pour un développeur souhaitant les remplacer avant installation.

Les modules doivent pouvoir évoluer à leur rythme, sans imposer de changement de la CLI, du Tooling ou du Core tant que les contrats techniques restent compatibles.

## Conséquences

- Ne pas déplacer une fonctionnalité applicative dans le Core au seul motif qu'elle est obligatoire à l'installation.
- Conserver une distribution standard prête à l'emploi.
- Documenter précisément les adaptations préalables à l'installation lorsque les modules natifs sont remplacés.
- Ne pas construire une couche d'abstraction complexe uniquement pour éliminer tout couplage.
