<?php

namespace Tests\Unit;

use App\Factories\OrderStorageFactory;
use App\Strategies\Order\JPYStorage;
use App\Strategies\Order\MYRStorage;
use App\Strategies\Order\RMBStorage;
use App\Strategies\Order\TWDStorage;
use App\Strategies\Order\USDStorage;
use PHPUnit\Framework\TestCase;

class OrderStorageFactoryTest extends TestCase
{
    /**
     * @dataProvider currencyProvider
     * @descrption 測試 OrderStorageFactory 是否正確返回對應的存儲實例
     */
    public function test_returns_correct_storage_instance($currency, $expectedClass)
    {
        $storage = OrderStorageFactory::make($currency);

        $this->assertInstanceOf($expectedClass, $storage);
    }

    public static function currencyProvider(): array
    {
        return [
            'twd case' => ['TWD', TWDStorage::class],
            'usd case' => ['USD', USDStorage::class],
            'jpy case' => ['JPY', JPYStorage::class],
            'myr case' => ['MYR', MYRStorage::class],
            'rmb case' => ['RMB', RMBStorage::class],
        ];
    }

    /**
     * @dataProvider invalidCurrencyProvider
     * @descrption 測試 OrderStorageFactory 對於不支持的貨幣類型是否拋出異常
     */
    public function test_throws_exception_for_unsupported_currency($invalidCurrency)
    {
        $this->expectException(\Exception::class);
        OrderStorageFactory::make($invalidCurrency);
    }

    public static function invalidCurrencyProvider(): array
    {
        return [
            'empty string' => [''],
            'unsupported'  => ['GBP'],
        ];
    }
}
