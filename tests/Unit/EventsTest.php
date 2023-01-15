<?php

declare(strict_types=1);

namespace Tests\Unit;

use Co\System;
use PHPUnit\Framework\TestCase;
use OpenSwoole\Process;
use OpenSwoole\Table;
use Tests\Samples\SampleEvent;

final class EventsTest extends TestCase
{
    public function test_can_add_listeners_and_dispatch_async_event()
    {
        $table = new Table(1024);
        $table->column('executed', Table::TYPE_INT, 1);
        $table->create();

        $table->set('executed-1', ['executed' => 0]);
        $table->set('executed-2', ['executed' => 0]);

        add_event_listener(SampleEvent::class, function(SampleEvent $event) use (&$table) {
            $executed = $table->get('executed-1');
            $executed['executed'] = 1;
            $table->set('executed-1', $executed);
        });

        add_event_listener(SampleEvent::class, function(SampleEvent $event) use (&$table) {
            $executed = $table->get('executed-2');
            $executed['executed'] = 1;
            $table->set('executed-2', $executed);
        });

        $process = new Process(function() {
            dispatch_event(new SampleEvent);
        });
        $process->start();

        \Co\run(function() use (&$table) {
            System::wait();

            $this->assertTrue($table->get('executed-1')['executed'] === 1);
            $this->assertTrue($table->get('executed-2')['executed'] === 1);
        });
    }

    public function test_can_add_listeners_and_dispatch_sync_event()
    {
        $executed1 = false;
        $executed2 = false;

        add_event_listener(SampleEvent::class, function(SampleEvent $event) use (&$executed1) {
            $executed1 = true;
        });

        add_event_listener(SampleEvent::class, function(SampleEvent $event) use (&$executed2) {
            $executed2 = true;
        });

        dispatch_event(new SampleEvent, true);

        $this->assertTrue($executed1);
        $this->assertTrue($executed2);
    }
}
