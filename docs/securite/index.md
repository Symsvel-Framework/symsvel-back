# Sécurité

Les modules natifs Admins, Roles et ACLs constituent le socle de gestion des identités et autorisations d'administration. Users est requis lorsque l'App est installée.

Le contrat fonctionnel prévoit une authentification distincte pour BO et App, ainsi qu'une authentification à deux facteurs. Ces fonctionnalités ne doivent pas être présentées comme livrées avant leur implémentation et leur validation.

Les endpoints doivent être protégés selon leurs responsabilités. Les secrets ne sont jamais versionnés. Les outils CLI et Tooling restent sur les postes de développement.

Voir les [ADR](../adr/README.md).
