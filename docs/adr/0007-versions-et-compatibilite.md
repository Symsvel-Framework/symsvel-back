# ADR 0007 : Faire évoluer les versions après validation

**Statut : acceptée**  
**Date : 2026-11-06**

## Contexte

Une nouvelle version disponible n’est pas une raison suffisante pour migrer.

## Décision

- PHP 8.4 est la référence tant qu’il est maintenu ; PHP inférieur non supporté, supérieur non garanti.
- Symfony 8.1 est la base actuelle, avec objectif d’évolution vers la future LTS 8.4.
- Appliquer la même prudence à SvelteKit et Node.js.
- Tester les montées de version dans un projet sandbox local avant adoption.
- Reporter la politique détaillée de rétrocompatibilité multiversion.

## Justification

Limiter les régressions et les migrations imposées aux projets existants.

## Conséquences

Les versions de référence évoluent après tests et mise à jour des contrats documentés.

## Vérification technique restante

Vérifier les versions réellement déclarées dans les manifests avant publication.
