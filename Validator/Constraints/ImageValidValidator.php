<?php

namespace Ibtikar\GlanceUMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Debug\Exception\ContextErrorException;

class ImageValidValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($value) {
        $image_info = @getimagesize($value);
        if($image_info["mime"] == "image/jpeg" ){
            $jpgdata = fopen($value->getPathname(), 'r'); // 'r' is for reading
                fseek($jpgdata, -2, SEEK_END); // move to EOF -2
                $eofdata = fread($jpgdata, 2);
                fclose($jpgdata);

            if($eofdata!="\xFF\xD9"){
                $this->context->addViolation(
                    $constraint->message);
                return;
            }
        }
            $extension = $value->guessExtension();
            // ignore bmp extensions because currently there is no function called imagecreatefrombmp
            if ($extension && $extension !== 'bmp') {
                if ($extension == 'jpg') {
                    $extension = 'jpeg';
                }
                $function = 'imagecreatefrom' . $extension;
                if(function_exists($function)) {
                    $previousMemoryLimit = ini_get('memory_limit');
                    ini_set('memory_limit', -1);
                    if (@$function($value->getPathname()) !== FALSE) {
                        ini_set('memory_limit', $previousMemoryLimit);
                        return;
                    }
                    ini_set('memory_limit', $previousMemoryLimit);
                    $this->context->addViolation($constraint->message);
                }
            }
        }
    }
}