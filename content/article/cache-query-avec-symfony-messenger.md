---
date: "2023-02-01 12:00:00"
tags: []
title: "Des Query mises en cache avec Symfony"
description: "Comment mettre en place un système de cache des Query avec les composants Symfony Messenger et Cache"
language: fr
cover: /img/articles/cached-queries/cached_dark.png
---

J'ai eu récemment sur [l'un de mes sites](https://whatthetune.com/profil/1) des routes aux temps de réponse insatisfaisants.

Les pages impliquées necéssitent des calculs statistiques assez coûteux sur de gros volumes de données et je souhaitais trouver un moyen d'améliorer ces temps de réponse tout en soulagant mon serveur de cette charge.

Mon code est organisé selon un pattern _CQRS_, j'ai donc des _Query_ et des _Handler_ chargés de les traiter, organisés autour d'un bus via composant **Mesenger** de Symfony.

Après quelques optimisation, j'ai finalement opté pour un petit **système de cache** qui me permetrait de conserver le résultat de mes queries coûteuses pendant un certain temps sans avoir à les recalculer systématiquent.

Dans cet article, nous detaillerons comment mettre en place ce système de cache dans un projet Symfony en s'appuyant sur les composant [symfony/messenger](https://symfony.com/doc/current/messenger.html) et [symfony/cache](https://symfony.com/doc/current/cache.html).

## Le cas pratique

Nous prendrons l'exemple d'un cas pratique similaire à mon cas d'usage réel.

### La query et son handler

Mettons que nous ayons une query representant la récupération de tout un tas de statistiques concernant un utilisateur donné :

```php
namespace App\Query;

/**
 * Get all the metrics!
 */
class GetUserMetricsQuery
{
    public function __construct(
        public int $userId
    ) { }
}
```

Voici son handler, faisant appel à un calculateur, _très compliqué et mystérieux_, mais capable de retourner un tableau de statistiques à propos de notre utilisateur :

```php
namespace App\QueryHandler;

use App\Query\GetUserMetricsQuery;
use App\Metric\MyMetricCalculator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetUserMetricsQueryHandler
{
    public function __construct(
        private MyMetricCalculator $calculator
    ) { }

    public function __invoke(GetUserMetricsQuery $query): array
    {
        return $this->calculator->getMetricsForUser(
            $query->userId
        );
    }
}
```

### Utilisation dans notre application

Nous utilisons cette query dans un controller par exemple :

```php
use App\Query\GetUserMetricsQuery;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserMetricsController
{
    public function __construct(
        private MessageBusInterface $bus
    ) { }

    #[Route('/metrics/user/{id}', methods: ['GET'])]
    public function __invoke(int $id): JsonResponse
    {
        $envelope = $this->bus->dispatch(
            new GetUserMetricsQuery($id)
        );

        return new JsonResponse(
            $envelope->last(HandledStamp::class)->getResult()
        );
    }
}
```

## Le problème

Maintenant, metons que l'execution de cette query est *très couteuse* : en CPU, en mémoire, parce qu'elle fait appel à un service tier, ou bien éxecute des requêtes SQL lourdes, ...

Comme nous l'avons vu en intro, nous voulons eviter d'executer cette query systématiquement alors que son resultat peut être considéré valide pendant un certain temps.

## Le système de cache comme solution

Pour cela, nous allons ajouter un système de cache à notre bus, afin qu'il soit capable de mettre en cache le restulat de nos Query.

### Création d'un middleware de cache

Nous allons implementer cette fonctionnalité en créant notre propre _Middleware_ qui s'interfacera avec le composant [symfony/messenger](https://symfony.com/doc/current/messenger.html#middleware).

Le traitement d'un message par notre middleware de cache va se dérouler ainsi :
1. Vérifier que nous traitons une query qui peut etre mise en cache.
2. Récupérer le cache correspondant à notre query.
3. _Si le cache est vide :_ traiter notre query en reprenant l'execution normale et stocker le resultat en cache.
4. Retourner le resultat depuis le cache.

Voici ce que ça donne :

```php
namespace App\Middleware;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class CacheMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AdapterInterface $cache,
    ) { }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        // 1. Only support queries that implements CachableQueryResult interface
        if (!$message instanceof CachableQueryResult) {
            // Handle other queries normaly
            return $this->continue($envelope, $stack);
        }

        // 2. Get the cache item for the query
        $item = $this->cache->getItem(
            $message->getCacheKey()
        );

        if (!$item->isHit()) {
            // 3. Result is not in cache, handle the query and cache the result:
            $item->set($this->continue($envelope, $stack));
            $this->cache->save($item);
        }

        // 4. Return result from cache
        return $item->get();
    }

    private function continue(Envelope $envelope, StackInterface $stack): Envelope
    {
        // Pass the enveloppe on to the next handler to resume handling:
        return $stack->next()->handle($envelope, $stack);
    }
}
```

Afin de réaliser le test de l'étape 1, notre middleware vient avec une interface `CachableQueryResult` :

```php
namespace App\Middleware;

interface CachableQueryResult
{
    public function getCacheKey(): string;
}
```

Cette interface nous permet deux choses :
- Activer le système de cache uniquement pour les query qui le supportent.
- Segmenter le cache en fonction des propriétés de la Query via la méthode `getCacheKey`.

### Configuration du middleware

Notre middleware de cache se repose sur un **Adapter** fourni par le composant [symfony/cache](https://symfony.com/doc/current/components/cache.html) qui va gérer le cache pour nous.

Nous allons déclarer le service `AdapterInterface` à injecter dans notre middleware, parmis les différentes implementations fournies par le composant _symfony/cache_ :

```yaml
# config/services.yaml
services:
    Symfony\Component\Cache\Adapter\AdapterInterface:
        #class: Symfony\Component\Cache\Adapter\ApcuAdapter
        #class: Symfony\Component\Cache\Adapter\DoctrineDbalAdapter
        class: Symfony\Component\Cache\Adapter\FilesystemAdapter
        arguments:
            $defaultLifetime: 3600 # 1h
```

Notre middleware de cache est prêt ! Nous pouvons l'ajouter à notre bus :

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        # ...
        buses:
            messenger.bus.default:
                middleware:
                    - 'App\Middleware\CacheMiddleware'
```

### Notre query cachable

Nous pouvons maintenant rendre notre query "cachable" en implementant l'interface `CachableQueryResult` :

```php
namespace App\Query;

use App\Middleware\CachableQueryResult;

class GetUserMetricsQuery implements CachableQueryResult
{
    // ...

    public function getCacheKey(): string
    {
        // Different cache key for each user
        return sprintf('user_metrics_%s', $this->userId);
    }
}
```

_Note :_ Nous ne voulons pas que les statistiques de l'utilisateur A soient mises en cache et renvoyé à l'utilisateur B ! C'est pourquoi la clée de cache d'une query `GetUserMetricsQuery` dépends de sa propriété `userId`.

### Le résultat !

Lors d'une premier traitement de notre Query, c'est le Handler calcule le résultat, comme avant :

![](/img/articles/cached-queries/standard_dark.png)

À partir du second traitement (et pour toute la durée du cache), c'est le middleware de cache qui réponds :

![](/img/articles/cached-queries/cached_dark.png)

Contrat rempli, nous avons des temps de réponse bien plus satisfaisants avec notre système de cache !

## Bonus et améliorations

Pour aller plus loin, voici quelques idées pour améliorer l'utilisabilité de notre nouveau système de cache :

### Forcer le rafraichissement du cache

Dans certain cas, nous allons vouloir eviter de passer par le cache.

Pour cela, nous allons utiliser le système de `Stamp` de _symfony/messenger_ qui permet d'associé un contexte au traitement d'une Query.

Nous créons notre propre Stamp qui representera l'information _"je veux forcer le rafraichissement du cache pour ce traitement"_ :

```php
namespace App\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Force cache refresh upon handling
 */
class RefreshCacheStamp implements StampInterface
{
}
```

Dans notre middleware, nous allons nous baser sur la présence de ce stap pour déclencher une rafraichissement forcé du cache :

```php
namespace App\Middleware;

// ...
use App\Stamp\RefreshCacheStamp;

class CacheMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // ...
        $force = $envelope->last(RefreshCacheStamp::class) !== null;

        if ($force || !$item->isHit()) {
            $item->set($this->continue($envelope, $stack));
            $this->cache->save($item);
        }
        // ...
    }
}
```

Nous pouvons maintenant, quand c'est nécessaire, forcer le rafraichissement du cache en annotant notre query d'un `RefreshCacheStamp`.

Dans une commande par exemple :

```php
namespace App\Command;

