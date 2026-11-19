# ADR 0002 : Modules natifs minimalistes et extensibles

- Statut : acceptée
- Date : 2026-11-03

## Contexte

Les modules natifs doivent être utiles au plus grand nombre sans imposer des données ou fonctionnalités propres à certains métiers.

## Décision

Chaque module natif couvre uniquement son besoin commun et sa responsabilité. Il propose des points d'extension ciblés permettant de l'enrichir sans modifier son code source.

Exemple de périmètre minimal envisagé pour Users : nom, prénom, email, mot de passe et rôle. Les adresses, informations familiales et autres données métier n'appartiennent pas par défaut à ce socle.

Les extensions doivent privilégier les mécanismes existants de Symfony et Svelte lorsque cela répond au besoin. Aucun catalogue massif de hooks préventifs : un hook répond à un cas d'extension identifié.

Auth rejoint les modules natifs obligatoires avec Admins, Roles, ACLs et Settings : le BO étant systématiquement présent, son authentification doit être disponible dès l'installation. Le module Auth est partagé par les contextes BO et App, sans rendre App obligatoire. La 2FA reste une fonctionnalité distincte et optionnelle. Settings rejoint les modules natifs obligatoires (Admins, Roles et ACLs). Il fournit le stockage de paramètres applicatifs déclarés par les modules, sans transporter de logique métier ou de configuration d'infrastructure dans Back/Core. Users reste requis uniquement lorsque App est activée. La déclaration d'un module obligatoire ne valide pas à elle seule son installation, sa migration ni son fonctionnement multi-SGBD.

## Conséquences

- Les fonctionnalités supplémentaires sont développées dans des modules ou extensions distincts.
- Les modules natifs restent légers et faciles à adopter.
- On évite à la fois le module monolithique et la multiplication artificielle de micro-modules.
- On ne crée pas des dizaines de hooks pour personnaliser un détail d'interface.


## Languages : module natif obligatoire (16/11/2026)

Languages rejoint la liste des modules obligatoires de chaque
distribution, BO seul compris. Le référentiel des langues, ses
statuts, les traductions de noms et sa sélection lors du Setup
appartiennent à ce module, pas à Back ni Core. Le Setup appelle
un point d'entrée CLI spécifique du module pour initialiser la
langue principale sans importer sa logique métier. Le statut
de migration et les essais multibase doivent être validés avant
publication stable.

## Traductions natives et modulaires (18/11/2026)

Les traductions restent une responsabilité du **module natif Languages**, même
dans les projets monolingues : la langue principale est configurable lors du
Setup, et ses textes demeurent éditables depuis le BO sans activer le
multilinguisme. Aucun module « Translations » supplémentaire n'est introduit.

**Identité publique d'une clé :** `<scope>.<module|system>.<key>`, avec les
scopes BO, App et Front. Le stockage Languages conserve `scope` distinct de
`translation_key` (`module.key`), sans dupliquer le scope dans la BDD ni
introduire de colonne propriétaire dérivable. Le préfixe `system` est réservé
aux seuls composants natifs des hôtes Svelte sans propriétaire modulaire.
Même lorsqu'une route Auth est physiquement dans BO/App, ses libellés
fonctionnels appartiennent au module Auth. `generated` est un répertoire
réservé aux personnalisations publiées, jamais un nom de module propriétaire.

Chaque module ayant des textes par scope fournit des fichiers
`<scope>/src/lib/translations/<lang>.ts`. Le Module Maker crée le dossier
vide, sans clés fictives. Les dictionnaires TypeScript sont des exports d'objets
JSON stricts (`export default {"key":"Texte"} as const;`), lus comme données
sans exécution TypeScript. Ils sont exclus du formatage automatique pour
garantir ce contrat. Tooling déploie les sources dans l'hôte à
`<scope>/src/lib/translations/<module>/<lang>.ts`, et les textes
strictement natifs demeurent dans `.../translations/system/<lang>.ts`.

