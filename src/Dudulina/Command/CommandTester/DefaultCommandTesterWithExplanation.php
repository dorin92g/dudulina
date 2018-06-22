<?php
/**
 * Copyright (c) 2017 Constantin Galbenu <xprt64@gmail.com>
 */

namespace Dudulina\Command\CommandTester;

use Dudulina\Aggregate\AggregateDescriptor;
use Dudulina\Aggregate\EventSourcedAggregateRepository;
use Dudulina\Command;
use Dudulina\Command\CommandApplier;
use Dudulina\Command\CommandSubscriber;
use Dudulina\Command\CommandWithMetadata;
use Dudulina\Command\MetadataWrapper as CommandMetadataFactory;
use Dudulina\Command\ValueObject\CommandHandlerAndAggregate;
use Dudulina\Event\EventsApplier\EventsApplierOnAggregate;
use Dudulina\Event\EventWithMetaData;
use Dudulina\Event\MetaData;
use Dudulina\Event\MetadataFactory as EventMetadataFactory;
use Dudulina\Scheduling\ScheduledCommand;
use Gica\Types\Guid;

class DefaultCommandTesterWithExplanation implements Command\CommandTesterWithExplanation
{
    /**
     * @var CommandSubscriber
     */
    private $commandSubscriber;
    /**
     * @var CommandApplier
     */
    private $commandApplier;
    /**
     * @var EventSourcedAggregateRepository
     */
    private $aggregateRepository;
    /**
     * @var EventsApplierOnAggregate
     */
    private $eventsApplierOnAggregate;
    /**
     * @var EventMetadataFactory
     */
    private $eventMetadataFactory;
    /**
     * @var CommandMetadataFactory
     */
    private $commandMetadataFactory;

    public function __construct(
        CommandSubscriber $commandSubscriber,
        CommandApplier $commandApplier,
        EventSourcedAggregateRepository $aggregateRepository,
        EventsApplierOnAggregate $eventsApplier,
        EventMetadataFactory $eventMetadataFactory,
        CommandMetadataFactory $commandMetadataFactory
    )
    {
        $this->commandSubscriber = $commandSubscriber;
        $this->commandApplier = $commandApplier;
        $this->aggregateRepository = $aggregateRepository;
        $this->eventsApplierOnAggregate = $eventsApplier;
        $this->eventMetadataFactory = $eventMetadataFactory;
        $this->commandMetadataFactory = $commandMetadataFactory;
    }

    public function whyCantExecuteCommand(Command $command)
    {
        try {
            $command = $this->commandMetadataFactory->wrapCommandWithMetadata($command, null);
            $this->applyCommand($command, $this->loadCommandHandlerAndAggregate($command));
            return [];
        } catch (\Exception $exception) {
            return [$exception];
        }
    }

    private function loadCommandHandlerAndAggregate(CommandWithMetadata $command): CommandHandlerAndAggregate
    {
        return new CommandHandlerAndAggregate(
            $this->commandSubscriber->getHandlerForCommand($command->getCommand()),
            $this->aggregateRepository->loadAggregate(
                new AggregateDescriptor(
                    $command->getAggregateId(),
                    $this->commandSubscriber->getHandlerForCommand($command->getCommand())->getHandlerClass()
                )
            )
        );
    }

    private function decorateEventWithMetaData($event, MetaData $metaData): EventWithMetaData
    {
        return new EventWithMetaData($event, $metaData->withEventId(Guid::generate()));
    }

    /**
     * @param CommandWithMetadata $command
     * @param CommandHandlerAndAggregate $handlerAndAggregate
     * @return void
     */
    private function applyCommand(CommandWithMetadata $command, CommandHandlerAndAggregate $handlerAndAggregate)
    {
        $aggregate = $handlerAndAggregate->getAggregate();
        $handler = $handlerAndAggregate->getCommandHandler();

        $metaData = $this->eventMetadataFactory->factoryEventMetadata($command, $aggregate);

        $newMessageGenerator = $this->commandApplier->applyCommand($aggregate, $command->getCommand(), $handler->getMethodName());

        foreach ($newMessageGenerator as $message) {
            if (!$this->isScheduledCommand($message)) {
                $eventWithMetaData = $this->decorateEventWithMetaData($message, $metaData);
                $this->eventsApplierOnAggregate->applyEventsOnAggregate($aggregate, [$eventWithMetaData]);
            }
        }
    }

    private function isScheduledCommand($message): bool
    {
        return $message instanceof ScheduledCommand;
    }
}