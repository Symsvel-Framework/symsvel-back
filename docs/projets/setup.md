# Setup Wizard PHP natif

> Nouvelle implémentation du 22/11/2026, en PR draft.
> Voir [ADR 0011](../adr/0011-bootstrap-php-natif.md).

## Principe

Un seul wizard sert les deux environnements. Le front controller PHP
intercepte les quatre chemins **avant de démarrer le Kernel Symfony** :

| Mode | Première page | Deuxième page | Accès |
| --- | --- | --- | --- |
| Local | /setup | /setup/admin | Loopback uniquement |
| Production | /prod_settings | /prod_settings/admin | Première installation |

Le bootstrap PHP utilise Composer pour charger les validateurs existants,
pas le Runtime Symfony pour gérer les formulaires. Les opérations techniques
restent dans Tooling et les modules.

## Parcours local

La CLI lance automatiquement un serveur PHP sur 127.0.0.1 lors de
symsvel create-project. La commande symsvel setup reprend le wizard.

La phase 1 recueille le nom du projet, son domaine, les origines HTTP
réelles Back/BO/App/Front et les paramètres PostgreSQL, MySQL, MariaDB
ou SQLite. Les validateurs existants DatabaseConnectionConfig,
ProjectMetadata et ProjectUrlMapping sont réutilisés. L'installateur
génère backend/.env.local avec APP_ENV=dev, APP_SECRET aléatoire,
DATABASE_URL, les origines et les règles CORS exactes.

Il exécute ensuite symsvel:setup:database qui conserve la responsabilité
des migrations, de l'initialisation de Settings et des dictionnaires.
Après un échec, la commande est relançable depuis running sans effacer
les données ni ressaisir les identifiants.

La phase 2 initialise Languages, le premier administrateur, puis
déclenche symsvel:setup:complete (builds Svelte et marqueur completed).
L'authentification/2FA du BO reste contrôlée par les modules natifs.

## Parcours de production

Le développeur clone son Backend sur l'hébergement et exécute composer install.
Le domaine Backend doit pointer sur backend/public. Il ouvre ensuite
https://api.domaine.tld/prod_settings et remplit le même premier formulaire,
avec HTTPS obligatoire pour toutes les origines renseignées.

Le Setup enregistre backend/.env.local et secrets/.env avec APP_ENV=prod,
APP_DEBUG=0, un APP_SECRET aléatoire, DATABASE_URL et les origines.
Les URL des dépôts BO (obligatoire), App et Front sont stockées dans
.symsvel/production.json, sans le PAT GitHub.

En phase 2, le choix est migrations Doctrine et premier administrateur,
ou import d'un dump SQL texte contenant un administrateur existant.
Un PAT fine-grained Actions:write déclenche ensuite les trois CD
deploy.yml du projet, sur main.

Si GitHub refuse une demande de CD, le Setup n'est pas clos et la
finalisation peut être reprise avec un nouveau PAT, sans réinitialiser
la BDD. Une demande acceptée (HTTP 204) ne prouve pas la publication
réussie des fichiers sur le VPS.

## Sécurité et cycle de vie

Le .env Git est minimal et ne contient aucun secret de production.
Avant une installation terminée, les autres routes HTTP du Backend
renvoient 503, sans démarrer Symfony. Le wizard applique une session
PHP native, un jeton CSRF, SameSite Strict, HttpOnly et Secure sur HTTPS.

Le cycle persistant de Tooling est :
pending → running → database_ready → completed.

Une fois completed, le Setup refuse toute réouverture. Sur le VPS
uniquement, son fichier StandaloneSetup.php est supprimé. Un futur
git pull peut le restaurer, mais le marqueur completed persiste :
la route reste verrouillée. Aucun fichier du poste de développement
ou dépôt GitHub n'est modifié par le nettoyage du VPS.

Le compte serveur exécutant PHP doit pouvoir écrire dans .symsvel,
backend/.env.local et secrets/.env. Le binaire CLI PHP 8.4 et les
extensions PDO nécessaires doivent être disponibles. La variable
SYMSVEL_PHP_CLI peut désigner explicitement le CLI du VPS.

## Validation indispensable

La CI vérifie lint PHP, PHPUnit, et un smoke HTTP : le wizard s'ouvre
sans APP_SECRET, une route hors Setup reçoit 503 et un POST sans CSRF
est rejeté. Le chantier ne sera pas clos avant des installations
locales fraîches, les quatre SGBD ciblés, le CT VPS Choralys,
les CD BO/App/Front déclenchées depuis le Setup, les droits FPM
et la suppression effective du bootstrap après finalisation.

Les décisions et diagnostics historiques restent dans les ADR
et l'historique Git, sans être des instructions d'installation actives.

## Canal SymSvel sur un clone neuf

Le fichier privé backend/.symsvel/project.json ne se versionne pas.
Avant toute commande Tooling en production, le bootstrap lit la contrainte
symsvel-framework/core dans le composer.json du projet pour recréer
le canal dev (contrainte dev-*) ou stable (version de release).
APP_ENV=prod reste indépendant du canal des packages. Un canal déjà
persisté dans backend/.symsvel n'est jamais remplacé par le wizard.
