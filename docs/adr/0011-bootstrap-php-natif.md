# ADR 0011 : Bootstrap PHP natif avant Symfony

**Statut : approuvée pour le CT Choralys (22/11/2026)**

## Problème observé

Sur le VPS, le premier clone du Back exposait `/prod_settings` en démarrant
le Kernel Symfony avec le `.env` d'installation (`APP_SECRET` vide,
`APP_ENV=dev`). Le journal CLI ne suffisait pas à expliquer la 500 HTTP ;
l'accès au profiler en phase non configurée démontrait toutefois une
mauvaise séparation entre bootstrap et runtime.

## Décision

- Un seul wizard PHP natif pour `/setup` (loopback, lancé par CLI) et
  `/prod_settings` (premier déploiement).
- Le front controller intercepte exactement les quatre chemins Setup
  **avant Composer runtime et Symfony Kernel**. Les classes existantes
  de validation, les commandes Tooling/Modules et le dispatcher CD restent.
- Toutes les requêtes hors Setup sont bloquées en HTTP 503 tant que
  `.env.local` n'existe pas ou que `.symsvel/setup.json` n'est pas
  `completed`.
- Le Setup génère `APP_SECRET` (32 octets aléatoires), `APP_ENV`,
  `DATABASE_URL`, et les origines exactes **avant** d'appeler Symfony
  en sous-processus CLI.
- Le Setup prod réutilise les migrations, le provisionnement natif Admins
  et Languages, ou l'import SQL, puis déclenche `deploy.yml` des
  dépôts Git du projet depuis le PAT ponctuel.
- Après succès prod, supprimer du déploiement le seul bootstrap natif
  `src/Setup/StandaloneSetup.php`. Le marqueur `completed` demeure
  et ferme tout nouvel accès même après un `git pull`.
- La machine de développement et GitHub ne sont jamais modifiés par
  cette purge exécutée sur le VPS. Le Setup local reste présent.
- Le code de l'ancien contrôleur Symfony et des Twig d'installation
  n'est plus nécessaire au routage du wizard et est retiré du Back.

## Limites et validation

Le HTTP 204 de GitHub Actions ne confirme pas la réussite des transferts SSH.
Le VPS doit autoriser l'utilisateur PHP à écrire dans `.symsvel`,
`backend/.env.local` et `secrets/.env` et à lancer les commandes
Symfony CLI. Le code ne prend pas en charge la gestion automatique de ces
permissions système.

Chantier **non finalisé** tant que CI, tests automatisés et CT utilisateur
(local plus VPS) ne sont pas tous validés. Une installation déjà achevée
conserve le marqueur `completed` comme unique verrou d'ouverture.

## Précision après le premier cycle CI

La CI valide que les routes Setup ne sont plus enregistrées dans le Kernel
Symfony et que le `.env` versionné ne configure aucune origine CORS
autorisé. L'allowlist des tests est fournie explicitement dans
`phpunit.dist.xml` ; le runtime attend les valeurs du wizard.

Un dépôt BO est obligatoire en production. Si les dépôts d'interfaces sont
renseignés, le PAT Actions:write doit permettre à GitHub d'accepter
tous les `workflow_dispatch`, sinon le Setup reste reprenable à la phase 2.
Le `completed` est écrit après acceptation et non après simple tentative.
