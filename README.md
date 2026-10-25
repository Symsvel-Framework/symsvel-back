# SymSvel Backend

Socle Symfony 8.1 / PHP 8.4 du framework SymSvel. Projet Composer privé (`type: project`).

## Créer un projet

Prérequis : PHP 8.4+, Composer 2, Git, accès GitHub aux dépôts privés `Symsvel-Framework` (Composer en HTTPS et Git en SSH).

Configurer une fois le dépôt VCS privé pour Composer :

```bash
composer config --global repositories.symsvel-back vcs https://github.com/Symsvel-Framework/symsvel-back
```

Configurer l'authentification GitHub de Composer via `composer config --global --auth github-oauth.github.com <TOKEN>` (ne jamais committer le jeton). Le compte doit avoir accès au dépôt privé.

Après publication d'un tag stable `vX.Y.Z` :

```bash
composer create-project symsvel-framework/back mon-projet
cd mon-projet
php bin/console symsvel:install
```

L'installateur propose :

1. BO uniquement (par défaut)
2. BO + App
3. BO + Front
4. BO + App + Front

L'installation clone les interfaces privées en SSH dans `bo/`, `app/` et/ou `front/`. Configurer une clé SSH GitHub avec accès à ces dépôts. Les dossiers existants ne sont jamais écrasés. Installer ensuite les dépendances des interfaces avec `npm ci` dans chaque dossier créé.

**Important :** `create-project` copie le socle à une version donnée. Les mises à jour du backend ne sont pas propagées automatiquement aux projets créés.

## Qualité

```bash
composer install
composer qa
```

## Releases

Pousser un tag `vX.Y.Z` sur le commit à publier déclenche le workflow GitHub Release. Il installe les dépendances, exécute `composer qa`, puis publie la release GitHub si les contrôles réussissent. Le dépôt reste privé.
