# SymSvel Backend

Socle Symfony 8.1 / PHP 8.4 du framework SymSvel. Projet Composer privé (`type: project`).

## Créer un projet

Prérequis : PHP 8.4+, Composer 2, Git, accès GitHub aux dépôts privés `Symsvel-Framework` (Composer en HTTPS et Git en SSH).

Configurer une fois le dépôt VCS privé pour Composer :

```bash
composer config --global repositories.symsvel-back vcs https://github.com/Symsvel-Framework/symsvel-back
```

Configurer l'authentification GitHub de Composer via `composer config --global --auth github-oauth.github.com <TOKEN>` (ne jamais committer le jeton). Le compte doit avoir accès au dépôt privé.

Une release `v0.1.0` existe déjà. Elle est antérieure aux derniers changements du Tooling. Pour valider le parcours complet, il faudra publier une nouvelle version du Back **uniquement après validation explicite**, avec un `composer.lock` actualisé et une CI verte.

Après publication de cette version :

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

L'installation clone les interfaces privées en SSH dans `bo/`, `app/` et/ou `front/`. Configurer une clé SSH GitHub avec accès à ces dépôts. Les dossiers existants ne sont jamais écrasés. La version actuelle du Tooling exécute automatiquement `npm ci` puis `npm run build` pour chaque interface. Il faut donc disposer de Node.js/npm localement. Les dossiers nouvellement créés sont supprimés si l'installation échoue, sans toucher aux chemins préexistants.

**Important :** `create-project` copie le socle à une version donnée. Les mises à jour du backend ne sont pas propagées automatiquement aux projets créés.

## Préparation du premier crash-test

Le premier test global partira d'un dossier vierge et devra confirmer : création du Back par Composer, installation des trois interfaces (choix 4), builds SvelteKit réussis, démarrage de Symfony et accès aux pages BO, App et Front dans le navigateur.

**Attention :** le `composer.lock` du Back verrouille une révision précise de Tooling. Le merge de Tooling dans `main` ne met pas automatiquement à jour les projets existants ni les releases du Back. Avant publication d'une nouvelle version, actualiser ce verrouillage via Composer, exécuter `composer qa` et vérifier la CI.

Les mises à jour sont exclusivement locales. Aucune commande de mise à jour ne doit s'exécuter sur un serveur de production.

## Qualité

```bash
composer install
composer qa
```

## Releases

Pousser un tag `vX.Y.Z` sur le commit à publier déclenche le workflow GitHub Release. Il installe les dépendances, exécute `composer qa`, puis publie la release GitHub si les contrôles réussissent. Le dépôt reste privé.
