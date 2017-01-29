<?php

namespace Ibtikar\GlanceUMSBundle\Document;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * @author Moemen Hussein <momen.shaaban@ibtikar.net.sa>
 * CountryRepository
 */
class CountryRepository extends DocumentRepository {


    public function findCountrySorted($locale) {

        return $this->getDocumentManager()->createQueryBuilder('IbtikarGlanceUMSBundle:Country')
                ->sort('specialCountrySort', 'DESC')
                ->sort('countryUsageCount', 'DESC')
                ->sort('countryName', 'ASC');
    }
}
