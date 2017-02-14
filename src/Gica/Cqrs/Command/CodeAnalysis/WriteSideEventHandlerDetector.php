<?php
/******************************************************************************
 * Copyright (c) 2016 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Gica\Cqrs\Command\CodeAnalysis;


class WriteSideEventHandlerDetector implements \Gica\CodeAnalysis\MethodListenerDiscovery\MessageClassDetector
{
    public function isMessageClass(\ReflectionClass $typeHintedClass):bool
    {
        return is_subclass_of($typeHintedClass->name, \Gica\Cqrs\Event::class) &&
        $typeHintedClass->name != \Gica\Cqrs\Event::class;
    }

    public function isMethodAccepted(\ReflectionMethod $reflectionMethod):bool
    {
        return 0 === stripos($reflectionMethod->name, 'process');
    }
}