<?php

namespace Marello\Bundle\MagentoBundle\Provider\Reader;

use Doctrine\ORM\Query\Expr\Join;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader;

class ProductExportReader extends EntityReader implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function createSourceEntityQueryBuilder($entityName, Organization $organization = null, array $ids = [])
    {
        $qb = parent::createSourceEntityQueryBuilder($entityName, $organization, $ids);

        //TODO: filter based on sales channels relation

        /*
        $queryBuilder
            ->where(
                $queryBuilder->expr()->isMemberOf(':salesChannel', 'product.channels')
            )
            ->setParameter('salesChannel', $salesChannels);
        */

        return $qb;
    }
}
