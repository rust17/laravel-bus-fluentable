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
            collect(self::getDispatchedBatches())
                ->filter(fn ($batch) => $callback(new FluentPendingBatch($busFake, $batch->jobs)))
                ->isNotEmpty(),
            "The expected batch was not dispatched."
        );
    }

    /**
     * Get all the batches that have been dispatched.
     *
     * @return array<\Illuminate\Support\Testing\Fakes\PendingBatchFake>
     */
    private static function getDispatchedBatches()
    {
        /** @phpstan-ignore variable.undefined */
        return (fn () => $this->batches)->bindTo(app(Dispatcher::class), BusFake::class)();
    }
}
