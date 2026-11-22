## Installation initiale après la refonte PHP native (22/11/2026)

Parcours cible : `git clone` du Backend dans son document root, puis
`composer install`, puis ouverture du wizard
`https://api.choralys.com/prod_settings`. Le wizard fonctionne sans
booter Symfony et crée une configuration sécurisée dans `.env.local`
avant d'utiliser les commandes CLI/Tooling. Le second écran propose
migrations/admin **ou** dump SQL, et déclenche les CD `deploy.yml`
depuis un PAT Actions:write fourni ponctuellement.

Le bootstrap source est supprimé **sur le VPS seulement** après succès.
La clôture par `.symsvel/setup.json` empêche la réouverture après
`git pull`. Les fichiers privés restent hors Git. Le HTTP 503 hors Setup
est attendu tant que la finalisation n'est pas terminée.

**État du chantier :** branche draft, CI + CT Choralys à valider. Ne pas
interpréter ces éléments comme une certification du déploiement.

---

# Premier déploiement d'un projet SymSvel

> **Statut : première implémentation sur `dev`, validation en cours.**
> Cette page concerne le déploiement **d'un projet créé avec SymSvel**, et non du framework SymSvel. Ni le parcours VPS ni l'import de dump n'ont encore été certifiés par crash-test.

## Préparation par le développeur

1. Créer les sous-domaines du projet (backend, BO et, selon le projet, App et Front).
2. Créer leurs répertoires cibles si l'hébergeur ne les crée pas automatiquement.
3. Se connecter à l'hébergement en SSH, se placer dans le répertoire backend,
   cloner **son dépôt applicatif backend dans le dossier courant**, puis exécuter `composer install`.

   ```bash
   cd /chemin/vers/backend
   git clone https://github.com/compte/monProjetBack.git .
   composer install
   ```

   Le point final de `git clone` évite de créer un sous-dossier supplémentaire.
   La racine HTTP du domaine backend doit viser `backend/public/`.
4. Ouvrir `https://backend.domaine.tld/prod_settings` dans le navigateur.
   Le même Wizard propose les modes **Installation locale** et **Déploiement production**.
   Le Setup est conservé dans le code versionné du projet ; seul son **état** est propre à chaque installation.

Le parcours de Setup est commun à l'installation locale et au premier
déploiement de production. L'écran 1 collecte nom du projet, domaine,
type/hôte/port/nom de BDD et identifiants. La branche production écrit
`backend/.env.local` et la copie privée `secrets/.env` avec `APP_ENV=prod`,
`APP_SECRET` et `DATABASE_URL`. L'écran 2 offre deux possibilités :
**migrations Doctrine** suivies de la création du premier administrateur,
ou **import d'un dump SQL** incluant les données et l'administrateur.
Le Setup s'appuie sur les commandes existantes des modules et de Tooling.
En cas d'erreur, il reste reprenable, sans promettre un rollback du dump.

Les dépôts Git des interfaces **appartiennent au projet utilisateur**.
Leurs URL (BO obligatoire, App et Front selon les composants installés) sont
proposées à partir du propriétaire du dépôt backend et restent modifiables.
Elles ne pointent pas vers les dépôts officiels `symsvel-bo`, `symsvel-app`
ou `symsvel-front`. Le projet récupère SymSvel comme dépendance.

## Conserver le Setup dans le projet déployé

Le **même Setup** sert à l'installation locale et au premier déploiement en
production. Il fait partie des fichiers du backend applicatif versionnés dans
le dépôt du développeur : **il ne doit pas être supprimé après la réussite
du Setup local** et doit être livré avec le projet lors du premier déploiement.

La fin du Setup local **ferme son accès dans cette installation** ; elle ne
supprime ni son point d'entrée ni son code. L'état d'installation, la
configuration et les secrets **sont propres à chaque environnement** :
ils ne doivent pas être transportés depuis la machine locale par Git.
Le backend fraîchement cloné en production doit donc pouvoir démarrer son
propre parcours de première initialisation, indépendamment du fait que le
développeur a déjà terminé le Setup sur son poste.

Après la première initialisation en production, l'accès au Setup est fermé
pour cette installation, sans supprimer les fichiers dont dépendrait une
nouvelle installation du même projet sur un autre hébergement.

**Point à valider dans l'implémentation :** l'entrée du Setup doit rester
utilisable sur le serveur cible après `composer install`, avec les
dépendances réellement livrées au projet. Cette capacité n'est pas encore
certifiée par les tests du Setup local.

## Prérequis des déploiements continus (CD)

**Pour automatiser le tout premier déploiement des interfaces et des
documentations, les CD des dépôts du projet doivent être configurées,
autorisées et opérationnelles avant le lancement de cette étape du Setup.**

Le développeur est responsable de préparer les workflows CD de son projet,
leurs accès et leurs destinations de publication. Dans la première implémentation
GitHub, le déclenchement attend un workflow **`deploy.yml`** activé sur la
branche **`main`**, contenant `workflow_dispatch`, avec un jeton de portée
limitée autorisé à déclencher les Actions (`Actions: write`) sur les dépôts.
Le jeton est fourni ponctuellement sur le deuxième écran et n'est pas enregistré. SymSvel ne crée ni ne
configure ces workflows à sa place. Connaître une URL GitHub ne suffit pas à
déclencher une CD : le workflow doit exister et pouvoir être déclenché par le
mécanisme autorisé du projet.

