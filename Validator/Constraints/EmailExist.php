<?php

namespace Ibtikar\GlanceUMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 */
class EmailExist extends Constraint {

    public $message = 'Please enter your valid and true email address';

}