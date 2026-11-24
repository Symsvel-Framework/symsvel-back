# ADR 0006 : Séparer sécurité applicative et fiabilité des outils

**Statut : acceptée**  
**Date : 2026-11-06**

## Contexte

Les outils locaux ne sont pas des services exposés ; le code runtime peut être déployé dans des contextes variés, y compris hors Internet.

## Décision

- CLI et Tooling privilégient contrôles, intégrité, sauvegarde et récupération des opérations.
- Back, Core et modules appliquent les protections applicatives adaptées : authentification, autorisations, sessions, API et validation des entrées.
- Les permissions et secrets de déploiement dépendent de l’hébergement réel.
- Le framework ne remplace ni pare-feu, ni WAF, ni administration système.

## Justification

Éviter la surprotection de l’outillage local sans affaiblir le runtime.

## Conséquences

Ne pas imposer une politique de droits Unix unique à tous les environnements. Documenter les exigences et les limites.

## Application au Setup

En local individuel, `.env.local` est publié en 0644 afin que les identités CLI et HTTP distinctes puissent le lire, sans droits d'écriture ou d'exécution pour les autres. En production les deux fichiers d'environnement générés restent en 0600. Les métadonnées Setup privées ne sont pas ouvertes : le nom public est projeté dans l'environnement runtime.
