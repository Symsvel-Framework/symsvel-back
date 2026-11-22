# Intégration continue

Depuis le 22 novembre 2026, les workflows CI de ce dépôt public s'exécutent sur **GitHub-hosted Ubuntu 24.04** et non sur les runners Docker du mainteneur. `.github/workflows/ci.yml` conserve les tests et utilise PHP 8.4 (Symfony et PHPUnit). Déclencheurs : `push` sur `dev` et `main`, et pull requests vers `main`. Le fichier `ci.yml.stub` n'est qu'une archive inactive de la configuration historique.

Les runners auto-hébergés sont réservés aux dépôts privés d'infrastructure `symsvel-packages` et `symsvel-recipes`. Une CI verte n'exonère pas des crash-tests locaux exigés par SymSvel.
 

La branche `dev` est le terrain de développement ; `main` conserve la version stable.

Le workflow `.github/workflows/ci.yml` est déclenché par les `push` sur `dev` et `main`, ainsi que par les pull requests ciblant `main`. Les pull requests ciblant `dev` ne déclenchent pas de CI au titre de l'événement `pull_request` ; un `push` sur `dev` déclenche sa propre exécution. Les éventuels déclencheurs supplémentaires définis dans le workflow restent applicables.

Les jobs utilisent les runners auto-hébergés configurés dans le workflow. Une modification n'est validée qu'après exécution et réussite de la CI, puis les crash-tests locaux requis.

## Releases sur GitHub-hosted (22/11/2026)

Le workflow `.github/workflows/release.yml` s'exécute désormais sur **GitHub-hosted Ubuntu 24.04** pour les tags `vX.Y.Z`. Il conserve les validations existantes, PHP 8.4 et Composer, ainsi que la vérification du tag et la GitHub Release via `actions/github-script@v7`. L'autorisation `contents: write` reste requise pour publier. Aucun tag n'a été créé pendant la migration ; une release réelle devra être validée séparément.


Le workflow de tags `vX.Y.Z` utilise les labels CI existants `[self-hosted, linux, ARM64, symsvel]`, avec PHP 8.4 et les extensions Symfony contrôlés sur le runner. L'installation privée Composer utilise toujours `SYMSVEL_READ_TOKEN`. La publication reste une GitHub Release du tag vérifié via `actions/github-script@v7`, sans dépendance à l'exécutable `gh`. Aucun tag n'est créé par cette modification. L'autorisation `contents: write`, les secrets existants et la CI avant release restent requis ; validation d'une release réelle à effectuer.
