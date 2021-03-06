<?php
/**
 * Copyright (c) 2018 Constantin Galbenu <xprt64@gmail.com>
 */

namespace Dudulina\ReadModel;


use Dudulina\Event\EventWithMetaData;
use Dudulina\ReadModel\ReadModelEventApplier\ReadModelReflector;
use Gica\CodeAnalysis\MethodListenerDiscovery\ListenerMethod;
use Psr\Log\LoggerInterface;

class ReadModelEventApplier
{
    /** @var OnlyOnceTracker */
    private $onlyOnceTracker;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ReadModelReflector
     */
    private $readModelReflector;

    public function __construct(
        LoggerInterface $logger,
        ReadModelReflector $readModelReflector
    )
    {
        $this->onlyOnceTracker = new OnlyOnceTracker();
        $this->logger = $logger;
        $this->readModelReflector = $readModelReflector;
    }

    public function applyEventOnlyOnce($readModel, EventWithMetaData $eventWithMetadata): void
    {
        if ($this->onlyOnceTracker->isEventAlreadyApplied($readModel, (string)$eventWithMetadata->getMetaData()->getEventId())) {
            return;
        }
        $this->onlyOnceTracker->markEventAsApplied($readModel, (string)$eventWithMetadata->getMetaData()->getEventId());

        $this->applyEvent($readModel, $eventWithMetadata);
    }

    private function applyEvent($readModel, EventWithMetaData $eventWithMetadata): void
    {
        $methods = $this->readModelReflector->getListenersForEvent($readModel, \get_class($eventWithMetadata->getEvent()));
        foreach ($methods as $method) {
            $this->executeMethod($readModel, $method, $eventWithMetadata);
        }
    }

    private function executeMethod($readModel, ListenerMethod $method, EventWithMetaData $eventWithMetadata): void
    {
        try {
            $readModel->{$method->getMethodName()}($eventWithMetadata->getEvent(), $eventWithMetadata->getMetaData());
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), [
                'model'          => \get_class($readModel),
                'eventId'        => $eventWithMetadata->getMetaData()->getEventId(),
                'aggregateId'    => $eventWithMetadata->getMetaData()->getAggregateId(),
                'aggregateClass' => $eventWithMetadata->getMetaData()->getAggregateClass(),
                'file'           => $exception->getFile(),
                'line'           => $exception->getLine(),
            ]);
        }
    }
}