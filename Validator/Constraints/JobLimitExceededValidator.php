<?php

namespace Ibtikar\GlanceUMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Ibtikar\BackendBundle\Document\Job;
use Ibtikar\BackendBundle\Document\Staff;

/**
 * @author Ahmad Gamal <a.gamal@ibtikar.net.sa>
 */
class JobLimitExceededValidator extends ConstraintValidator {

    private $dm;

    public function __construct(ManagerRegistry $mr) {
        $this->dm = $mr->getManager();
    }

    public function validate($staff, Constraint $constraint) {
        if($staff && $staff->getJob()) {
        $max = $staff->getJob()->getStaffMembersMaxCount();
        if ($max && $staff->getJob()->getStaffMembersCount() >= $max) {
            if ($staff->getId()) {
                $staffExist = $this->dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                                ->field('job')->equals($staff->getJob()->getId())
                                ->field('id')->equals($staff->getId())
                                ->field('deleted')->equals(FALSE)->count()
                                ->getQuery()->execute();
                if (!$staffExist) {
                    $this->context->addViolationAt('job', 'Can not assign more members to this job.');
                }
            } else {
                $this->context->addViolationAt('job', 'Can not assign more members to this job.');
            }
        }
        }
    }

}
