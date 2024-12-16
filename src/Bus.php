<?php

namespace Circle33\LaravelBusFluentable;

use Circle33\LaravelBusFluentable\FluentPendingBatch;

class Bus
{
    public static function assertPendingBatched(callable $callback)
    {
        $dispatcher = app(\Illuminate\Contracts\Bus\Dispatcher::class);

        return collect($dispatcher->dispatchedBatches())
            ->filter(fn ($batch) => $callback(new FluentPendingBatch($dispatcher, $batch->jobs)));
    }
}
