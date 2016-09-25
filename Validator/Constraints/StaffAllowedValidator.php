<?php

namespace Ibtikar\GlanceUMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

class StaffAllowedValidator extends ConstraintValidator {

    private $dm;

    public function __construct(ManagerRegistry $mr) {
        $this->dm = $mr->getManager();
    }

    public function validate($protocol, Constraint $constraint) {
        if ($protocol->getVisibility() == 'private') {
            $authors = $protocol->getStaff();
            foreach ($authors as $author) {
                if (!in_array('ROLE_CONTACTGROUP_VIEW', $author->getRoles())) {
                    $this->context->addViolationAt('staff', $constraint->message);
                    break;
                }
            }
        }
    }

}
