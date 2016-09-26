<?php

namespace Ibtikar\GlanceUMSBundle\Listener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Ibtikar\GlanceUMSBundle\Document\Visitor;

class PostLogin
{
    /* @var $dm DocumentManager */

    private $dm;

    /**
     * @param ManagerRegistry $mr
     */
    public function __construct(ManagerRegistry $mr)
    {
        $this->dm = $mr->getManager();
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
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

        $user->setLastLoginTime(new \DateTime())->setLastLoginIp($request->getClientIp());
//        $user->setNoOfVisits($user->getNoOfVisits() + 1);

        $this->dm->flush($user);
    }
}
