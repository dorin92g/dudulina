<?php
/******************************************************************************
 * Copyright (c) 2017 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Gica\Cqrs\CodeGeneration;


use Gica\CodeAnalysis\MethodListenerDiscovery;
use Gica\CodeAnalysis\MethodListenerDiscovery\ClassSorter\ByConstructorDependencySorter;
use Gica\CodeAnalysis\MethodListenerDiscovery\ListenerClassValidator\AnyPhpClassIsAccepted;
use Gica\Cqrs\Command\CodeAnalysis\ReadModelEventHandlerDetector;
use Gica\FileSystem\FileSystemInterface;
use Psr\Log\LoggerInterface;

class ReadModelEventListenersMapCodeGenerator implements Discoverer
{
    public function generate(
        LoggerInterface $logger,
        FileSystemInterface $fileSystem = null,
        string $eventSubscriberTemplateClassName,
        string $searchDirectory,
        string $outputFilePath,
        string $outputShortClassName
    )
    {
        $generator = new CodeGenerator();

        $generator->discoverAndPutContents(
            $this,
            $fileSystem,
            $eventSubscriberTemplateClassName,
            $searchDirectory,
            $outputFilePath,
            $outputShortClassName
        );

        $logger->info("Read models events handlers map wrote to: $outputFilePath");
    }

    public function discover(string $searchDirectory)
    {
        $discoverer = new MethodListenerDiscovery(
            new ReadModelEventHandlerDetector(),
            new AnyPhpClassIsAccepted,
            new ByConstructorDependencySorter()
        );

        $discoverer->discoverListeners($searchDirectory);

        return $discoverer->getEventToListenerMap();
    }
}