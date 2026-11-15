# ADR 0005 : Un projet utilisable après le setup

**Statut : acceptée**  
**Date : 2026-11-06**

## Contexte

L’installation doit aboutir à une application exploitable, pas à un squelette à compléter manuellement.

## Décision

- Le BO est obligatoire ; App et Front sont optionnels.
- `create-project` prépare le projet sans base obligatoire.
- `setup` configure la base, le projet et le premier administrateur.
- Après setup, l’administrateur accède au BO via Auth ; une 2FA peut être ajoutée via un module indépendant.
- Avec App, inscription et connexion utilisateur via Auth ; la 2FA reste optionnelle et indépendante.
- Avec Front, page initiale accessible.
- Modules natifs requis : Admins, Roles, ACLs, Settings et Auth (y compris en BO seul) ; Users si App est installée, y compris ultérieurement. Auth est installé par le manifeste du Back lors de `create-project`, avant le Setup Wizard. La 2FA est distincte et optionnelle ; son activation n'est pas une condition de disponibilité de l'authentification.
- Chaque module fournit ses pages et processus selon ses scopes.

## Justification

Une installation doit livrer un résultat directement vérifiable.

## Conséquences

Le dashboard initial du BO reste minimal. Aucun système de widgets générique n’est conçu avant besoin réel.

## Validation technique restante

Valider dans les crash-tests les quatre combinaisons bo/app/front/all et l’ajout différé d’App.
