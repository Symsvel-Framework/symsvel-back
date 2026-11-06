# ADR 0005 : Un projet utilisable après le setup

**Statut : acceptée**  
**Date : 2026-11-06**

## Contexte

L’installation doit aboutir à une application exploitable, pas à un squelette à compléter manuellement.

## Décision

- Le BO est obligatoire ; App et Front sont optionnels.
- `create-project` prépare le projet sans base obligatoire.
- `setup` configure la base, le projet et le premier administrateur.
- Après setup, l’administrateur accède au BO avec 2FA.
- Avec App, inscription et connexion utilisateur avec 2FA.
- Avec Front, page initiale accessible.
- Modules natifs requis : Admins, Roles, ACLs ; Users si App est installée, y compris ultérieurement.
- Chaque module fournit ses pages et processus selon ses scopes.

## Justification

Une installation doit livrer un résultat directement vérifiable.

## Conséquences

Le dashboard initial du BO reste minimal. Aucun système de widgets générique n’est conçu avant besoin réel.

## Validation technique restante

Valider dans les crash-tests les quatre combinaisons bo/app/front/all et l’ajout différé d’App.
