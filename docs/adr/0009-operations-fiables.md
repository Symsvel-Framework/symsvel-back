# ADR 0009 : Installation et maintenance récupérables

**Statut : acceptée**  
**Date : 2026-11-06**

## Contexte

Une opération interrompue ne doit pas laisser silencieusement un projet incohérent.

## Décision

- Les installations, mises à jour et suppressions contrôlent leurs préconditions.
- Préserver les fichiers et données préexistants.
- En cas d’échec, restaurer ce qui est techniquement restaurable et signaler explicitement les limites.
- Ne jamais annoncer un rollback complet sans preuve.
- Les mises à jour de composants doivent préserver les modifications locales et détecter les conflits.

## Justification

La sécurité des outils est principalement la fiabilité de leurs processus.

## Conséquences

Tests d’échec, diagnostics et procédures de récupération sont obligatoires.

## Vérification technique restante

Auditer les transactions existantes et la stratégie de baseline des composants avant de figer les garanties exactes.
