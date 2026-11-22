# Intégration continue

Depuis le 22/11/2026, la CI des dépôts publics SymSvel s'exécute sur **GitHub-hosted Ubuntu 24.04**, sans runner local. `.github/workflows/ci.yml` utilise PHP 8.4, Composer et PHPUnit ; déclencheurs : push sur `dev` et `main`, PR vers `main`. `ci.yml.stub` est une archive inactive.

Les runners auto-hébergés du mainteneur restent réservés aux dépôts privés `symsvel-packages` et `symsvel-recipes`. Les validations CI ne remplacent pas les crash-tests locaux.

## Releases sur GitHub-hosted (22/11/2026)

La publication de tags `vX.Y.Z` par `.github/workflows/release.yml` s'exécute sur GitHub-hosted Ubuntu 24.04. Elle conserve PHP 8.4 et Composer, la vérification du tag, les contrôles existants et la création de GitHub Release via `actions/github-script@v7`. Aucun tag n'a été créé durant cette migration ; la validation d'une release réelle reste nécessaire.

