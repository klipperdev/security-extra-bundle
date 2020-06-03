<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\SecurityExtraBundle\Listener;

use Klipper\Component\HttpFoundation\Util\RequestUtil;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class SecurityFakeHostSubscriber implements EventSubscriberInterface
{
    private FirewallMap $firewallMap;

    public function __construct(FirewallMap $firewallMap)
    {
        $this->firewallMap = $firewallMap;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => [
                ['fakeHostOfTargetPath', 1000],
            ],
        ];
    }

    /**
     * Fake the host of the security target path in session.
     *
     * @param ControllerArgumentsEvent $event The event
     */
    public function fakeHostOfTargetPath(ControllerArgumentsEvent $event): void
    {
        $request = $event->getRequest();
        $targetPath = $this->getRedirectPath($request);

        if (null !== $targetPath
                && $request->hasSession()
                && null !== $request->getSession()
                && $request->getSession()->has($targetPath)) {
            $request->getSession()->set($targetPath, RequestUtil::restoreFakeHost($request->getSession()->get($targetPath)));
        }
    }

    /**
     * Get the security target path.
     *
     * @param Request $request The request
     */
    protected function getRedirectPath(Request $request): ?string
    {
        $targetPath = null;

        if ($request->hasSession()
                && null !== $request->getSession()
                && null !== ($firewall = $this->firewallMap->getFirewallConfig($request))
                && $request->getSession()->isStarted()) {
            $targetPath = sprintf('_security.%s.target_path', $firewall->getName());
        }

        return $targetPath;
    }
}
