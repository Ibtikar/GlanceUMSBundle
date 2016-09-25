<?php

namespace Ibtikar\GlanceUMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @author Ahmad Gamal <a.gamal@ibtikar.net.sa>
 */

/**
 * @Annotation
 *
 */
class JobLimitExceeded extends Constraint {

    public $message = 'Can not assign more members to this job.';

    public function validatedBy() {
        return "jobLimitExceeded";
    }

    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }


}
