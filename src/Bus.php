<?php

namespace Circle33\LaravelBusFluentable;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Testing\Fakes\BusFake;
use PHPUnit\Framework\Assert as PHPUnit;

class Bus
{
    /**
     * Assert that a batch was dispatched based on a callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function assertPendingBatched(callable $callback)
    {
        /** @var BusFake $busFake */
        $busFake = app(Dispatcher::class);

        PHPUnit::assertTrue(
            collect($busFake->dispatchedBatches())
                ->filter(fn ($batch) =>$callback(new FluentPendingBatch($busFake, $batch->jobs)))
                ->isNotEmpty(),
            "The expected batch was not dispatched."
        );
    }
}