use App\Query\GetUserMetricsQuery;
use App\Stamp\RefreshCacheStamp;
// ...

#[AsCommand(name: 'app:user-metric')]
class UserMetricCommand extends Command
{
    // ...

    protected function configure(): void
    {
        $this
            ->addArgument('userId', InputArgument::REQUIRED, 'User id')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force cache resfresh')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userId = $input->getArgument('userId');
        $stamps = [];

        if ($input->getOption('force')) {
            // Add the RefreshCacheStamp when option '--force' is passed
            $stamps[] = new RefreshCacheStamp();
        }

        $envelope = $this->bus->dispatch(
            new GetUserMetricsQuery($userId),
            $stamps
        );

        $output->writeln(
            $envelope->last(HandledStamp::class)->getResult()
        );

        return Command::SUCCESS;
    }
}

```

💡 Mais là où ce sera le plus pertinent, c'est pour pré-calculer le cache lorsqu'on sait qu'il n'est plus à jour.

Par exemple, lorsqu'on modifie des données de notre utilisateur qui impacteront ses statistiques, on peut déclecher un rafraichissement du cache :

```php
<?php

namespace App\Controller;

use App\Query\GetUserMetricsQuery;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class EditUserController
{
    // ...

    #[Route('/admin/user/{id}/edit', methods: ['POST'])]
    public function __invoke(User $user): Response
    {
        // Update the user...

        // Run the Query in force mode so cache will be warm and up-to-date!
        $envelope = $this->bus->dispatch(
            new GetUserMetricsQuery($id)
            new RefreshCacheStamp()
        );

        return new Response();
    }
}
```

_Note :_ Puisque le re-calcul du cache est couteux, nous voudrons probablement différer ce traitement _après_ que la réponse ai été renvoyée à l'utilisateur. En traitant la query lors de l'evenement `kernel.terminate` ou bien via la mise en place d'un worker. C'est d'ailleurs ce que j'ai fais dans mon cas d'utilisation réel :)

Ainsi :
- Nous pouvons mettre une durée de cache assez haute (ex: 24h).
- La modification des données d'un utilisateur déclenchera le rafraichissement du cache associé.
- Les routes publiques bénéficieront toujours d'un cache frais et à jour, répondant rapidement _et_ correctement.

#### Invalidation du cache

Nous pouvons aussi vouloir invalider le cache, sans pour autant lancer un recalcul.
Pour ce cas là, nous utiliserons simplement la méthode `clear` de notre adapter:

```php
// Invalidate cache a specific query:
$cache->clear((new GetUserMetricsQuery(42))->getCacheKey());
// Invalidate cache for all GetUserMetricsQuery:
$cache->clear('user_metrics_');
// Invalidate cache for all queries:
$cache->clear();
```

### Configurer la durée de mise cache en fonction de la query

Actuellement, la durée de mise en cache est définie globalement à 1 heure via la durée par défault fournie à notre Adapter.

Une autre amélioration serait de pouvoir configurer la durée de mise en cache individuellement pour chaque type de query.

Pour cela, nous allons étoffer notre interface `CachableQueryResult` d'une méthode `getLifeTime` :

```php
namespace App\Query;

