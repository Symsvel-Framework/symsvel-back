## Bootstrap natif (refonte du 22/11/2026, branche de travail)

**Le premier écran d'installation ne démarre plus Symfony.**
`public/index.php` intercepte `/setup` et `/prod_settings` (ainsi que
leur page `/admin`) puis exécute le wizard PHP autonome
`src/Setup/StandaloneSetup.php`.

Ce wizard utilise Composer uniquement pour charger les classes PHP et les
validateurs déjà présents ; il appelle les commandes Symfony CLI/Tooling
**après** avoir enregistré un `.env.local` fonctionnel avec un
`APP_SECRET` aléatoire. Le code historique de `SetupController` et les
Twig associés sont remplacés, sans changer la CLI d'entrée `symsvel setup`.

Le runtime HTTP reste en 503 jusqu'à `.symsvel/setup.json: completed`.
L'accès aux pages Setup locales reste limité au loopback ; le Setup prod
reste limité à la première installation de ce serveur. Les formulaires ont
un jeton CSRF de session PHP. Le Setup prod utilise le même flux que la
version locale, sauf pour le choix migrations/dump et le déclenchement
Github Actions via PAT temporaire.

**Nettoyage production uniquement :** après état `completed`, le wizard
supprime son propre fichier natif de ce checkout du VPS. La réinstallation
du fichier par une future mise à jour Git n'autorise pas un deuxième Setup,
car le verrou d'état est indépendant. Ni les sources locales ni les dépôts
GitHub ne sont modifiés par le nettoyage serveur.

Le fichier `.env` du squelette est volontairement minimal et **ne
contient aucune configuration de production**. La configuration opérationnelle
vient de `.env.local`. Les tests historiques précédant le 22/11 restent
dans la documentation à titre de suivi, pas comme instructions actives.

