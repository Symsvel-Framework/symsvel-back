# Intégration continue

La branche `dev` est le terrain de développement ; `main` conserve la version stable.

Le workflow `.github/workflows/ci.yml` est déclenché par les `push` sur `dev` et `main`, ainsi que par les pull requests ciblant `main`. Les pull requests ciblant `dev` ne déclenchent pas de CI au titre de l'événement `pull_request` ; un `push` sur `dev` déclenche sa propre exécution. Les éventuels déclencheurs supplémentaires définis dans le workflow restent applicables.

Les jobs utilisent les runners auto-hébergés configurés dans le workflow. Une modification n'est validée qu'après exécution et réussite de la CI, puis les crash-tests locaux requis.

## Release sur runners auto-hébergés (16/11/2026)

Le workflow de tags `vX.Y.Z` utilise les labels CI existants `[self-hosted, linux, ARM64, symsvel]`, avec PHP 8.4 et les extensions Symfony contrôlés sur le runner. L'installation privée Composer utilise toujours `SYMSVEL_READ_TOKEN`. La publication reste une GitHub Release du tag vérifié via `actions/github-script@v7`, sans dépendance à l'exécutable `gh`. Aucun tag n'est créé par cette modification. L'autorisation `contents: write`, les secrets existants et la CI avant release restent requis ; validation d'une release réelle à effectuer.
