<?php

namespace Ibtikar\GlanceUMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

/**
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 */
class EmailExistValidator extends ConstraintValidator {

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint) {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, 'getPath'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = (string) $value;

        $validator = new EmailValidator();
        $valid = $validator->isValid($value, new RFCValidation());

        if ($valid !== true) {


            $this->context->addViolation($constraint->message, array(
                '{{ value }}' => $this->formatValue($value),
            ));
        }
    }

}