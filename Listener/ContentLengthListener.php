<?php

namespace Ibtikar\GlanceUMSBundle\Listener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;


class ContentLengthListener {

    private $container;

    public function __construct($container) {
        $this->container = $container;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event) {
        $response = $event->getResponse();
        $headers = $response->headers;
        if (!$response->isRedirection() && !$headers->has('Content-Length') && !$headers->has('Transfer-Encoding')) {
            $headers->add(array('Content-Length' => strlen($response->getContent())));
        }
    }

}
