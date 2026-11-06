# ADR 0008 : Les modules possèdent leurs données et migrations

**Statut : acceptée**  
**Date : 2026-11-06**

## Contexte

Les données métier doivent survivre aux évolutions du code. Les modules portent leurs propres schémas.

## Décision

- Chaque module possède ses tables et migrations Doctrine natives.
- Tooling collecte, ordonne selon les dépendances de clés étrangères et compile les migrations dans `backend/migrations/`.
- PostgreSQL est le fonctionnement actuel rapporté.
- Le setup doit proposer PostgreSQL, MySQL, MariaDB et SQLite.
- Le socle et les modules natifs doivent fonctionner sur ces quatre moteurs.
- Les modules métier tiers restent responsables de leur compatibilité.
- Toute suppression destructive exige sauvegarde et confirmation adaptées.

## Justification

Centraliser l’exécution sans déplacer la propriété des données hors des modules.

## Conséquences

L’extension multibase doit réutiliser le compilateur existant, pas introduire un second mécanisme.

## Vérification technique restante

Examiner le code du compilateur : génération DBAL ou SQL PostgreSQL spécifique, ordonnancement FK, reprises et récupération.
