# ADR 0002 : Modules natifs minimalistes et extensibles

- Statut : acceptée
- Date : 2026-11-03

## Contexte

Les modules natifs doivent être utiles au plus grand nombre sans imposer des données ou fonctionnalités propres à certains métiers.

## Décision

Chaque module natif couvre uniquement son besoin commun et sa responsabilité. Il propose des points d'extension ciblés permettant de l'enrichir sans modifier son code source.

Exemple de périmètre minimal envisagé pour Users : nom, prénom, email, mot de passe et rôle. Les adresses, informations familiales et autres données métier n'appartiennent pas par défaut à ce socle.

Les extensions doivent privilégier les mécanismes existants de Symfony et Svelte lorsque cela répond au besoin. Aucun catalogue massif de hooks préventifs : un hook répond à un cas d'extension identifié.

## Conséquences

- Les fonctionnalités supplémentaires sont développées dans des modules ou extensions distincts.
- Les modules natifs restent légers et faciles à adopter.
- On évite à la fois le module monolithique et la multiplication artificielle de micro-modules.
- On ne crée pas des dizaines de hooks pour personnaliser un détail d'interface.