interface CachableQueryResult
{
    // ...
    public function getLifeTime(): ?int;
}
```

Ensuite nous mettons à jour notre query pour spécifier une durée de mise en cache :

```php
namespace App\Query;

class GetUserMetricsQuery implements CachableQueryResult
{
    // ...

    public function getLifeTime(): int
    {
        return 30; // in seconds
    }
}
```

Maintenant, notre middleware de cache va utiliser cette nouvelle méthode pour définir un temps de mise en cache spécifique au niveau de l'item :

```php
namespace App\Middleware;

// ...

class CacheMiddleware implements MiddlewareInterface
{
    // ...

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // ...
        if (!$item->isHit()) {
            if ($lifetime = $message->getLifeTime()) {
                // Set expiration if a custom lifetime is provided
                $item->expiresAfter($lifetime);
            }

            $item->set($this->continue($envelope, $stack));
            $this->cache->save($item);
        }
        // ..
    }
}
```

## Conclusion

Nous voici avec un bon petit système de cache assez souple pour nos Query CQRS utilisant le composant Messenger, à l'aide d'un simple Middleware.

Une démo complète et fonctionelle est disponible sur Github :

[Tom32i/demo-cached-queries](https://github.com/Tom32i/demo-cached-queries).

Des remarques, des questions ? [N'hesitez pas !](https://twitter.com/tom32i)
