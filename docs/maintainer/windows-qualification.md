# Qualification du support Windows natif

## Périmètre et état initial

Cible : Windows 11 x64 maintenu, PHP 8.4, Composer 2, Git, Node/npm et Python. Windows natif sans WSL2, Docker ni shell Unix obligatoire. Ne pas modifier les contrats de Core, Back ou des modules pour des besoins relevant de l'outillage, sauf nécessité établie. Les règles de versions de l'ADR 0007 restent applicables.

Audit initial (code de dev) : le lanceur CLI est un script à shebang Unix ; `Cli::available` invoque /bin/sh ; `RuntimePermissions` exige macOS/Linux et POSIX ; `DevDocumentationBuilder` utilise .venv/bin/python et .venv/bin/mkdocs. L'ouverture du navigateur Windows et la recherche de npm.cmd sont déjà prévues partiellement. La CI CLI couvre Linux/macOS, pas Windows. Ceci n'est pas un audit exhaustif : analyser aussi les chemins, liens, permissions, appels Git/Composer/npm/PHP, processus Setup, imports/exports de base, opérations de maintenance, rollback et tests utilisant rm/chmod/ps. Ne pas déclarer Windows supporté avant validation.

## Critères techniques avant le CT indépendant

- CLI installable depuis PowerShell/invite de commandes avec un lanceur natif et PATH utilisateur, doctor fiable, propagation correcte des arguments et codes de retour ; chemins avec espaces et caractères non ASCII.
- Opérations de fichiers et processus compatibles sans utilitaires Unix obligatoires, avec protection des secrets et des chemins. Stratégie de droits Windows explicite, ciblée, sans accorder de droits généraux à Everyone ni exiger une CLI administrateur.
- Setup loopback, création SQLite et migrations, création premier administrateur, builds BO/App/Front, parcours navigateur ; reprise et traitement d'échec.
- Site MkDocs généré avec le Python Windows et son venv Scripts, ouvert localement ; navigation et recherche vérifiées.
- Maintenance et rollback sur données jetables ; tester les moteurs SQL annoncés avant de généraliser le support.
- CI Windows réelle (au minimum CLI et Tooling), CI existantes Linux/macOS vertes et documentation actualisée. Vérifier les contrats inter-dépôts avant les releases. 

## Fiche de crash-test indépendant

À remettre au testeur après adaptation du code et passage de la CI Windows. Ne pas fournir d'assistance préalable : le testeur suit uniquement la documentation publique. En cas de blocage, noter le dernier point réussi et le message complet expurgé des secrets avant toute intervention. Ne pas utiliser un projet réel.

| Étape | Résultat attendu | Verdict |
| --- | --- | --- |
| Poste et prérequis | Versions, PATH, extensions PHP et venv vérifiés depuis PowerShell | À tester |
| CLI | Installation et doctor depuis un nouveau terminal, utilisateur standard | À tester |
| Création dev | Projet neuf, composants all, dépendances dev-dev cohérentes | À tester |
| Setup | Wizard, SQLite, migrations et premier admin | À tester |
| HTTP | Hôtes locaux, accès BO, App, Front, healthcheck, CORS | À tester |
| Auth | Connexion, 2FA selon la politique du projet, inscription et mails via transport de test configuré | À tester |
| Documentation | Build MkDocs, navigation, liens, sommaire, lecture hors ligne | À tester |
| Développement | Créer une API métier et l'afficher depuis une interface | À tester |
| Module | Créer un module de test, l'installer, le mettre à jour, le retirer | À tester |
| Mise à jour | Mise à jour sans écrasement du code métier ni changement de canal | À tester |
| Reprise et rollback | Échec contrôlé sur copie jetable et reprise documentée | À tester |
| Stable | Installation vierge sans --dev après publication effective | À tester |

Compte rendu : version Windows et architecture, versions PHP/Composer/Git/Node/npm/Python, shell, serveur HTTP, SGBD, version ou révision SymSvel, commandes exactes, durée par étape, résultat et logs expurgés. Ajouter une colonne « Où ai-je dû demander de l'aide ? » : toute intervention non documentée est une anomalie documentaire à corriger, même si l'installation finit par fonctionner.

Clôture : aucune anomalie bloquante non résolue, CI verte, CT indépendant validé par le mainteneur et guides à jour. La disponibilité du matériel Windows du testeur ne constitue pas une validation préalable.

## Première tranche d'adaptation (26/11/2026)

Le CT initial a confirmé l'échec de `/bin/sh` et la limitation POSIX des ACL. CLI : lanceur CMD, recherche PATH native, Composer PHAR, nettoyage du Git upstream et cas local sous le compte CLI ; CI Windows bootstrap verte après correction de la détection Composer. Tooling PR #85 : venv Windows et vraie navigation MkDocs validés dans la première CI, résolution native Composer/npm et normalisation des clés de fichiers ajoutées ensuite (CI à confirmer). Restent notamment : nettoyage des composants, chemins et rollback transactionnels, Setup complet, gestion NTFS sous des comptes distincts, tests d'installation réelle et CT étudiant. Ne pas promouvoir le support Windows stable ni relancer le CT complet sur cette seule base.

Le test de résolution native dev retire le dépôt Satis stable à son adresse actuelle (`packages.symsvel.com`) ainsi qu'à l'ancienne adresse. Il ne faut pas masquer l'audit Composer. La mise à jour du verrou Symfony vers les correctifs de sécurité 8.1 est un prérequis du CT.

Le lock Symfony a été régénéré par Composer sur le runner GitHub PHP 8.4 (job 140090826723) : Symfony 8.1.12 pour les composants concernés, Flex 2.12, validation du manifeste et audit sans avis de sécurité. Le content-hash du lock correspond au manifeste mis à jour. Le job temporaire de génération est retiré ; la CI normale vérifie le lock commité.
