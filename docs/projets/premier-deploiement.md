# Premier déploiement d'un projet SymSvel

> Parcours cible, refonte PHP native en PR draft au 22/11/2026.
> Voir [le Setup](setup.md) et [l'ADR 0011](../adr/0011-bootstrap-php-natif.md).

## Préparer le projet

Après une installation locale avec la CLI, le développeur publie
le Backend, BO, App et Front dans **ses propres dépôts GitHub**.
Les variables secrètes, fichiers .env.local et états .symsvel
locaux ne sont pas versionnés.

Les sous-domaines HTTPS doivent être créés avec leurs racines HTTP :
Backend vers backend/public, BO/App/Front vers leurs dossiers de
builds statiques. Aucun serveur Node.js n'est nécessaire en production.

Chaque dépôt Svelte dispose au préalable de son propre
.github/workflows/deploy.yml sur main, avec workflow_dispatch et
les accès SSH/rsync lui permettant d'envoyer son build au bon dossier.
Les cinq secrets usuels du CT Choralys sont SSH_HOST, SSH_USER,
SSH_PORT, SSH_PRIVATE_KEY et SSH_KNOWN_HOSTS.
SymSvel ne construit pas ces workflows à la place du projet.

## Sur le VPS : un clone, un Setup

Dans le dossier Backend vide :

~~~bash
cd /chemin/du/projet/backend
git clone git@github.com:organisation-projet/backend.git .
composer install
~~~

La première installation conserve Tooling en require-dev.
Le parcours --no-dev autonome reste à tester séparément.

Ouvrir https://api.domaine.tld/prod_settings.
Ce wizard **n'a pas besoin de démarrer Symfony** pour afficher
les formulaires. Il génère .env.local avec APP_ENV=prod,
APP_DEBUG=0, APP_SECRET unique, la base et les CORS, puis
propose les migrations avec création d'admin ou l'import
d'un dump SQL existant.

Les origines sont des URL réelles indépendantes des noms de dossier.
Exemple Choralys :

| Scope | Origine |
| --- | --- |
| Back | https://api.choralys.com |
| BO | https://bo.choralys.com |
| App | https://manager.choralys.com |
| Front | URL publique déclarée dans le formulaire |

Le Setup propose les dépôts des interfaces à partir du dépôt
backend, et ils restent personnalisables. Le BO est obligatoire.

## CD initiales automatiques

Le développeur saisit ponctuellement son PAT fine-grained autorisé
à déclencher GitHub Actions (Actions:write sur les dépôts BO,
App, Front). Le Setup demande à GitHub de lancer deploy.yml sur
main pour chacun des composants déclarés. Si GitHub refuse,
la finalisation demeure réessayable, sans recreuser la base
ni recréer l'administrateur.

La réponse HTTP 204 confirme que GitHub a **accepté**
le déclenchement, pas que le build statique est déjà publié.
Vérifier la conclusion des Actions et leur destination VPS
fait partie du crash-test final.

Le token n'est pas enregistré et n'est pas un secret SSH.
Les documentations privées et les sources Node ne sont jamais
livrées dans les racines HTTP.

## Fermeture du Setup sur le VPS seulement

Le marqueur .symsvel/setup.json passe à completed après les
vérifications et l'acceptation des CD. Le fichier PHP natif
du bootstrap est ensuite supprimé du **checkout du VPS**.
Ni le poste local ni les dépôts GitHub ne sont touchés.
Un futur git pull peut restaurer ce fichier sans rouvrir
le wizard : completed reste le verrou d'installation.

Les autres routes HTTP Symfony restent indisponibles (503)
tant que la configuration et ce marqueur ne sont pas prêts.
PHP-FPM doit avoir le droit d'écrire les fichiers privés
de configuration et de lancer les commandes PHP CLI.
La variable SYMSVEL_PHP_CLI permet de désigner la bonne
version CLI du VPS si nécessaire.

L'import ne prend en charge que les dumps SQL texte .sql,
avec les outils natifs PostgreSQL, MySQL/MariaDB ou SQLite.
Aucun rollback automatique des opérations SQL n'est garanti.

## Validation

CI automatisée puis CT utilisateur : installation locale
de zéro, migrations sur les SGBD retenus, premier admin,
2FA, clone VPS, Setup prod, CD automatiques BO/App/Front,
et fermeture définitive du Setup même après git pull.
Aucune validation VPS n'est revendiquée avant ce parcours.
