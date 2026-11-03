# ADR 0004 : Sélection des mécanismes XCore vers SymSvel

- Statut : acceptée
- Date : 2026-11-03

## Contexte

SymSvel succède à XCore, mais certaines implémentations de XCore peuvent rester pertinentes.

## Décision

Pour chaque mécanisme XCore étudié :

1. Vérifier si SymSvel propose déjà un équivalent.
2. Si absent et pertinent, l'intégrer en l'adaptant aux contrats SymSvel.
3. Si SymSvel est plus simple ou plus efficace, le conserver.
4. Si XCore est meilleur, adapter ou remplacer l'implémentation SymSvel.
5. Si les deux sont complémentaires, retenir une combinaison sobre.
6. Écarter les mécanismes inutiles ou sources de complexité disproportionnée.

Comparer le code réel et les effets sur la modularité, l'installation, la mise à jour, la suppression, la maintenabilité et les tests. Les ADR précédentes restent des contraintes de conception.

## Conséquences

- Aucun portage automatique de XCore.
- Aucune conservation automatique de SymSvel au seul motif qu'il est plus récent.
- Priorité à la modularité maximale et à la complexité minimale.
