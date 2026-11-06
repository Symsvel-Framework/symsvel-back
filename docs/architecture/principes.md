# Principes

- **Simple, sans bricolage.** Choisir les mécanismes natifs et compréhensibles ; ne pas construire pour un besoin hypothétique.
- **Responsabilités explicites.** L'outillage prépare et maintient ; le runtime exécute ; les modules fournissent les fonctionnalités.
- **Opérations fiables.** Une installation ou une mise à jour doit détecter les échecs et préserver un état récupérable.
- **Données préservées.** Le code peut évoluer sans détruire les données métier, fichiers ou médias.
- **Sécurité proportionnée.** Le code déployé protège les usages applicatifs ; l'outillage local sécurise surtout ses opérations.
- **Stabilité avant nouveauté.** Une évolution de dépendance se teste avant adoption.
- **Qualité vérifiable.** Tests, CI, crash-tests et documentation font partie de la livraison.

Une décision validée fait référence jusqu'à révision explicite. Les ADR décrivent **pourquoi** ; les guides techniques expliquent **comment**.
