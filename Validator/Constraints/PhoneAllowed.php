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
class PhoneAllowed extends Constraint {

    public $message = 'not valid';

    public function validatedBy() {
        return "phoneAllowed";
    }

    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }

}
