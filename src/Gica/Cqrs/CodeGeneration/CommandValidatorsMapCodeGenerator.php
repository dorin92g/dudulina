<?php
/******************************************************************************
 * Copyright (c) 2017 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Gica\Cqrs\CodeGeneration;


use Gica\CodeAnalysis\MethodListenerDiscovery;
use Gica\CodeAnalysis\MethodListenerDiscovery\ClassSorter\ByConstructorDependencySorter;
use Gica\CodeAnalysis\MethodListenerDiscovery\ListenerClassValidator\AnyPhpClassIsAccepted;
use Gica\CodeAnalysis\MethodListenerDiscovery\MethodListenerMapperWriter;
use Gica\Cqrs\Command\CodeAnalysis\AggregateCommandValidatorDetector;
use Gica\FileSystem\FileSystemInterface;
use Gica\FileSystem\OperatingSystemFileSystem;
use Psr\Log\LoggerInterface;

class CommandValidatorsMapCodeGenerator
{
    public function generate(
        LoggerInterface $logger,
        FileSystemInterface $fileSystem = null,
        string $commandValidatorSubscriberTemplateClassName,
        string $searchDirectory,
        string $outputFilePath,
        string $outputShortClassName
    )
    {
        $fileSystem = $fileSystem ?? new OperatingSystemFileSystem();

        $classInfo = new \ReflectionClass($commandValidatorSubscriberTemplateClassName);

        $this->deleteFileIfExists($fileSystem, $outputFilePath);

        $discoverer = new MethodListenerDiscovery(
            new AggregateCommandValidatorDetector(),
            new AnyPhpClassIsAccepted,
            new ByConstructorDependencySorter());

        $discoverer->discoverListeners($searchDirectory);

        $map = $discoverer->getEventToListenerMap();

        $writer = new MethodListenerMapperWriter();

        $template = $fileSystem->fileGetContents($classInfo->getFileName());

        $template = str_replace($classInfo->getShortName() /*CommandValidatorSubscriberTemplate*/, $outputShortClassName /*CommandValidatorSubscriber*/, $template);

        $template = str_replace('--- This is just a template ---', '--- generated by ' . __FILE__ . ' at ' . date('c') . ' ---', $template);

        $code = $writer->generateAndGetFileContents($map, $template);

        $fileSystem->filePutContents($outputFilePath, $code);

        $fileSystem->fileSetPermissions($outputFilePath, 0777);

        $logger->info("Commands validators wrote to: $outputFilePath");
    }

    private function deleteFileIfExists(FileSystemInterface $fileSystem, string $outputFilePath)
    {
        try {
            $fileSystem->fileDelete($outputFilePath);
        } catch (\Exception $exception) {
            //it's ok
        }
    }
}