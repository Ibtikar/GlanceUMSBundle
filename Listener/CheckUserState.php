<?php

namespace Ibtikar\GlanceUMSBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @author Gehad Mohamed <gehad.mohamed@ibtikar.net.sa>
 */
class CheckUserState {

    private $router;
    private $container;

    public function __construct($router, $container) {
        $this->router = $router;
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event) {
        $container = $this->container;

        $token = $container->get('security.token_storage')->getToken();

        $route = $container->get('request_stack')->getCurrentRequest()->get('_route');
        $staffChangePasswordRoute = 'ibtikar_glance_ums_staff_changePassword';
        $visitorChangePasswordRoute = 'change_password';
        $allowedRoutesNames = array($visitorChangePasswordRoute, $staffChangePasswordRoute, 'post_login');
        $requestURI = $event->getRequest()->getRequestUri();
        if ($token && $token->getUser() != "anon." && !in_array($route, $allowedRoutesNames) && strpos($requestURI, '/assets/') === false) {
            $user = $token->getUser();
            if ($user->getMustChangePassword()) {
                if (is_a($user, 'Ibtikar\GlanceUMSBundle\Document\Staff')) {
                    $event->setResponse(new RedirectResponse($this->router->generate($staffChangePasswordRoute)));
                }
//                else {
//                    $event->setResponse(new RedirectResponse($this->router->generate($visitorChangePasswordRoute)));
//                }
            }
        }
    }

}
