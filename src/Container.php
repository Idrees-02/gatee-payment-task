<?php

declare(strict_types=1);

namespace App;

use App\Catalog\Catalog;
use App\Database\Connection;
use App\Gateway\GateEClient;
use App\Repository\CustomerRepository;
use App\Repository\PaymentRepository;
use App\Repository\TransactionRepository;
use App\Service\CheckoutService;
use App\Service\PaymentSyncService;
use App\Support\Env;

/**
 * Wires the application's services together. Each service is created once, on first use.
 */
final class Container
{
    /** @var array<string, object> */
    private array $instances = [];

    public function checkout(): CheckoutService
    {
        return $this->once(__FUNCTION__, fn () => new CheckoutService(
            $this->connection(),
            $this->customers(),
            $this->payments(),
            $this->gateway(),
            Env::get('GATEE_CURRENCY', 'BHD'),
            Env::get('GATEE_GATEWAY_ID'),
        ));
    }

    public function paymentSync(): PaymentSyncService
    {
        return $this->once(__FUNCTION__, fn () => new PaymentSyncService(
            $this->payments(),
            $this->transactions(),
            $this->gateway(),
        ));
    }

    public function catalog(): Catalog
    {
        return $this->once(__FUNCTION__, fn () => new Catalog());
    }

    public function payments(): PaymentRepository
    {
        return $this->once(__FUNCTION__, fn () => new PaymentRepository($this->connection()));
    }

    public function transactions(): TransactionRepository
    {
        return $this->once(__FUNCTION__, fn () => new TransactionRepository($this->connection()));
    }

    private function customers(): CustomerRepository
    {
        return $this->once(__FUNCTION__, fn () => new CustomerRepository($this->connection()));
    }

    private function gateway(): GateEClient
    {
        return $this->once(__FUNCTION__, fn () => new GateEClient(
            Env::get('GATEE_API_URL', 'https://api01.gate-e.net/'),
            Env::require('GATEE_API_USER'),
            Env::require('GATEE_API_KEY'),
        ));
    }

    private function connection(): \PDO
    {
        return $this->once(__FUNCTION__, fn () => Connection::make(
            Env::get('DB_HOST', 'db'),
            Env::require('DB_NAME'),
            Env::require('DB_USER'),
            Env::require('DB_PASS'),
        ));
    }

    /**
     * @template T of object
     * @param callable(): T $factory
     * @return T
     */
    private function once(string $key, callable $factory): object
    {
        return $this->instances[$key] ??= $factory();
    }
}
