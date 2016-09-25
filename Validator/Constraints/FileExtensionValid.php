<?php

namespace Ibtikar\GlanceUMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Exception\MissingOptionsException;
use Symfony\Component\Validator\Constraint;

/**
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 * @Annotation
 */
class FileExtensionValid extends Constraint {

    public $message = 'file not correct.';
    protected $extensions = array();

    /**
     * @param array $options
     * @throws MissingOptionsException
     */
    public function __construct(array $options = array()) {
        if (isset($options['extensions']) && count($options['extensions']) > 0) {
            parent::__construct($options);
            return;
        }
        throw new MissingOptionsException('You must set the extensions array with some values in the validation options.', array());
    }

    /**
     * @return array
     */
    public function getRequiredOptions() {
        return array('extensions');
    }

    /**
     * @return array
     */
    public function getExtensions() {
        return $this->extensions;
    }

}
