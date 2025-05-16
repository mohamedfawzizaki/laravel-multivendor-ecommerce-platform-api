<?php

namespace App\Providers;

use App\Models\User;
use StripePaymentGateway;
use Illuminate\Http\Request;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;
use App\Models\Invoices\Invoice;
use App\Services\PaymentService;
use App\Contracts\PaymentGateway;
use App\Listeners\LogMessageSent;
use App\Observers\InvoiceObserver;
use App\Listeners\LogMessageSending;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Mail;
use App\Models\Payments\OrderPayment;
use Illuminate\Support\Facades\Event;
use App\Observers\OrderPaymentObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use App\Repositories\RepositoryInterface;
use App\Models\Vendors\VendorPaymentAccount;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use App\Repositories\Eloquent\UserRepository;
use App\Observers\VendorPaymentAccountObserver;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Auth\Notifications\ResetPassword;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        // $this->app->bind(RepositoryInterface::class, BaseRepository::class);
        // $this->app->bind(UserRepository::class, function ($app) {
        //     return new UserRepository(new User());
        // });
        // $this->app->bind(UserService::class, function ($app) {
        //     return new UserService(new UserRepository(new User()));
        // });
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        
        /**
         * for models -----------------------------------------------------------------------
         */
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventLazyLoading(! $this->app->isProduction());
        // Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
        //     $class = $model::class;
        //     info("Attempted to lazy load [{$relation}] on model [{$class}].");
        // });


        // OrderPayment::observe(OrderPaymentObserver::class);
        VendorPaymentAccount::observe(VendorPaymentAccountObserver::class);
        Invoice::observe(InvoiceObserver::class);




        // $this->app->bind(
        //     \Illuminate\Contracts\Debug\ExceptionHandler::class,
        //     function ($app) {
        //         return new class($app) extends \Illuminate\Foundation\Exceptions\Handler {
        //             protected function unauthenticated($request, AuthenticationException $exception)
        //             {
        //                 return Response::json(['message' => 'Unauthenticated.'], 401);
        //             }
        //         };
        //     }
        // );




        /**
         * for testing -----------------------------------------------------------------------
         */
        // Hook: Runs once for each parallel test process as soon as it starts.
        ParallelTesting::setUpProcess(function (int $token) {
            // In an eCommerce app, you might want to create a temporary storage directory
            // for product images specific to each test process.
            // $tempStoragePath = storage_path("app/test_images_{$token}");
            // if (!is_dir($tempStoragePath)) {
            //     mkdir($tempStoragePath, 0777, true);
            // }

            // // Optionally, update the filesystem configuration so that your tests
            // // use this process-specific directory.
            // config(['filesystems.disks.test_images.root' => $tempStoragePath]);

            // // Log the process startup for debugging purposes.
            // logger()->info("Parallel test process {$token} started. Using temporary storage: {$tempStoragePath}");
        });

        // Hook: Runs before each test case in a parallel process.
        ParallelTesting::setUpTestCase(function (int $token, TestCase $testCase) {
            // For each test case, you could clear caches, reset mocks, or perform other per-test setups.
            // This is helpful if your tests depend on some global state.
            logger()->info("Setting up test case in process {$token}.");
        });

        // Hook: Runs when a test database is created for a parallel process.
        ParallelTesting::setUpTestDatabase(function (string $database, int $token) {
            // For an eCommerce app, you'll likely need to seed your database with
            // categories, products, orders, and other related data.
            // First, ensure the test database is fresh.
            // Artisan::call('migrate:fresh');

            // Then, seed the test database using a custom seeder.
            // Here, we assume you have an EcommerceTestSeeder that seeds:
            // - Categories
            // - Products (with associated images and stock)
            // - Orders and order items
            // - Customers, etc.
            // Artisan::call('db:seed', ['--class' => 'UserSeeder']);

            // Log that the database has been seeded for this process.
            // logger()->info("Test database '{$database}' seeded for process {$token}.");
        });

        // Hook: Runs after each test case in a parallel process.
        ParallelTesting::tearDownTestCase(function (int $token, TestCase $testCase) {
            // After each test case, you might clear any temporary data or reset state.
            logger()->info("Tearing down test case in process {$token}.");
        });

        // Hook: Runs once when the parallel test process is ending.
        ParallelTesting::tearDownProcess(function (int $token) {
            // Perform final cleanup, such as removing temporary directories.
            // $tempStoragePath = storage_path("app/test_images_{$token}");
            // if (is_dir($tempStoragePath)) {
            //     // For example, you might remove the directory after testing:
            //     // (Be cautious with recursive deletes in production code.)
            //     // @unlinkDirectory($tempStoragePath); // Pseudo-code; use a proper method.
            //     logger()->info("Parallel test process {$token} ending. Cleanup recommended for temporary storage: {$tempStoragePath}");
            // }
        });
    }
}