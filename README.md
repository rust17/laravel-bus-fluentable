When using Laravel, the current approach to testing batched jobs, as shown below, is somewhat unconventional:

```
Bus::assertBatched(fn (PendingBatchFake $batchedCollection) =>
    $batchedCollection->jobs->count() === 1 && $batchedCollection->jobs->first()->value === 'hello';
);
```

This package introduces some helper functions to enhance the testability of batched jobs. Inspired by [fluent JSON testing](https://laravel.com/docs/11.x/http-tests#fluent-json-testing), these methods provide a more streamlined and readable approach to testing job batches within the Laravel application. The goal is to improve developer experience by offering clear, concise methods with illustrative examples.

## Documentation

### `has`

Assert that the batch contains a job of the given type. You can also pass an integer to assert that the batch contains the exact number of jobs.

**Example:**

```php
use Circle33\LaravelBusFluentable\Bus as BusFacade;
use Circle33\LaravelBusFluentable\FluentPendingBatch;

Bus::fake();

Bus::batch([
    new AJob(1, 2),
    new BJob,
])->dispatch();

BusFacade::assertPendingBatched(fn (FluentPendingBatch $batch) =>
    $batch->has(2)
        ->has(AJob::class, [1, 2])
);
```

### `missing`

Assert that the batch does not contain a job of the given type.

**Example:**

```php
use Circle33\LaravelBusFluentable\Bus as BusFacade;
use Circle33\LaravelBusFluentable\FluentPendingBatch;

Bus::fake();

Bus::batch([
    new BJob,
])->dispatch();

BusFacade::assertPendingBatched(fn (FluentPendingBatch $batch) =>
    $batch->missing(AJob::class)
);
```

### `hasAll`

Assert that the batch contains all of the given jobs.

**Example:**

```php
use Circle33\LaravelBusFluentable\Bus as BusFacade;
use Circle33\LaravelBusFluentable\FluentPendingBatch;

Bus::fake();

Bus::batch([
    new AJob,
    new BJob,
])->dispatch();

BusFacade::assertPendingBatched(fn (FluentPendingBatch $batch) =>
    $batch->hasAll([AJob::class, BJob::class])
);
```

### `missingAll`

Assert that the batch does not contain any of the given jobs.

**Example:**

```php
use Circle33\LaravelBusFluentable\Bus as BusFacade;
use Circle33\LaravelBusFluentable\FluentPendingBatch;

Bus::fake();

Bus::batch([
    new AJob,
    new BJob,
])->dispatch();

BusFacade::assertPendingBatched(fn (FluentPendingBatch $batch) =>
    $batch->missingAll([CJob::class, DJob::class])
);
```

### `hasAny`

Assert that the batch contains any of the given jobs.

**Example:**

```php
use Circle33\LaravelBusFluentable\Bus as BusFacade;
use Circle33\LaravelBusFluentable\FluentPendingBatch;

Bus::fake();

Bus::batch([
    new AJob(1, 2),
    new BJob,
])->dispatch();

BusFacade::assertPendingBatched(fn (FluentPendingBatch $batch) =>
    $batch->hasAny(AJob::class, CJob::class)
);
```

### `first`

Assert that the first job in the batch matches the given callback.

**Example:**

```php
use Circle33\LaravelBusFluentable\Bus as BusFacade;
use Circle33\LaravelBusFluentable\FluentPendingBatch;

Bus::fake();

Bus::batch([
    [
        new AJob(1, 2),
        new BJob,
    ],
    new CJob,
])->dispatch();

BusFacade::assertPendingBatched(fn (FluentPendingBatch $batch) =>
    $batch->first(fn (PendingBatchFake $firstBatch) =>
        $firstBatch->has(AJob::class, [1, 2])
            ->has(BJob::class)
    )
);
```

### `nth`

Assert that the nth job in the batch matches the given callback or type and parameters.

**Example:**

```php
use Circle33\LaravelBusFluentable\Bus as BusFacade;
use Circle33\LaravelBusFluentable\FluentPendingBatch;

Bus::fake();

Bus::batch([
    [
        new AJob(1, 2),
        new BJob
    ],
    new CJob::class(1)
])->dispatch();

BusFacade::assertPendingBatched(fn (FluentPendingBatch $batch) =>
    $batch->nth(0, fn (FluentPendingBatch $batch) =>
        $batch->has(AJob::class, [1, 2])
            ->has(BJob::class)
    )->nth(1, CJob::class, [1])
);
```

### `equal`

Assert that the batch contains exactly the given jobs with the specified parameters.

**Example:**

```php
use Circle33\LaravelBusFluentable\Bus as BusFacade;
use Circle33\LaravelBusFluentable\FluentPendingBatch;

Bus::fake();

Bus::batch([
    [
        new AJob(1, 2),
        new BJob
    ],
    new CJob::class(1)
])->dispatch();

BusFacade::assertPendingBatched(fn (FluentPendingBatch $batch) =>
    $batch->equal([
        [
            AJob::class => [1, 2],
            BJob::class
        ],
        CJob::class => [1]
    ])
);
```

### `etc`

Assert that the batch has unexpected jobs beyond those checked.

**Example:**

```php
use Circle33\LaravelBusFluentable\Bus as BusFacade;
use Circle33\LaravelBusFluentable\FluentPendingBatch;

Bus::fake();

Bus::batch([
    new AJob(1, 2),
    new BJob,
    new CJob::class(1)
])->dispatch();

BusFacade::assertPendingBatched(fn (FluentPendingBatch $batch) =>
    $batch->has(AJob::class, [1, 2])
        ->has(BJob::class)
        ->etc()
);
```

# Contributing

Thank you for considering contributing to this project! We welcome and appreciate your help.

1. Fork the repository to your GitHub account.
2. Clone your forked repository to your local machine:
    ```sh
    git clone https://github.com/your-username/laravel-bus-fluentable.git

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.