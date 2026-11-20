# Sécurité

Les modules natifs Admins, Roles et ACLs constituent le socle de gestion des identités et autorisations d'administration. Auth et TwoFactor sont installés avec le BO ; **la 2FA est obligatoire pour toute nouvelle connexion administrateur** et ne peut pas être désactivée dans Settings. Users est requis lorsque l'App est installée, dont la politique 2FA est configurable.

Le contrat fonctionnel maintient une authentification distincte pour BO et App. Aucun accès BO complet ne doit être émis sans validation du second facteur ; l'absence de provider doit provoquer un refus de connexion. Aucun fournisseur Google ou OIDC n'est permis au BO. La connexion Google (App uniquement) est une évolution distincte non encore livrée. Ces fonctionnalités ne doivent pas être présentées comme validées avant CI et crash-tests.

Les endpoints doivent être protégés selon leurs responsabilités. Les secrets ne sont jamais versionnés. Les outils CLI et Tooling restent sur les postes de développement.

Voir les [ADR](../adr/README.md).
