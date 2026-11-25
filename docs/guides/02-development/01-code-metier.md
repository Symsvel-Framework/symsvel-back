# Développer avec Symfony et Svelte

SymSvel n'impose pas de module pour votre code métier. Vous pouvez développer directement dans votre projet avec Symfony et SvelteKit.

## Exemple : une première API catalogue

Créez backend/src/Controller/CatalogueController.php :

~~~php
<?php

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use SymSvel\Core\Http\ApiResponse;

final class CatalogueController
{
    #[Route('/apiopen/catalogue-demo', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            ['id' => 1, 'name' => 'Carnet'],
            ['id' => 2, 'name' => 'Stylo'],
        ], 200);
    }
}
~~~

Depuis backend, vérifiez la route :

~~~bash
php bin/console debug:router
curl http://api.catalogue.test/apiopen/catalogue-demo
~~~

Cet exemple retourne des données publiques fictives. Pour des données privées, utilisez un scope protégé et contrôlez les autorisations côté serveur.

## Afficher une page Svelte

Dans une interface installée, ajoutez src/routes/catalogue-demo/+page.svelte :

~~~svelte
<script lang="ts">
  let products = [
    { id: 1, name: 'Carnet' },
    { id: 2, name: 'Stylo' }
  ];
</script>

<h1>Catalogue</h1>
<ul>
  {#each products as product (product.id)}
    <li>{product.name}</li>
  {/each}
</ul>
~~~

Lancez npm run dev dans cette interface, puis ouvrez /catalogue-demo. Pour connecter la page à l'API, utilisez le client API de l'interface ou fetch avec l'origine backend du projet. Assurez-vous que l'origine du serveur de développement est autorisée par la configuration CORS.

## Aller plus loin

- Entités, repositories et services : [documentation Symfony](https://symfony.com/doc/current/index.html).
- Pages, composants et chargement des données : [documentation SvelteKit](https://svelte.dev/docs/kit/introduction).
- Pour vos propres tables applicatives : utilisez les migrations Doctrine sans modifier les tables gérées par les modules natifs.
- Pour une fonctionnalité réutilisable dans plusieurs projets, choisissez le [développement modulaire](02-module-metier.md).
