# Préparer un poste Windows

## État du support

Windows natif est une cible de SymSvel, sans WSL2 ni Docker obligatoires. **Ce parcours est en préparation : l'installation complète n'est pas encore validée.** Vous pouvez dès maintenant préparer les outils ci-dessous. Ne lancez pas un projet métier tant que la CLI et le parcours de création natifs ne sont pas déclarés compatibles.

Ce guide cible Windows 11 64 bits maintenu. Les autres versions et architectures ne sont pas encore qualifiées. Utilisez PowerShell ou l'invite de commandes Windows ; les exemples ci-dessous utilisent PowerShell. Il n'est pas nécessaire de désactiver les protections de Windows ni d'exécuter le projet en administrateur.

## 1. Installer les outils

| Outil | Installation |
| --- | --- |
| PHP | [PHP 8.4 pour Windows](https://www.php.net/downloads.php?os=windows&version=8.4), [instructions PHP](https://www.php.net/manual/fr/install.windows.manual.php) |
| Composer | [Composer 2, installation Windows](https://getcomposer.org/doc/00-intro.md#installation-windows) |
| Git | [Git pour Windows](https://git-scm.com/download/win) |
| Node.js et npm | [Installateur Node.js](https://nodejs.org/en/download/), Node 24 utilisé par la CI actuelle du BO |
| Python | [Python pour Windows](https://www.python.org/downloads/windows/), avec pip et venv |
| SGBD | SQLite pour commencer ; ou PostgreSQL, MySQL, MariaDB si vous avez déjà un serveur |

### PHP

Téléchargez une version maintenue de PHP 8.4 adaptée à votre architecture et à votre serveur HTTP. Pour la CLI et un serveur FastCGI, utilisez le build NTS ; pour Apache avec PHP chargé comme module, le build TS correspondant à Apache. Installez le runtime Visual C++ indiqué sur le site PHP si nécessaire.

Décompressez PHP dans un répertoire dédié, par exemple `C:\Tools\php84`. Copiez `php.ini-development` en `php.ini`, configurez `extension_dir` vers le dossier `ext` et activez les extensions nécessaires : PDO et le pilote de la base retenue, intl, mbstring, openssl, curl, zip, sodium et celles signalées par Composer. Certaines extensions sont déjà intégrées au binaire ; vérifiez leur présence avec `php -m` avant de modifier le fichier. Pour SQLite, activez `pdo_sqlite`. Ne chargez pas une DLL d'une autre version de PHP.

Ajoutez le dossier PHP au PATH de votre utilisateur via les variables d'environnement Windows, puis ouvrez un nouveau terminal. L'installation Composer-Setup doit utiliser ce même `php.exe`.

### Composer, Git, Node.js et Python

Utilisez les installateurs officiels et rendez leurs exécutables accessibles depuis le PATH utilisateur. Fermez puis rouvrez PowerShell après installation. Node inclut npm ; si PowerShell bloque le script npm.ps1, utilisez `npm.cmd` dans les vérifications, sans modifier globalement sa politique d'exécution. Pour Python, la commande `python` doit lancer un interpréteur installé, pas une redirection vers le Store.

~~~powershell
php -v
php --ini
php -m
composer --version
git --version
node --version
npm.cmd --version
python --version
python -m pip --version
~~~

Contrôlez les chemins réellement utilisés :

~~~powershell
Get-Command php, composer, git, node, npm.cmd, python
~~~

Si plusieurs versions d'un outil coexistent, vérifiez que la bonne est prioritaire dans le PATH.

Vérifiez également la création d'un environnement Python, hors de tout projet :

~~~powershell
$venv = Join-Path ([System.IO.Path]::GetTempPath()) ("symsvel-check-" + [guid]::NewGuid())
python -m venv $venv
& (Join-Path $venv "Scripts\python.exe") -m pip --version
~~~

Ce test crée seulement un dossier temporaire ; vous pourrez le supprimer une fois le contrôle terminé.

## 2. Préparer le serveur local et la base

Le Setup SymSvel ne fournit pas le serveur HTTP permanent de votre projet. Pour le premier test, choisissez un serveur HTTP local compatible PHP 8.4 (par exemple Apache avec une intégration PHP adaptée), et SQLite pour limiter les dépendances. SQLite ne nécessite pas de serveur SQL ; le processus PHP doit pouvoir écrire dans le répertoire de la base et de ses fichiers auxiliaires.

Le serveur HTTP devra servir les dossiers suivants, après création du projet :

| Hôte d'exemple | Dossier à servir |
| --- | --- |
| api.catalogue.test | backend/public |
| bo.catalogue.test | bo/build |
| app.catalogue.test | app/build |
| www.catalogue.test | front/build |

Les adresses doivent résoudre vers la machine locale et correspondre exactement aux origines renseignées dans le wizard (schéma, hôte et port). Préparez vos hôtes virtuels selon la documentation de votre serveur. Les routes applicatives devront être testées lors de la qualification du serveur choisi. Ne rendez jamais accessibles la racine du projet, les secrets, vendor ou les bases SQLite.

Pour la base, choisissez un fichier à l'intérieur du backend, hors de public. Si vous utilisez un autre SGBD, préparez son service et un compte autorisé à créer la base du projet. Les réglages des droits HTTP Windows devront être validés dans le chantier natif : ne transposez pas les commandes chmod ou setfacl de Linux.

## 3. Installer la CLI : première qualification native sur dev

Les commandes d'installation macOS/Linux ne sont pas des commandes PowerShell. Le lanceur Windows `symsvel.cmd` est disponible sur la branche `dev` de la CLI. Le contrôle des prérequis et les primitives de bootstrap ont passé la première CI Windows native ; cela ne valide pas encore une création complète.

~~~powershell
git clone --branch dev https://github.com/Symsvel-Framework/symsvel-cli.git "$HOME\symsvel-cli"
$env:Path = "$HOME\symsvel-cli\bin;$env:Path"
symsvel.cmd doctor
~~~

Si le dépôt CLI est déjà présent, placez-vous dans ce dépôt et utilisez `git switch dev`, puis `git pull --ff-only`. Ajoutez le dossier `bin` au PATH utilisateur pour rendre le lanceur accessible après réouverture du terminal. Utilisez Composer-Setup (composer.phar), Node.js/npm et Python installés normalement, sans modifier la politique d'exécution PowerShell.

Le Setup intégré utilise le compte qui exécute la CLI ; aucune ACL NTFS n'est modifiée dans ce cas. Un serveur HTTP sous un compte distinct exige encore une configuration de droits spécifique, sans transposer chmod/setfacl. Le build documentaire Windows utilise désormais `Scripts/python.exe` dans la PR Tooling #85, dont l'intégration et le parcours complet restent à qualifier.

Ne lancez pas encore la création d'un projet métier : les transactions, les interfaces et le Setup complet doivent passer leurs tests Windows. L'objectif reste de conserver les mêmes commandes SymSvel sur les trois systèmes, sans retoucher les sources installées.

Le [parcours de création](03-creer-un-projet.md) et le [Setup local](04-setup-local.md) restent les étapes communes après qualification.

## Alternative facultative : WSL2

Si vous souhaitez utiliser un environnement Linux sur Windows, [installez WSL2 selon Microsoft](https://learn.microsoft.com/fr-fr/windows/wsl/install). Installez les outils Linux dans la distribution, conservez le projet dans son système de fichiers Linux et exécutez les commandes dans son terminal. Reportez-vous aux [prérequis](01-prerequis.md) et à [l'installation CLI macOS/Linux](02-cli.md). Les hôtes locaux et l'accès HTTP depuis le navigateur Windows doivent aussi être configurés ; ce parcours n'est pas encore validé par SymSvel.

WSL2 reste facultatif et ne remplace pas la qualification du parcours Windows natif.
