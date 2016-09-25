<?php

namespace Ibtikar\GlanceUMSBundle\Listener;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * this class is for listenning on each request the user make
 * if the user last seen time is old this class will update the user last seen time
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 * @author Moemen Hussein <momen.shaaban@ibtikar.net.sa>
 */
class UpdateUserLastSeenListener {

    private $securityContext;
    private $dm;

    public function __construct(SecurityContextInterface $securityContext, $dm) {
        $this->securityContext = $securityContext;
        $this->dm = $dm->getManager();
    }

    public function onRequest(GetResponseEvent $event) {
        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
            //get the token from the firewall
            $token = $this->securityContext->getToken();
            //check if we have a logged in user
            if ($token) {
                //get the user objcet
                $user = $token->getUser();
                if (is_object($user) && $user instanceof \Ibtikar\GlanceUMSBundle\Document\User) {
                    //not valid time update the user last seen time
                    $user->setLastSeen(new \DateTime());
                    //save in the database
                    $this->dm->flush($user);
                }
            }
        }
    }

}
