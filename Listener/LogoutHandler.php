<?php

namespace Ibtikar\GlanceUMSBundle\Listener;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Description of PreLogin
 *
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 */
class LogoutHandler implements LogoutHandlerInterface {

    /** @var $container ContainerInterface */
    private $container = null;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $token
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $session = $request->getSession();
        $session->remove('redirectUrl');
        $session->remove('firstTimeRedirected');
        $response->headers->clearCookie($this->container->getParameter('logged_cookie_name'), '/', $this->container->getParameter('cookies_domain'));
        $session->remove('security.secured_area.target_path');
//        if (isset(\Hybrid_Auth::$config["providers"])) {
//            \Hybrid_Auth::logoutAllProviders();
//        }

        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_STAFF')) {
            $response->headers->set('Location', $this->container->get('router')->generate('ibtikar_glance_ums_staff_login'));
            $response = new RedirectResponse($this->container->get('router')->generate('ibtikar_glance_ums_staff_login'));
            return $response;
        }
        $locale = $session->get('_locale');
        if ($locale) {
            $response->headers->set('Location', $this->container->get('router')->generate('ibtikar_goody_frontend_homepage', array('_locale', $locale)));
            $response = new RedirectResponse($this->container->get('router')->generate('ibtikar_goody_frontend_homepage', array('_locale', $locale)));
            return $response;
        }

        // this must be the last one so it can remove the location header set by previous actions
        $referer_url = $request->headers->get('referer');
        if ($request->isXmlHttpRequest()) {
            $response->setContent(json_encode(array(
                'status' => "success",
                'target_path' => $referer_url
            )));
//            $response->headers->remove('Location');
            $response->headers->set('Content-Type', 'application/json');
        }
    }
}
