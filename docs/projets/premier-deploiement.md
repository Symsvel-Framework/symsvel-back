# Premier déploiement d'un projet SymSvel

> **Statut : parcours cible en revue architecturale, non implémenté.**
> Cette page définit les prérequis et le comportement attendu du premier
> déploiement **d'un projet créé avec SymSvel**, et non du framework SymSvel.

## Préparation par le développeur

1. Créer les sous-domaines du projet (backend, BO et, selon le projet, App et Front).
2. Créer leurs répertoires cibles si l'hébergeur ne les crée pas automatiquement.
3. Se connecter à l'hébergement en SSH, se placer dans le répertoire backend,
   cloner **son dépôt applicatif backend**, puis exécuter `composer install`.
4. Ouvrir le Setup du projet dans le navigateur et sélectionner le mode
   **Déploiement production**.

Le parcours de Setup est commun à l'installation locale et au premier
déploiement de production. Les données de connexion à la base sont conservées
dans le premier écran ; le mode choisi oriente la suite. En production, le
deuxième écran propose soit l'exécution des migrations Doctrine et la création
du premier administrateur, soit l'import d'un dump.

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
leurs accès et leurs destinations de publication. SymSvel ne crée ni ne
configure ces workflows à sa place. Connaître une URL GitHub ne suffit pas à
déclencher une CD : le workflow doit exister et pouvoir être déclenché par le
mécanisme autorisé du projet.

Lorsque les CD sont opérationnelles, le Setup peut, dans le parcours cible,
déclencher les actions du projet pour publier :

- les **builds statiques Svelte** du BO et, s'ils sont présents, de l'App
  et du Front, dans leurs dossiers d'hébergement respectifs ;
- les **documentations destinées à la production**, dans leurs
  emplacements respectifs, en respectant leur niveau d'accès.

Les interfaces peuvent résider sur un autre hébergement que le backend :
le Setup n'inspecte pas leurs répertoires distants. Chaque CD est responsable
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

## Critères de validation de la future implémentation

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

Ce guide décrit **le comportement cible**. Les workflows et le déclenchement
automatique des CD de projets utilisateurs ne sont pas actuellement
implémentés par le Setup.
