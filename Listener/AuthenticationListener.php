<?php

namespace Ibtikar\GlanceUMSBundle\Listener;

use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * @author Ola <ola.ali@ibtikar.net.sa>
 */
class AuthenticationListener {

    private $session;

    public function __construct($session) {

        $this->session = $session;
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event) {
        $loginTrials = $this->session->get('loginTrials', 1);
        $loginTrials++;
        $this->session->set('loginTrials', $loginTrials);

    }


    public function onAuthenticationSuccess(InteractiveLoginEvent $event) {
        // executes on successful login
    }

}