Lorsque les CD sont opérationnelles, le Setup **demande le déclenchement**
des actions du projet pour publier :

- les **builds statiques Svelte** du BO et, s'ils sont présents, de l'App
  et du Front, dans leurs dossiers d'hébergement respectifs ;
- les **documentations destinées à la production**, dans leurs
  emplacements respectifs, en respectant leur niveau d'accès.

Les interfaces peuvent résider sur un autre hébergement que le backend :
le Setup n'inspecte pas leurs répertoires distants. Une réponse HTTP `204`
de GitHub prouve uniquement que la demande de déclenchement est acceptée,
**pas** que le build a été transféré ou publié. Chaque CD est responsable
de vérifier la publication dans sa destination et d'en signaler l'échec.

La documentation développeur `dev-doc/` et son site MkDocs restent
**strictement locaux** et ne font pas partie de la publication de
production. Les guides administrateur/utilisateur et l'index documentaire
assemblé relèvent de la diffusion privée : ne jamais déposer
`backend/var/symsvel/documentation.json` dans une racine web publique.
Voir [le contrat documentaire de Tooling](https://github.com/Symsvel-Framework/symsvel-tooling/blob/dev/docs/documentation.md).

### Si aucune CD n'est prête

L'absence de CD **n'entraîne aucune publication automatique** des
interfaces Svelte ni des documentations. Le Setup doit signaler clairement
les composants et publications restant à effectuer, sans annoncer un
déploiement complet ni considérer un dossier vide comme un build installé.

Le développeur met ensuite en place ses propres CD et procède à leur
déclenchement et à la vérification des destinations. Cette étape reste
**de sa responsabilité** ; SymSvel ne fabrique pas un mécanisme de
déploiement de remplacement.

## Contraintes techniques de cette première implémentation

Lors de `composer install`, les dossiers de sous-domaines déjà créés
mais dépourvus de sources Svelte ne doivent plus bloquer la synchronisation
Composer : le hook Back et le synchroniseur Tooling distinguent un dossier
d'hébergement d'un projet Svelte réellement présent. Cela ne publie
**aucun** build ; les CD restent responsables de la livraison des interfaces.

- Le serveur doit disposer de PHP **CLI 8.4+**, accessible sous `php` depuis
  PHP-FPM pour lancer les commandes d'initialisation. Si l'hébergeur utilise
  plusieurs versions de PHP, `SYMSVEL_PHP_CLI` peut indiquer le bon exécutable.
  La possibilité de lancer des sous-processus PHP depuis le serveur web
  doit être disponible pour cette automatisation.
- Le serveur doit pouvoir exécuter `composer install` avec **Tooling présent**
  pendant le premier Setup. Un livrable `composer install --no-dev` autonome
  n'est **pas encore validé** : Tooling est actuellement en `require-dev`.
- Le dump via formulaire est un fichier **SQL texte `.sql`** ; les formats
  `.dump`, `.gz` et les archives ne sont pas pris en charge. Les limites PHP
  `upload_max_filesize` et `post_max_size` s'appliquent. Le serveur doit disposer
  du client `psql`, `mysql` ou `sqlite3` correspondant au SGBD choisi.
- L'import est effectué dans une base existante et peut modifier des données.
  Le développeur prépare une sauvegarde avant import ; aucun rollback automatique
  des opérations SQL importées n'est revendiqué.
- Le premier écran collecte les **URL réelles des scopes Back, BO, App et Front**, indépendamment des dépôts GitHub et des noms de dossiers. Ainsi, `https://manager.choralys.com` peut être renseigné comme URL de l'App sans renommer le scope `app`. Les URLs BO et App sont utilisées par le module Auth ; les URLs BO, App et Front génèrent les CORS exacts. Les URL optionnelles non renseignées n'autorisent aucune origine supplémentaire. En production, HTTPS est exigé pour les URL renseignées.
- Le Setup ne crée pas de workflow, de secret GitHub ni d'accès SSH pour le projet.
  Il ne déploie jamais les dépôts officiels SymSvel.
- L'accès distant `/prod_settings` est fermé lorsque l'état de ce déploiement
  devient `completed`. Le Setup local `/setup` reste limité au loopback.

## Critères de validation de la première implémentation

- Le mode local conserve son fonctionnement sans dépendre de CD.
- Le mode production peut proposer et corriger les URL des dépôts du projet.
- Une CD absente, inaccessible ou en échec est explicitement signalée ;
  aucun succès fictif n'est affiché.
- Le Setup ne déduit pas la réussite d'une CD de la présence de
  dossiers locaux : BO, App et Front peuvent être hébergés ailleurs.
  La vérification des fichiers publiés relève des CD propres au projet.
- Les documents réservés aux administrateurs et aux utilisateurs ne
  deviennent pas publics par erreur.
- Tests automatisés, CI verte, crash-tests locaux et documentation mise
  à jour sont nécessaires avant de déclarer le chantier terminé.

**État du chantier :** les modifications du Wizard sont poussées sur `dev`.
Tests automatisés et CI ne remplacent pas la réinstallation Choralys `--dev`,
son premier déploiement sur VPS, et la validation des vrais workflows CD
applicatifs. Aucune release stable n'est publiée par cette documentation.
