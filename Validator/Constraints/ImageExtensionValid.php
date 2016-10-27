<?php

namespace Ibtikar\GlanceUMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Exception\MissingOptionsException;
use Ibtikar\GlanceUMSBundle\Validator\Constraints\FileExtensionValid;

/**
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 * @Annotation
 */
class ImageExtensionValid extends FileExtensionValid {

    public $message = 'picture not correct.';
    protected $extensions = array('jpg', 'jpeg', 'png');

    /**
     * @param array $options
     * @throws MissingOptionsException
     */
    public function __construct(array $options = array()) {
        if (!isset($options['extensions']) || count($options['extensions']) === 0) {
            $options['extensions'] = $this->extensions;
        }
        parent::__construct($options);
    }

    /**
     * @return string
     */
    public function validatedBy() {
        return 'Ibtikar\GlanceUMSBundle\Validator\Constraints\FileExtensionValidValidator';
    }

}
