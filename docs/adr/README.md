# Architecture Decision Records (ADR)

Les ADR consignent les décisions d'architecture validées pour SymSvel Framework.

- Une ADR est ajoutée lorsqu'une décision structurante est actée.
- Elle décrit le contexte, la décision et ses conséquences.
- Une décision remplacée reste dans l'historique et pointe vers la nouvelle ADR.
- Les détails d'implémentation ordinaires ne nécessitent pas d'ADR.
- Avant de proposer un mécanisme transversal, consulter ces décisions.

Pour une vue d'ensemble, consulter [Comprendre SymSvel](../architecture/index.md).

## Registre

- [0000 : Simplicité et responsabilités claires](0000-principes-fondateurs.md)
- [0001 : Frontière framework et modules](0001-framework-et-modules.md)
- [0002 : Modules natifs minimalistes et extensibles](0002-modules-natifs.md)
- [0003 : Routage des modules via Symfony Flex](0003-routage-flex.md)
- [0004 : Sélection des mécanismes XCore vers SymSvel](0004-selection-xcore.md)
- [0005 : Un projet utilisable après le setup](0005-installation-operationnelle.md)
- [0006 : Séparer sécurité applicative et fiabilité des outils](0006-securite-et-environnements.md)
- [0007 : Faire évoluer les versions après validation](0007-versions-et-compatibilite.md)
- [0008 : Les modules possèdent leurs données et migrations](0008-donnees-et-migrations.md)
- [0009 : Installation et maintenance récupérables](0009-operations-fiables.md)
- [0010 : Une fonctionnalité livrée est testée et documentée](0010-qualite-et-documentation.md)
- [0011 : Bootstrap PHP natif avant Symfony](0011-bootstrap-php-natif.md)

Les ADR définissent les choix validés. Les vérifications techniques restantes sont signalées dans les documents concernés ; elles ne constituent pas une validation de l'implémentation.
