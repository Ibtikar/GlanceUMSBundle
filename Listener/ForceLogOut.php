<?php

namespace Ibtikar\GlanceUMSBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @author Gehad Mohamed <gehad.mohamed@ibtikar.net.sa>
 */
class ForceLogOut {

    private $router;
    private $container;

    public function __construct($router, $container) {
        $this->router = $router;
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $container = $this->container;
        $dm= $container->get('doctrine_mongodb')->getManager();
        $token = $container->get('security.token_storage')->getToken();
        if ($token && $token->getUser() != "anon.") {
            $user = $token->getUser();
            if ($user->getForceLogout()) {
                $user->setForceLogout(FALSE);
                $dm->flush();
                $container->get('security.token_storage')->setToken(null);
                $container->get('session')->invalidate();
                $event->setResponse(new JsonResponse(array('status' => 'reload-page'), 403));
            }
        }
    }
}
