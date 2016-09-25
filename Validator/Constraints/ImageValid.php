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
class ImageValid extends Constraint
{
    public $message = 'This image is corrupted, please try another image';
}
