# ADR 0005 : Un projet utilisable après le setup

**Statut : acceptée**  
**Date : 2026-11-06**

## Contexte

L’installation doit aboutir à une application exploitable, pas à un squelette à compléter manuellement.

## Décision

- Le BO est obligatoire ; App et Front sont optionnels.
- `create-project` prépare le projet sans base obligatoire.
- `setup` configure la base, le projet et le premier administrateur.
- Après setup, le premier administrateur accède au BO via Auth **uniquement après enrôlement et validation TOTP**. Chaque nouvelle session BO exige un second facteur, sans paramètre de désactivation.
- Avec App, inscription et connexion utilisateur via Auth ; le module TwoFactor est préinstallé mais la politique 2FA App reste configurable. Un compte App déjà enrôlé reste protégé.
- Avec Front, page initiale accessible.
- Modules natifs requis : Admins, Roles, ACLs, Settings, Mails, Auth et TwoFactor (y compris en BO seul) ; Users si App est installée, y compris ultérieurement. Auth et TwoFactor sont installés par le manifeste du Back lors de `create-project`, avant le Setup Wizard. TwoFactor reste un module distinct, mais **sa présence et la vérification 2FA BO sont obligatoires** dans la distribution standard. La connexion Google et les éventuels fournisseurs OIDC sont exclusivement réservés à App et ne sont pas installés tant que leur module dédié n'existe pas.
- Chaque module fournit ses pages et processus selon ses scopes.

## Justification

Une installation doit livrer un résultat directement vérifiable.

## Conséquences

Le dashboard initial du BO reste minimal. Aucun système de widgets générique n’est conçu avant besoin réel. La vérification du second facteur intervient avant l'émission de la première paire de tokens BO. Si le provider TwoFactor est absent ou ne produit aucun challenge, Auth refuse la connexion BO sans émettre de session ; l'absence de provider reste acceptée pour App lorsque sa 2FA n'est pas activée.

## Précision d'intégration Composer (16/11/2026)

Auth (et Admins/Users) dépend de Mails. Le manifeste du Back demande donc explicitement Mails avec les autres modules natifs. En canal de développement, la CLI applique à chacun la contrainte racine `dev-dev`, ce qui autorise Composer à résoudre cette dépendance sans modifier `minimum-stability: stable` pour le reste du projet. Cette précision corrige un échec réel de `create-project --components=all --dev` observé sur XT ; la reprise du CT doit confirmer la résolution effective.

## Validation technique restante

Valider dans les crash-tests les quatre combinaisons bo/app/front/all, l’ajout différé d’App, l'enrôlement TOTP du premier administrateur, le fail-closed sans provider, les codes de secours et l'activation/désactivation de la politique App.


## Native Languages et phase 2 (16/11/2026)

La phase 1 installe et migre Languages en même temps que les autres
modules natifs. La phase 2 demande la langue principale, non
présélectionnée, puis le premier administrateur. Le moteur
d'initialisation de la langue est dans Languages ; Setup appelle
sa commande console par entrée standard locale. La sélection
est définitive et la langue choisie reste active.
Le multilinguisme public demeure désactivé tant qu'il n'a pas été
activé dans Settings. Le test de démarrage complet reste exigé.

## Countries : installation native (19/11/2026)

Le manifeste `config/symsvel-native-modules.json` demande désormais
`symsvel-framework/module-countries` **après Languages** pour tous les
projets, y compris ceux sans App, Front ou multilinguisme. Countries garde
ses données disponibles via `/apiopen/countries` ou `/api/countries` ;
l'interface d'administration est masquée tant que
`languages.multilingual` n'est pas activé.

**Distribution :** la recipe Countries a été déclarée prête par
le mainteneur et sa structure a été vérifiée dans `symsvel-recipes/main`
(bundle et import des routes). La disponibilité effective via le
catalogue Flex distant et l'application sur XT doivent toujours être
validées par une installation neuve. La présence du module dans le
manifeste ne prouve ni la résolution Composer, ni l'exécution des
migrations : les crash-tests PostgreSQL/MySQL/MariaDB/SQLite, les ACL et
le menu BO conditionnel restent des critères de validation.
