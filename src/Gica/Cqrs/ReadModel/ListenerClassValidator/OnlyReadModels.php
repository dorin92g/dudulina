<?php
/******************************************************************************
 * Copyright (c) 2016 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Gica\Cqrs\ReadModel\ListenerClassValidator;


use Gica\CodeAnalysis\MethodListenerDiscovery\ListenerClassValidator;
use Gica\Cqrs\ReadModel\ReadModelInterface;

class OnlyReadModels implements ListenerClassValidator
{
    public function isClassAccepted(\ReflectionClass $typeHintedClass):bool
    {
        return is_subclass_of($typeHintedClass->name, ReadModelInterface::class) && $typeHintedClass->name != ReadModelInterface::class;
    }
}