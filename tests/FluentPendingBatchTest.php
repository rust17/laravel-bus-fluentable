<?php

namespace Circle33\LaravelBusFluentable\Tests;

use Circle33\LaravelBusFluentable\Bus as BusFacade;
use Circle33\LaravelBusFluentable\FluentPendingBatch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\AssertionFailedError;

class FluentPendingBatchTest extends TestCase
{
    public function test_pending_batch_has()
    {
        Bus::fake();

        Bus::batch([
            new AJob(1, 2),
            new BJob(),
            new CJob(),
            new DJob(),
        ])->dispatch();

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->has(AJob::class, [1, 2])
                ->has(BJob::class)
                ->has(CJob::class)
                ->etc()
        );
    }

    public function test_pending_batch_has_fail_when_missing()
    {
        Bus::fake();

        Bus::batch([
            new AJob(1, 2),
            new BJob(),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'The batch does not contain a job of type [Circle33\LaravelBusFluentable\Tests\CJob].'
        );

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->has(CJob::class));
    }

    public function test_pending_batch_has_fail_when_has_but_parameters_dont_match()
    {
        Bus::fake();

        Bus::batch([
            new AJob(1, 2),
            new BJob(),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'The job parameters does not match the expected values for class [Circle33\LaravelBusFluentable\Tests\AJob].'
        );

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->has(AJob::class));
    }

    public function test_pending_batch_has_count_fail_when_incorrect()
    {
        Bus::fake();

        Bus::batch([
            new AJob(1, 2),
            new BJob(),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'Failed to assert the batch contains the exact number of [3] jobs.'
        );

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->has(3));
    }

    public function test_pending_batch_missing()
    {
        Bus::fake();

        Bus::batch([
            new AJob(),
            new CJob(),
        ])->dispatch();

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->has(AJob::class)
                ->missing(BJob::class)
        );
    }

    public function test_pending_batch_missing_fail_when_has()
    {
        Bus::fake();

        Bus::batch([
            new AJob(),
            new CJob(),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'The batch does not miss a job of type [Circle33\LaravelBusFluentable\Tests\AJob].'
        );

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->missing(AJob::class));
    }

    public function test_pending_batch_has_all()
    {
        Bus::fake();

        Bus::batch([
            new AJob(),
            new BJob(),
        ])->dispatch();

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->hasAll([AJob::class, BJob::class]));
    }

    public function test_pending_batch_has_all_fail_when_missing()
    {
        Bus::fake();

        Bus::batch([
            new BJob(),
            new CJob(),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The batch does not contain all expected jobs.');

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->hasAll([AJob::class, BJob::class]));
    }

    public function test_pending_batch_missing_all()
    {
        Bus::fake();

        Bus::batch([
            new AJob(),
            new BJob(),
        ])->dispatch();

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->missingAll([CJob::class, DJob::class]));
    }

    public function test_pending_batch_missing_all_fail_when_has()
    {
        Bus::fake();

        Bus::batch([
            new AJob(),
            new BJob(),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The batch does not miss all of given jobs.');

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->missingAll([AJob::class]));
    }

    public function test_pending_batch_has_and_has_any()
    {
        Bus::fake();

        Bus::batch([
            new AJob(),
            new CJob(),
        ])->dispatch();

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->has(2)
                ->has(AJob::class)
                ->hasAny(BJob::class, CJob::class, DJob::class)
        );
    }

    public function test_pending_batch_has_any_fail_when_missing()
    {
        Bus::fake();

        Bus::batch([
            new AJob(),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The batch does not contains any of the expected jobs.');

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->hasAny(BJob::class, CJob::class, DJob::class));
    }

    public function test_nested_jobs_in_pending_batch()
    {
        Bus::fake();

        Bus::batch([
            [
                new AJob(1),
                new BJob(1),
            ],
            new CJob(2),
            [
                new CJob(2),
                new DJob(2),
            ],
        ])->dispatch();

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->has(3)
                ->first(
                    fn (FluentPendingBatch $assert) => $assert->has(AJob::class, [1])
                        ->has(BJob::class, [1])
                )
                ->nth(1, fn (FluentPendingBatch $assert) => $assert->has(CJob::class, [2]))
                ->nth(
                    2,
                    fn (FluentPendingBatch $assert) => $assert->has(CJob::class, [2])
                        ->has(DJob::class, [2])
                )
        );
    }

    public function test_nested_jobs_in_pending_batch_fail_when_first_missing()
    {
        Bus::fake();

        Bus::batch([
            [
                new AJob(1),
                new BJob(1),
            ],
            new CJob(2),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'The first one in the batch does not matches the given callback: The batch does not contain a job of type [Circle33\LaravelBusFluentable\Tests\CJob]'
        );

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->first(
                fn (FluentPendingBatch $assert) => $assert->has(2)
                    ->has(BJob::class, [1])
                    ->has(CJob::class, [2])
            )
        );
    }

    public function test_nth_job_in_pending_batch()
    {
        Bus::fake();

        Bus::batch([
            new AJob(0, 1),
            new BJob(1),
            new CJob(1),
        ])->dispatch();

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->nth(0, AJob::class, [0, 1])
                ->nth(1, BJob::class, [1])
                ->nth(2, CJob::class, [1])
        );
    }

    public function test_nth_job_in_pending_batch_fail_when_missing()
    {
        Bus::fake();

        Bus::batch([
            new AJob(0, 1),
            new BJob(1),
            new CJob(1),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The batch does not contains a job at index [3].');

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->nth(3, CJob::class));
    }

    public function test_nested_jobs_in_pending_batch_fail_when_nth_missing()
    {
        Bus::fake();

        Bus::batch([
            [
                new AJob(1),
                new BJob(1),
            ],
            new CJob(2),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'The [1st] one in the batch does not matches the given callback: The batch does not contain a job of type [Circle33\LaravelBusFluentable\Tests\AJob]'
        );

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->nth(1, fn (FluentPendingBatch $assert) => $assert->has(AJob::class, [1])->has(BJob::class, [1])));
    }

    public function test_nested_jobs_in_pending_batch_fail_when_nth_does_not_match()
    {
        Bus::fake();

        Bus::batch([
            new AJob(0, 1),
            new BJob(1),
            new CJob(1),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'The batch does not contain a job of type [Circle33\LaravelBusFluentable\Tests\DJob] at index [2].'
        );

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->nth(0, AJob::class, [0, 1])
                ->nth(1, BJob::class, [1])
                ->nth(2, DJob::class, [1])
        );
    }

    public function test_nested_jobs_in_pending_batch_fail_when_nth_has_but_params_does_not_match()
    {
        Bus::fake();

        Bus::batch([
            [
                new AJob(1),
                new BJob(1),
            ],
            new CJob(2),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'The [0th] one in the batch does not matches the given callback: The job parameters does not match the expected values for class [Circle33\LaravelBusFluentable\Tests\AJob].'
        );

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->nth(0, fn (FluentPendingBatch $assert) => $assert->has(AJob::class, [2])->has(BJob::class)));
    }

    public function test_equal_jobs_in_pending_batch()
    {
        Bus::fake();

        Bus::batch([
            new AJob(0, 1),
            new BJob(1),
            new CJob(1),
            new DJob(2),
            new EJob(2, 3),
        ])->dispatch();

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->equal([
            AJob::class => [0, 1],
            BJob::class => [1],
            CJob::class => [1],
            DJob::class => [2],
            EJob::class => [2, 3],
        ]));
    }

    public function test_equal_jobs_in_pending_batch_fail_when_job_missing()
    {
        Bus::fake();

        Bus::batch([
            new AJob(0, 1),
            new BJob(1),
            new CJob(1),
            new DJob(2),
            new EJob(2, 3),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'The job of type [Circle33\LaravelBusFluentable\Tests\EJob] does not exists.'
        );

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->equal([
            AJob::class => [0, 1],
            BJob::class => [1],
            CJob::class => [1],
            DJob::class => [2],
        ]));
    }

    public function test_equal_jobs_in_pending_batch_fail_when_job_parameters_does_not_match()
    {
        Bus::fake();

        Bus::batch([
            new AJob(0, 1),
            new BJob(1),
            new CJob(1),
            new DJob(2),
            new EJob(2, 3),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'The job parameters does not match the expected values for class [Circle33\LaravelBusFluentable\Tests\EJob].'
        );

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->equal([
            AJob::class => [0, 1],
            BJob::class => [1],
            CJob::class => [1],
            DJob::class => [2],
            EJob::class,
        ]));
    }

    public function test_equal_with_nested_jobs_in_pending_batch()
    {
        Bus::fake();

        Bus::batch([
            new AJob(1),
            [
                new BJob(2, 3),
                new CJob(),
                new DJob(4),
            ],
            [
                new AJob(1, 2),
                new BJob(),
            ],
            new CJob(),
        ])->dispatch();

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->equal([
            AJob::class => [1],
            [
                BJob::class => [2, 3],
                CJob::class,
                DJob::class => [4],
            ],
            [
                AJob::class => [1, 2],
                BJob::class,
            ],
            CJob::class,
        ]));
    }

    public function test_equal_with_nested_jobs_in_pending_batch_fail_when_is_not_array()
    {
        Bus::fake();

        Bus::batch([
            new AJob(1),
            [
                new BJob(2, 3),
                new CJob(),
                new DJob(4),
            ],
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The one in the batch at index [1] is not an array.');

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->equal([
            AJob::class => [1],
            BJob::class => [2, 3],
            CJob::class,
            DJob::class => [4],
        ]));
    }

    public function test_equal_with_nested_jobs_in_pending_batch_fail_when_is_not_same()
    {
        Bus::fake();

        Bus::batch([
            new AJob(1),
            [
                new BJob(2, 3),
                new CJob(),
                new DJob(4),
            ],
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The [1st] one in the batch at index [2] does not match: The job of type [Circle33\LaravelBusFluentable\Tests\DJob] does not exists.');

        BusFacade::assertPendingBatched(fn (FluentPendingBatch $assert) => $assert->equal([
            AJob::class => [1],
            [
                BJob::class => [2, 3],
                CJob::class,
            ],
        ]));
    }

    public function test_etc_with_additional_job()
    {
        Bus::fake();

        Bus::batch([
            new AJob(),
            new BJob(),
            new CJob(),
            new DJob(),
        ])->dispatch();

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->has(AJob::class)
                ->has(BJob::class)
                ->has(CJob::class)
                ->etc()
        );
    }

    public function test_etc_with_different_order()
    {
        Bus::fake();

        Bus::batch([
            new AJob(),
            new BJob(),
            new CJob(),
            new DJob(),
        ])->dispatch();

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->has(CJob::class)
                ->has(AJob::class)
                ->has(BJob::class)
                ->etc()
        );
    }

    public function test_etc_with_single_duplicate()
    {
        Bus::fake();

        Bus::batch([
            new AJob(),
            new BJob(),
            new AJob(),
            new CJob(),
            new DJob(),
        ])->dispatch();

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->has(AJob::class)
                ->has(BJob::class)
                ->has(AJob::class)
                ->has(CJob::class)
                ->etc()
        );
    }

    public function test_etc_with_multiple_duplicates()
    {
        Bus::fake();

        Bus::batch([
            new AJob(),
            new BJob(),
            new AJob(),
            new BJob(),
            new CJob(),
            new DJob(),
        ])->dispatch();

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->has(AJob::class)
                ->has(BJob::class)
                ->has(AJob::class)
                ->has(BJob::class)
                ->has(CJob::class)
                ->etc()
        );
    }

    public function test_etc_with_reordered_duplicates()
    {
        Bus::fake();

        Bus::batch([
            new AJob(),
            new BJob(),
            new AJob(),
            new CJob(),
            new DJob(),
        ])->dispatch();

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->has(BJob::class)
                ->has(AJob::class)
                ->has(CJob::class)
                ->has(AJob::class)
                ->etc()
        );
    }

    public function test_etc_with_no_unexpected_jobs()
    {
        Bus::fake();

        Bus::batch([
            new AJob(),
            new BJob(),
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('There are no additional jobs in the batch.');

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->has(AJob::class)
                ->has(BJob::class)
                ->etc()
        );
    }

    public function test_etc_with_nested_jobs()
    {
        Bus::fake();

        Bus::batch([
            [
                new AJob(),
                new BJob(),
            ],
            new CJob(),
            [
                new CJob(),
                new DJob(),
            ],
        ])->dispatch();

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->first(
                fn (FluentPendingBatch $assert) => $assert->has(AJob::class)
                    ->has(BJob::class)
            )->nth(1, fn (FluentPendingBatch $assert) => $assert->has(CJob::class))->etc()
        );
    }

    public function test_etc_with_nested_jobs_fail()
    {
        Bus::fake();

        Bus::batch([
            [
                new AJob(),
                new BJob(),
            ],
            new CJob(),
            [
                new CJob(),
                new DJob(),
            ],
        ])->dispatch();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('There are no additional jobs in the batch.');

        BusFacade::assertPendingBatched(
            fn (FluentPendingBatch $assert) => $assert->first(fn (FluentPendingBatch $assert) => $assert->has(AJob::class)
            ->has(BJob::class))
            ->nth(1, fn (FluentPendingBatch $assert) => $assert->has(CJob::class))
            ->nth(
                2,
                fn (FluentPendingBatch $assert) => $assert->has(CJob::class)
                    ->has(DJob::class)
            )->etc()
        );
    }
}

trait Parameterable
{
    public $parameters = [];

    public function __construct(...$parameters)
    {
        $this->parameters = $parameters;
    }
}

class AJob
{
    use Queueable;
    use Batchable;
    use Parameterable;
}

class BJob
{
    use Queueable;
    use Batchable;
    use Parameterable;
}

class CJob
{
    use Queueable;
    use Batchable;
    use Parameterable;
}

class DJob
{
    use Queueable;
    use Batchable;
    use Parameterable;
}

class EJob
{
    use Queueable;
    use Batchable;
    use Parameterable;
}
