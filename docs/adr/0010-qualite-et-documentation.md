# ADR 0010 : Une fonctionnalité livrée est testée et documentée

**Statut : acceptée**  
**Date : 2026-11-06**

## Contexte

Le code seul ne permet ni la maintenance ni la reprise par un autre développeur.

## Décision

- Documenter en même temps que l’implémentation.
- Ne pas clôturer sans tests automatisés, CI verte, crash-tests locaux validés et documentation à jour.
- Conserver les décisions validées ; toute remise en cause exige une raison technique explicite.
- Les ADR expliquent les choix, les guides techniques décrivent les mécanismes et procédures.
- Chantiers simples : push direct ; chantiers longs : PR draft, une seule PR ouverte par dépôt.
- Ne pas merger avec une dette technique non résolue.

## Justification

La documentation fait partie du produit ; les conventions réduisent les régressions.

## Conséquences

Le chantier n’est pas terminé tant que l’ensemble de ces critères n’est pas satisfait.
