<?php
/******************************************************************************
 * Copyright (c) 2016 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Gica\Cqrs\EventStore;


interface AggregateEventStream extends \Gica\Cqrs\EventStore\EventStream
{
    public function getVersion():int;

    public function getSequence() : int;
}