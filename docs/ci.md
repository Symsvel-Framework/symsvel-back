# Intégration continue

Depuis le 22/11/2026, la CI des dépôts publics SymSvel s'exécute sur **GitHub-hosted Ubuntu 24.04**, sans runner local. `.github/workflows/ci.yml` utilise PHP 8.4, Composer et PHPUnit ; déclencheurs : push sur `dev` et `main`, PR vers `dev` et `main`. `ci.yml.stub` est une archive inactive.

Les runners auto-hébergés du mainteneur restent réservés aux dépôts privés `symsvel-packages` et `symsvel-recipes`. Les validations CI ne remplacent pas les crash-tests locaux.

## Releases sur GitHub-hosted (22/11/2026)

La publication de tags `vX.Y.Z` par `.github/workflows/release.yml` s'exécute sur GitHub-hosted Ubuntu 24.04. Elle conserve PHP 8.4 et Composer, la vérification du tag, les contrôles existants et la création de GitHub Release via `actions/github-script@v7`. Aucun tag n'a été créé durant cette migration ; la validation d'une release réelle reste nécessaire.


## Audit des dépendances PHP

`composer audit --locked` est bloquant dans la CI et dans `composer qa`. Aucun avis n'est ignoré. Le lock du squelette Back doit être résolu par Composer, puis validé par `composer validate --strict --no-check-publish` et les audits complet et production (`composer audit --locked --no-dev`). En novembre 2026, les planchers de sécurité de Symfony 8.1 sont relevés pour HttpClient, Mime, Security HTTP, Serializer, Validator et YAML ; PHPUnit est relevé à 13.4.4. Les CT restent obligatoires après mise à jour et avant release.

### Correctif Twig (24/11/2026)

L'audit du lock a détecté neuf avis concernant `twig/twig` (CVE-2026-133763 à 133768, 133770 à 133772). Le plancher de Twig est relevé à `^3.31.1` et le lock doit être résolu par une mise à jour ciblée Composer (`--with-dependencies --minimal-changes`), avec les audits complet et production. Aucun avis ignoré, aucune modification des contrôles CI. Le CT reste nécessaire.

## Promotion stable du 24/11/2026

Le Back requiert Core `^1.2.1` et Tooling `^1.4`. Régénérer le lock par `composer update symsvel-framework/core symsvel-framework/tooling --with-dependencies --minimal-changes --no-scripts`, puis valider le manifeste et les audits complet et production. Le test `StandaloneSetupPhpCliTest` exige désormais le résolveur PHP CLI dans Tooling stable : une absence est un échec, pas un test ignoré. Le parcours de premier déploiement utilise encore `composer install` avec les dépendances de développement ; `--no-dev` n'est pas validé pour le Setup initial.

`ComposerStableLockTest` vérifie explicitement les contraintes du manifeste (`^1.2.1`, `^1.4`), les versions stables minimales (Core 1.2.1, Tooling 1.4.0), le content-hash du lock et la cohérence des références source/dist. Lors d'une promotion, ces assertions doivent être actualisées avec le manifeste et le lock généré par Composer.
