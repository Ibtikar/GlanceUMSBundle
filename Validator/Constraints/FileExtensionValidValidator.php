<?php

namespace Ibtikar\GlanceUMSBundle\Validator\Constraints;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;

/**
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 */
class FileExtensionValidValidator extends ConstraintValidator {

    /**
     * @param type $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint) {
        if($value && $value instanceof File) {
            $extension = $value->getExtension();
            if($value instanceof UploadedFile) {
                $extension = $value->getClientOriginalExtension();
            }
            if (in_array(strtolower($extension), $constraint->getExtensions())) {
                return;
            }
            $this->context->addViolation($constraint->message, array('%extensions%' => implode(' أو ', array_map('strtoupper', $constraint->getExtensions()))));
        }
    }

}