**Cycle de vie :** Tooling découvre les modules Composer installés et leurs
fichiers, prépare un inventaire validé, puis invoque Languages **après les
migrations** pour persister les clés et traductions initiales. Les valeurs
historiques ou modifiées dans le BO/CSV sont protégées ; seules les valeurs
livrées par défaut et non personnalisées peuvent être mises à jour. Le cycle
s'applique à l'installation de modules et à la mise à jour native des hôtes,
à l'intérieur des transactions et checkpoints existants. La lecture du runtime
reste statique dans SvelteKit : les surcharges sont publiées par Tooling en
`lib/translations/generated/<lang>.ts`, puis intégrées au build, sans
requêtes de dictionnaire runtime, Redis obligatoire ou serveur Node de prod.

**Parcours BO :** sélectionner la langue, afficher les modules réellement
installés et `system` avec pourcentages et compteurs, puis ouvrir le module,
filtrer et éditer. Les statistiques comptent les textes explicitement stockés
et non vides, **jamais les fallbacks**, et la progression globale est pondérée
par le nombre de clés. Une catégorie sans clé n'est pas affichée à 100 %.
Les scopes BO/App/Front sont proposés dans un module uniquement lorsqu'ils
disposent de clés.

Ce contrat n'est considéré terminé qu'après CI verte, tests automatisés,
essais de migrations multibase, crash-tests locaux validés par l'utilisateur
et documentation synchronisée.

## Countries : module natif obligatoire, menu conditionnel (19/11/2026)

Countries rejoint Languages parmi les **modules natifs installés sur tous les
projets**, BO seul compris. Le catalogue des pays et territoires et ses
migrations appartiennent au module Countries, jamais à Back ni Core. Le module
est initialisé lors du cycle normal d'installation/migrations, sans
consultation réseau ni migration conditionnelle au mode multilingue.

**Visibilité administrative :** Countries n'apparaît dans la navigation BO
que si le paramètre existant `languages.multilingual` vaut `1` et si
l'administrateur est autorisé à consulter les pays. L'API du module masque
son endpoint-sonde lorsque la fonction est désactivée. L'App et le Front
continuent à consulter les pays actifs, y compris en projet monolingue.
Le changement de paramètre peut déclencher une nouvelle vérification de
navigation sans rechargement ; le BO ne possède pas la règle Countries.

**État d'intégration :** portage en PR draft Countries ; recette préparée dans
le catalogue Flex, mais sa distribution distante, l'ajout au manifeste natif
`config/symsvel-native-modules.json` et le CT XT restent à réaliser.
Ne pas annoncer un projet installé avec Countries tant que ces opérations,
les tests de migrations multibase et la CI distribuée ne sont pas validés.

## Cadrage avant le prochain CT (19/11/2026)

**Countries** est natif obligatoire dans la distribution cible :
l'installation et la migration ont lieu même si le site reste monolingue.
Le BO n'affiche sa navigation que si `languages.multilingual` est activé
et si les ACL l'autorisent. Les données de pays restent disponibles pour
l'App/Front en mode monolingue. L'intégration dans le manifeste de Back
reste conditionnée aux preuves de distribution de la recette, tests de
migrations multibases et crash-tests ; la PR du module est encore draft.

**Storage**, contrairement à une décision historique de novembre, est
désormais un **module facultatif et différé**. Même si son contrat
local-first/fallback reste utile, il n'appartient pas à Core et ne doit
pas faire partie des dépendances natives du prochain Setup.

**Mails personnalisables** reste également une fonctionnalité modulaire
facultative, après la stabilisation du transport et des mails natifs.

Les **profils BO/App** sont à implémenter via les modules d'identité
respectifs et Auth, sans remplacer les permissions administratives par
une autorisation de libre modification d'autres comptes.

Les **alertes de configuration** appartiennent déjà au shell BO et à
Settings. Elles suivent les déclarations `required` et `configured`
des propriétaires, sans inventer un autre moteur d'onboarding.
