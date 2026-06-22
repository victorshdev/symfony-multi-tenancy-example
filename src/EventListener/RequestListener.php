<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class RequestListener
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
    ) {
    }

    #[AsEventListener]
    public function onKernelRequest(RequestEvent $event): void
    {
        $this->init($event->getRequest());
    }

    private function init(Request $request): void
    {
        if ($this->isPrivateArea($request)) {
            $this->initializeContext();
        }
    }

    /**
     * Note: it's better to move this into a Resolver class,
     * but for simplicity I keep it here.
     *
     * @return void
     */
    private function initializeContext(): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {   // use App\Entity\User;
            return;
        }

        $filter = $this->em->getFilters()->enable('context_filter');
        $filter->setParameter('org_id', $user->getOrganization()->getId());
    }

    private function isPrivateArea(Request $request): bool
    {
        $route = $request->attributes->get('_route');

        if (is_null($route)) {
            return false;
        }

        return str_starts_with((string) $route, 'private_area_'); // Just an example. Use your own.
    }
}
