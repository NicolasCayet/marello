<?php

namespace Marello\Bundle\InventoryBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Marello\Bundle\ProductBundle\Entity\Product;
use Marello\Bundle\InventoryBundle\Entity\InventoryItem;
use Marello\Bundle\InventoryBundle\Manager\InventoryItemManagerInterface;

class OnProductCreateEventListener
{
    /** @var InventoryItemManagerInterface $producer */
    protected $inventoryItemManager;

    /** @var UnitOfWork $unitOfWork */
    protected $unitOfWork;

    /** @var EntityManager $em */
    protected $em;

    /**
     * InventoryLevelEventListener constructor.
     * @param InventoryItemManagerInterface $manager
     */
    public function __construct(InventoryItemManagerInterface $manager)
    {
        $this->inventoryItemManager = $manager;
    }

    /**
     * Handle incoming event
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->em = $eventArgs->getEntityManager();
        $this->unitOfWork = $this->em->getUnitOfWork();

        if (!empty($this->unitOfWork->getScheduledEntityInsertions())) {
            $records = $this->filterRecords($this->unitOfWork->getScheduledEntityInsertions());
            $this->applyCallBackForChangeSet('createInventoryItem', $records);

        }
    }

    /**
     * @param Product $entity
     */
    protected function createInventoryItem(Product $entity)
    {
        $result = $this->inventoryItemManager->createInventoryItem($entity);
        if ($result) {
            $inventoryItem = $this->checkReplenishment($result);
            $this->em->persist($inventoryItem);
            $classMeta = $this->em->getClassMetadata(get_class($inventoryItem));
            $this->unitOfWork->computeChangeSet($classMeta, $inventoryItem);
        }
    }

    /**
     * @param InventoryItem $item
     * @return mixed
     */
    protected function checkReplenishment($item)
    {
        if (!$item->getReplenishment()) {
            // get default replenishment option
            $replenishment = $this->inventoryItemManager->getDefaultReplenishment();
            if ($replenishment) {
                $item->setReplenishment($replenishment);
            }
        }

        return $item;
    }

    /**
     * @param array $records
     * @return array
     */
    protected function filterRecords(array $records)
    {
        return array_filter($records, [$this, 'getIsEntityInstanceOf']);
    }

    /**
     * @param $entity
     * @return bool
     */
    public function getIsEntityInstanceOf($entity)
    {
        return ($entity instanceof Product);
    }

    /**
     * @param string $callback function
     * @param array $changeSet
     * @throws \Exception
     */
    protected function applyCallBackForChangeSet($callback, array $changeSet)
    {
        try {
            array_walk($changeSet, [$this, $callback]);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
