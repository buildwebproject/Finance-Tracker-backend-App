<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Country;
use App\Entity\FinanceCategory;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ApiDataCacheInvalidationSubscriber implements EventSubscriber
{
    public function __construct(
        #[Autowire(service: 'cache.api_responses')]
        private readonly TagAwareAdapterInterface $apiResponseCache,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->invalidateForEntity($args->getObject());
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->invalidateForEntity($args->getObject());
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->invalidateForEntity($args->getObject());
    }

    private function invalidateForEntity(object $entity): void
    {
        if ($entity instanceof Country) {
            $this->apiResponseCache->invalidateTags(['api.countries']);

            return;
        }

        if ($entity instanceof FinanceCategory) {
            $this->apiResponseCache->invalidateTags(['api.categories']);
        }
    }
}
