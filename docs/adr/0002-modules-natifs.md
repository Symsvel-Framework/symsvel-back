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
