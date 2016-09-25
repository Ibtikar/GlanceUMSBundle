<?php

namespace Ibtikar\GlanceUMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

class PhoneAllowedValidator extends ConstraintValidator {

    private $dm;

    public function __construct(ManagerRegistry $mr) {
        $this->dm = $mr->getManager();
    }

    public function validate($protocol, Constraint $constraint) {
        if ($protocol->getId() && $protocol->getMobile()->getPhone()) {
            $contactCount = $this->dm->createQueryBuilder('IbtikarGlanceUMSBundle:Contact')
                            ->field('mobile.phone')->equals($protocol->getMobile()->getPhone())
                            ->field('id')->notEqual($protocol->getId())
                            ->field('deleted')->equals(false)
                            ->getQuery()->count();
            if ($contactCount > 0) {
                $this->context->addViolationAt('mobile', $constraint->message);
            }
        }
    }

}