**Validation requise :** CI native, CT CLI local, clone vierge sur VPS,
création de la BDD/admin et déclenchement des trois CD Choralys.
Voir [l'ADR 0011](../adr/0011-bootstrap-php-natif.md).

---

# Setup Wizard

Le Setup Wizard est lancé après la création des composants par la CLI. Il est accessible **uniquement en local** pendant la phase d'installation ; son contrôleur vérifie l'adresse du client, l'état `.symsvel/setup.json`, les permissions d'écriture et le jeton CSRF des formulaires.

```bash
symsvel setup
```

Cette commande permet de reprendre le Wizard après une interruption. La finalisation technique est déléguée à `symsvel setup:finalize` (commande Symfony `symsvel:setup:finalize`). Une interruption du formulaire n'implique pas que les changements déjà effectués dans la base aient été annulés.

## Conservation du Setup pour le premier déploiement (parcours cible)

**Décision de conception, première implémentation sur `dev` :** un projet SymSvel conserve le même Setup après
son installation locale, afin que le backend publié dans le dépôt Git du
développeur puisse l'exécuter lors du premier déploiement en production.
Le Setup terminé est **fermé dans l'environnement courant**, pas supprimé
du code source. Le projet cloné sur un autre serveur possède un état
d'initialisation propre et peut sélectionner le mode **Déploiement
production**. La configuration locale et les secrets ne sont jamais
versionnés ni réutilisés comme état de production.

Cette extension est **implémentée sur `dev`, mais doit être validée en CI et
par crash-test Choralys sur VPS**. `/setup` reste limité à la machine locale.
Le point d'entrée `/prod_settings` est accessible sur le serveur distant
tant que sa propre initialisation est incomplète. L'état absent après un
clone neuf y est initialisé sans copier l'état local. Une fois le Setup fini,
l'accès se ferme, mais le code demeure dans le dépôt.

Le premier écran de production configure `backend/.env.local` et sa copie
privée dans `secrets/.env` puis propose les URL des dépôts d'interfaces
appartenant **au projet utilisateur**. Le second choisit migrations Doctrine
avec création du premier administrateur ou import d'un dump SQL texte avec
administrateur déjà présent. La branche locale conserve son parcours initial.

Le déclenchement facultatif de la CD utilise `deploy.yml` sur `main` dans
les dépôts utilisateur, avec un jeton GitHub saisi ponctuellement.
L'acceptation par GitHub ne garantit pas la publication. Sans CD, le
développeur déploiera les builds et les documentations plus tard.
Voir le [guide du premier déploiement](premier-deploiement.md), notamment
les limites de `composer install`, du client SQL et des hébergements distincts.

## Périmètre de stabilisation PostgreSQL (novembre 2026)

Le chantier en cours stabilise le Setup en deux phases **sur PostgreSQL uniquement**. La prise en charge de MySQL, MariaDB et SQLite, l'import i18n et la 2FA sont explicitement reportés à leurs chantiers respectifs. L'ADR 0008 décrit la cible multibase, pas les moteurs déjà opérationnels.

### Parcours implémenté sur la branche de travail

1. `symsvel create-project` prépare les composants et leurs dépendances ; les builds Svelte sont différés pendant l'installation initiale.
2. `GET /setup` affiche le formulaire local : nom du projet, domaine local et paramètres PostgreSQL. Après validation CSRF, `DatabaseConnectionConfig` vérifie la connexion et détecte la version du serveur. `SetupController` écrit `backend/.env.local`, sauvegarde les métadonnées du projet puis appelle `symsvel:setup:database`.
3. La commande Tooling crée la base si nécessaire, compile et exécute les migrations des modules Composer, puis passe l'état à `database_ready`. Un journal de récupération de migrations en attente bloque la poursuite.
4. `GET /setup/admin` exige `database_ready`. Le formulaire crée directement le premier administrateur via `symsvel:admins:bootstrap:stdin` ; aucun `pending-admin.json` n'est créé par ce parcours.
5. `symsvel:setup:complete` vérifie l'administrateur via `symsvel:admins:bootstrap:verify`, lance les builds statiques des composants présents (BO, App, Front) et passe l'état à `completed` uniquement après réussite. En cas d'échec, une action distincte permet de relancer la finalisation sans recréer l'administrateur.

Le Setup est réservé aux accès locaux ; les formulaires vérifient les jetons CSRF. Le fichier `.env.local` est écrit avec des permissions restrictives. Le champ `channel` des métadonnées du projet est préservé.

### Validation et limites

**État : implémentation en PR draft, CI individuelles vertes, pas encore validée en crash-test XT.** Les tests automatisés et CI ne remplacent pas la validation du parcours distribué après merge via `dev-main`. La reprise après échec, les permissions effectives sous PHP-FPM, la présence des commandes du module Admins et le fonctionnement complet du Setup doivent être vérifiés avant de considérer la stabilisation terminée.

La commande historique `symsvel:setup:finalize` existe encore ; elle ne doit pas être confondue avec le nouveau parcours `symsvel:setup:database` puis `symsvel:setup:complete`. Le stockage historique d'administrateur temporaire reste dans le code mais n'est plus utilisé par ce parcours.

La compatibilité multibase est hors périmètre de cette stabilisation. Les migrations initiales des modules natifs contiennent encore du SQL spécifique à PostgreSQL ; elles ne sont pas portables en l'état.

## Audit multibase du 8 novembre 2026

L'examen des sources actuelles confirme que la compilation de Tooling extrait et concatène les corps `up()` et `down()` des migrations Doctrine, sans interpréter ni convertir les appels `addSql()`. L'exécuteur lance ensuite `doctrine:migrations:execute` dans l'ordre, journalise les opérations et tente de compenser les migrations déjà terminées en cas d'échec. Le journal de récupération bloque les nouvelles opérations en cas d'incertitude. Le mécanisme de compilation/exécution doit être conservé.

**Blocage concret :** les migrations initiales des modules natifs Admins, Roles, ACLs et Users utilisent `INT GENERATED BY DEFAULT AS IDENTITY`, `BOOLEAN DEFAULT TRUE/FALSE` et, pour Admins/Users, `TIMESTAMP(0) WITHOUT TIME ZONE` dans du SQL PostgreSQL. Le compilateur ne rend pas ce SQL portable. Activer les quatre choix dans le formulaire avant correction des sources donnerait des installations cassées.

**Adaptation retenue pour le chantier :** garder le contrat de migrations natives et le compilateur unique, mais exprimer les créations de tables/index dans les sources initiales des modules via l'API `Doctrine\\DBAL\\Schema\\Schema` / `Table` / `Column` et conserver les insertions de données compatibles via `addSql`. Cette stratégie nécessite de vérifier le comportement de `Schema` dans les migrations compilées et le SQL réellement exécuté sur chaque moteur avant de modifier des migrations déjà publiées. Les migrations enregistrées sont immuables : toute migration déjà reçue doit rester reconnue par son empreinte, et une stratégie de compatibilité est obligatoire pour les installations existantes. Ne pas réécrire silencieusement les fichiers de migrations déjà distribués.

La configuration `DatabaseConnectionConfig` et le formulaire Back restent PostgreSQL uniquement. Les extensions de connexion devront produire les URL Doctrine adaptées à PostgreSQL, MySQL, MariaDB et SQLite, avec détection de version pour les moteurs serveur et sans forcer une version pour SQLite. Le chemin SQLite doit être confiné au workspace ; les informations de connexion doivent rester dans `.env.local`. Les pilotes PDO nécessaires seront vérifiés explicitement et les tests de connexion et migrations devront être réalisés pour chaque moteur.

**Validation requise avant activation du sélecteur :** tests unitaires des URL/validations, tests de compilation et d'immuabilité, tests d'intégration des migrations natives sur les quatre moteurs, reprise après erreur, puis crash-tests XT. Aucun moteur supplémentaire n'est déclaré fonctionnel à ce stade.

### Diagnostic de la phase 2 (CT XT)

La finalisation depuis `/setup/admin` lance `symsvel:setup:complete` comme sous-processus Symfony. En cas d'échec, le formulaire local affiche désormais la sortie de cette commande au lieu de masquer le diagnostic derrière un message générique. Le statut reste `database_ready` et la finalisation est relançable sans recréer le premier administrateur. Le succès de la commande en terminal ne valide pas le parcours navigateur : un CT XT complet reste obligatoire.

## Bilan CT XT multi-SGBD du 11 novembre 2026

Les crash-tests d'installation sur le workspace XT vierge ont abouti avec **PostgreSQL, MySQL, SQLite et MariaDB**. Le parcours validé comprend la création/configuration de la base, l'exécution des migrations natives Doctrine Schema (Admins, Roles, ACLs, Users), la création du premier administrateur, les builds Svelte et le routing. Pour MariaDB, le contrôle SQL a confirmé les tables `doctrine_migration_versions`, `symsvel_acl_rules`, `symsvel_admins`, `symsvel_roles` et `symsvel_users`, ainsi qu'un administrateur activé avec le rôle `ROLE_DEV`. Ne pas publier d'identifiants ni de hashes issus des CT.

**Incident de CT distinct du fonctionnement multi-SGBD :** une tentative a été perturbée par un terminal de Setup précédent resté ouvert pendant la recréation du workspace. Les états `running` et `recovering` observés dans le nouveau workspace et le refus du bootstrap ne constituent pas une preuve de défaut Doctrine ou MariaDB. L'interaction exacte entre processus n'a pas été tracée ; la protection contre deux installations concurrentes reste à auditer, sans modification architecturale décidée à ce stade. Pour les prochains CT, arrêter tous les processus de Setup avant de supprimer/recréer XT.

**Périmètre de validation :** ces résultats valident l'installation initiale et les contrôles fonctionnels mentionnés, pas les parcours `require/update/remove`, les rollbacks, le canal stable via Satis, la 2FA ou l'i18n. Avant publication : vérifier les CI sur les commits de release, les versions et dépendances inter-dépôts, les documents et les parcours de maintenance encore requis. Le suivi npm temporaire doit être traité explicitement avant release.

### Origines CORS locales

Depuis le mapping d'URL du Setup (`dev`, 22/11/2026), le premier écran associe les **scopes logiques** Back, BO, App et Front à leurs **origines HTTP(S) réelles**. Les champs sont proposés avec les sous-domaines conventionnels, mais restent entièrement modifiables ; App peut par exemple être `https://manager.choralys.com`. App et Front peuvent rester vides s'ils ne sont pas installés. Ce mapping **ne renomme aucun dossier** et ne dépend pas de la localisation des hébergements.

Le Setup publie `SYMSVEL_BACK_ORIGIN`, `SYMSVEL_BO_ORIGIN`, `SYMSVEL_APP_ORIGIN`, `SYMSVEL_FRONT_ORIGIN` dans `backend/.env.local`. La valeur `SYMSVEL_CORS_ALLOW_ORIGIN` est générée à partir des **seules origines BO, App et Front renseignées**, avec comparaison exacte de protocole, hôte et port, sans joker de sous-domaine. Le Back étant le serveur API recevant les requêtes, son origine n'est pas ajoutée à la liste CORS. En production, chaque origine déclarée doit utiliser HTTPS.

Les origines BO et App alimentent également le contrat natif du module Auth (contrôle de l'origine du navigateur BO et liens d'activation/invitation). En local, l'HTTP est accepté uniquement dans les conditions de développement déjà prévues par Auth. La valeur historique `SYMSVEL_BO_DOMAIN` est conservée pour compatibilité, mais les origines explicites du mapping ont priorité.

Le Setup remplace ses variables générées sans doublons lors d'une nouvelle saisie. Le mapping est une **configuration d'environnement**, jamais une découverte DNS ou une configuration des dépôts Git/CD. Validation : tests automatisés et crash-test Choralys local puis VPS.

## CT XT : résolution des dépendances Mails en canal dev (16/11/2026)

Lors de l'installation vierge `symsvel create-project --components=all --dev`, Composer rejetait `symsvel-framework/module-mails:dev-dev` comme dépendance transitive d'Admins, Auth et Users, car `minimum-stability: stable` est conservé dans le squelette. Le manifeste `config/symsvel-native-modules.json` demande désormais **Mails directement** parmi les modules natifs de tout projet, y compris BO seul. La CLI et le Tooling existants l'installent dans la même transaction que les autres modules avec la contrainte explicite `dev-dev` en canal de développement ; les dépôts VCS sont configurés dans le checkpoint transactionnel. Aucune ouverture générale à la stabilité `dev` et aucune manipulation manuelle du `composer.json` de XT ne sont nécessaires. Le test `NativeModulesManifestTest` protège cette présence ; la résolution Composer réelle reste à confirmer par le CT utilisateur puis les CI.

## Indicateur de mot de passe au bootstrap local (16/11/2026)

La seconde phase du Setup montre une jauge de diversité sur le mot de passe du premier administrateur et vérifie côté navigateur les limites **déjà appliquées côté PHP** : de 12 à 1024 octets UTF-8 sans caractère NUL et double saisie identique. Les majuscules, chiffres et symboles enrichissent seulement l'indicateur et ne deviennent pas des obligations supplémentaires. L'attribut HTML `minlength` est retiré car il compte les caractères, contrairement à la validation PHP en octets. Aucun secret n'est enregistré ou transmis par la jauge, aucune modification du flux serveur, ni du contrat du Setup. CI et nouveau crash-test local requis.


## Transports d'e-mails déclaratifs et tests locaux (16/11/2026)

Le squelette Back définit les transports nommés Symfony Mailer
`smtp` (`MAILER_DSN`), `mailpit` (SMTP local `127.0.0.1:1025`),
`mailhog` (même port local, alternatif), `smtp4dev`
(`127.0.0.1:25`) et `mailtrap` (`MAILTRAP_DSN`). Mails propose
les choix dans Settings et place l'en-tête `X-Transport` sur chaque
message pour que Symfony sélectionne **un seul** transport. Aucun
changement de `.env.local` n'est effectué depuis les requêtes HTTP.

**Pas de détection ni d'installation :** ces transports sont proposés
même si les exécutables/serveurs ne sont pas présents. Le développeur
doit les installer, les lancer et adapter les DSN ou ports lorsque
nécessaire. La présence dans le dropdown ne garantit ni disponibilité
ni réussite d'un envoi. Mailtrap nécessite les identifiants de la
sandbox hébergée ; l'exemple `.env` ne comporte **aucun secret** et
ne fonctionnera pas sans configuration. Pour les tests isolés, Mailpit
et MailHog n'exigent aucun compte externe.

Les transports externes supplémentaires pourront être fournis par des
modules possédant leur propre configuration Symfony nommée ; le
registre Mails ne scanne aucun service réseau et n'a pas d'autorisation
pour modifier les fichiers d'environnement à l'exécution.

QA : vérifier les cinq noms dans `mailer.yaml`, le routage
`X-Transport` et l'absence de fallback. Crash-test XT : le développeur
lance manuellement le serveur mail de son choix et vérifie qu'un vrai
mail y arrive ; les CI ne simulent pas son installation.


## Rectification du Setup des langues (17/11/2026)

Le choix obligatoire de langue principale ajouté le 16/11 a été retiré du Wizard : il confondait la préférence linguistique du premier administrateur avec la langue principale du projet. Le Setup ne doit pas lire le référentiel Languages en base pour afficher le formulaire du premier administrateur. Le formulaire ne sollicite plus `symsvel_languages_setup_choices`, ne contient plus de sélection de langue principale et n'appelle plus `symsvel:languages:bootstrap:stdin` avant le bootstrap Admins.

**Décision de parcours :** le premier administrateur reçoit le code ISO de la préférence navigateur (`Accept-Language`, langue primaire sans sous-tag), transmis par stdin au bootstrap Admins sans requête au référentiel Languages. La colonne `symsvel_admins.language` est persistée par le module Admins avec migration additive, sans relation SQL avec Languages. Une reprise ne remplace pas une préférence déjà enregistrée. En l'absence d'en-tête navigateur reconnu, le repli technique est `en` ; la préférence de langue effective après connexion reste à valider dans les clients BO.

Le module Languages demeure natif, propriétaire du catalogue, des réglages linguistiques globaux et de leur initialisation à l'étape appropriée, après disponibilité de ses données. Aucun mécanisme supplémentaire n'est introduit dans Core ou Back. La commande de bootstrap Languages existante n'est pas supprimée : seul son appel prématuré depuis le Wizard est retiré.

**Validation requise :** PHPUnit/CI sur `dev`, puis CT depuis zéro en vérifiant le rendu de `/setup/admin` sans accès au catalogue Languages, la création du compte et l'absence d'erreur si les langues ne sont pas encore peuplées. La préférence de langue effective du compte reste une vérification distincte tant que sa persistance n'est pas implémentée.

## Langue de compte Admins et Users (17/11/2026)

Les deux modules conservent une colonne `language` nullable (ISO 639 alphabétique, 2 à 4 lettres, sans FK). Les écrans de création/modification BO consultent `GET /apibo/languages/account-options`. Avec une seule langue active, aucun sélecteur n'est rendu et le serveur impose la langue principale du site ; avec plusieurs, les langues actives seules sont sélectionnables. Les langues `pre_open` non actives sont exclues. La langue du compte et la langue d'envoi d'un message restent deux notions distinctes.

Les API de compte utilisent `LanguageCatalog::resolveAccountLanguage` pour contrôler la valeur ; la préférence personnelle du premier développeur ne requiert pas ce catalogue avant son initialisation. L'initialisation de la langue principale du site et la validation du parcours App d'inscription sont des points distincts à vérifier en CT. CI, migrations et builds Svelte restent à valider avant clôture.

## Initialisation automatique et repli des traductions (17/11/2026)

À la phase 2 du Setup, après la compilation/exécution des migrations, la préférence `Accept-Language` du navigateur est réduite au code ISO de langue. Si l'en-tête n'est pas exploitable, le repli initial est `fr`. Le Setup transmet ce code à `symsvel:languages:bootstrap:stdin` avant la création du compte `ROLE_DEV`. Languages active la langue principale et la conserve dans Settings de façon idempotente ; aucune lecture de ses tables n'est effectuée avant les migrations. Le même code ISO est transmis au bootstrap du premier administrateur.

Le **fallback français pour les traductions** est une règle de rendu : lorsqu'aucun fichier de traduction ne correspond à la langue demandée, les textes proviennent du fichier français disponible. La langue ISO enregistrée pour le site ou le compte ne doit pas être remplacée par `fr` à cause de cette absence. Les clients BO/App/Front et la chaîne i18n devront vérifier cette résolution de fichiers. Les tests end-to-end et les CI restent nécessaires.

## Envoi immédiat des e-mails (17/11/2026)

La configuration Symfony Messenger du Back route désormais `Symfony\\Component\\Mailer\\Messenger\\SendEmailMessage` vers le transport natif `sync://`, et non vers la file Doctrine `async`. Les mails d'invitation, de confirmation et de récupération sont donc **tentés dans la requête qui les déclenche**, sans attendre l'exécution de `messenger:consume`. La sélection du transport SMTP nommé par le module Mails (`smtp`, `mailpit`, `mailhog`, `smtp4dev`, `mailtrap`) ne change pas. Un envoi SMTP lent peut allonger la requête et une panne du transport reste traitée par la logique métier du module émetteur ; aucune garantie de livraison n'est déduite du seul retour du mailer.

Les autres messages routés (Notifier Chat/SMS) restent en `async` et la file `failed` est conservée. Le test `SynchronousMailerRoutingTest` protège ce périmètre. CI et crash-tests HTTP/SMTP locaux restent requis avant validation finale.
