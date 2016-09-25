<?php

namespace Ibtikar\GlanceUMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @author gehad mohamed <gehad.mohamed@ibtikar.net.sa>
 */

/**
 * @Annotation
 *
 */
class StaffAllowed extends Constraint {

    public $message = 'the users selected dont have permission';

    public function validatedBy() {
        return "staffAllowed";
    }

    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }

}
