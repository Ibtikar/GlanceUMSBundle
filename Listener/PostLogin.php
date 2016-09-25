<?php

namespace Ibtikar\GlanceUMSBundle\Listener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Ibtikar\VisitorBundle\Document\Visitor;

/**
 * Description of PostLogin
 *
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 */
class PostLogin {

    /* @var $dm DocumentManager */
    private $dm;

    /**
     * @param ManagerRegistry $mr
     */
    public function __construct(ManagerRegistry $mr) {
        $this->dm = $mr->getManager();
    }

    /**
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event) {
        $user = $event->getAuthenticationToken()->getUser();
        $request = $event->getRequest();
        $session = $request->getSession();
        if ($session) {
            $session->set('userId', $user->getId());
            $session->remove('identity');
            $session->remove('loginTrials');
            $session->remove('secret');
            $session->remove('firstTimeRedirected');
        }
        $user->setLastLoginTime(new \DateTime())->setLastLoginIp($request->getClientIp())->setLastLoginFrom($request->attributes->get('requestFrom', Visitor::$REGISTERATION_LOCATIONS['site']));
        $user->setNoOfVisits($user->getNoOfVisits() + 1);

        $this->dm->flush($user);
    }

}
