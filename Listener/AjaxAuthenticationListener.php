<?php

namespace Ibtikar\GlanceUMSBundle\Listener;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


class AjaxAuthenticationListener {

    private $router;
    private $session;
    private $translator;

    public function __construct($router,$session,$translator) {
        $this->router = $router;
        $this->session = $session;
        $this->translator = $translator;
    }

    /**
     * Handles security related exceptions.
     *
     * @param GetResponseForExceptionEvent $event An GetResponseForExceptionEvent instance
     */
    public function onKernelException(GetResponseForExceptionEvent $event) {
        $exception = $event->getException();
        $request = $event->getRequest();
        if(strpos($request->getRequestUri(), '/api') !== false) {
            return;
        }
//        $request->getSession()->remove('firstTimeRedirected');
        if ($request->isXmlHttpRequest()) {
            if ($exception instanceof AuthenticationException || $exception instanceof AccessDeniedException) {
                $this->session->getFlashBag()->add('error', $this->translator->trans('You are not authorized to do this action any more'));
                $event->setResponse(new JsonResponse(array('status' => 'reload-page'), 403));
                return;
            }
        }
        if ($request->get('iframe') === 'true') {
            if ($request->get('redirectUrl')) {
                $request->getSession()->set('redirectUrl', $request->get('redirectUrl'));
                $event->setResponse(new Response('<!DOCTYPE html>
                <html lang="en">
                    <head>
                        <meta charset="utf-8" />
                        <script type="text/javascript">
                            function reloadParent() { window.parent.location = "' . $this->router->generate($request->get('_route'), $request->get('_route_params')) . '";}
                        </script>
                    </head>
                    <body onload="reloadParent()">
                    </body>
                </html>', 403));
            } else {
                $event->setResponse(new Response('<!DOCTYPE html>
                <html lang="en">
                    <head>
                        <meta charset="utf-8" />
                        <script type="text/javascript">
                            function reload() { window.location = "' . $this->router->generate($request->get('_route'), array_merge($request->get('_route_params'), array('iframe' => 'true'))) . '&redirectUrl=" + encodeURIComponent(window.parent.location.href);}
                        </script>
                    </head>
                    <body onload="reload()">
                    </body>
                </html>', 403));
            }
        }
    }
}