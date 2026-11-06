# Cycle de vie d'un projet

1. **Créer** : `symsvel create-project` installe Back, BO et, selon le choix, App et/ou Front, sans exiger de base préexistante.
2. **Initialiser** : `symsvel setup` configure le projet, la base et le premier administrateur.
3. **Utiliser** : le BO permet l'accès administrateur avec 2FA ; App, lorsqu'elle est installée, permet l'inscription et l'authentification utilisateur avec 2FA ; Front expose sa page initiale.
4. **Étendre** : les modules ajoutent des fonctionnalités et leurs ressources propres.
5. **Maintenir** : les mises à jour et suppressions passent par les outils prévus, avec contrôles et récupération en cas d'échec.
6. **Déployer** : livrer le runtime et les builds nécessaires ; ne pas exposer l'outillage local comme un service public.

**Contrat visé après setup :** projet immédiatement utilisable. Ce contrat décrit le comportement attendu, pas une certification que chaque étape est déjà implémentée et validée.

**Bases proposées au setup :** PostgreSQL, MySQL, MariaDB, SQLite. Le socle et les modules natifs doivent les supporter ; les modules métier tiers définissent leur propre compatibilité.
