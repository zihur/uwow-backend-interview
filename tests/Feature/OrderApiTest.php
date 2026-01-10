<?php

namespace Tests\Feature;

use App\Enums\Currency;
use App\Events\OrderCreated;
use App\Models\OrderTWD;
use App\Models\OrderUSD;
use Database\Factories\OrderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    // 測試是否有成功觸發事件
    public function test_can_dispatch_order_created_event_successfully()
    {
        Event::fake();

        $payload = [
            'id' => 'A0000001',
            'name' => 'Melody Holiday Inn',
            'address' => [
                'city' => 'taipei-city',
                'district' => 'da-an-district',
                'street' => 'fuxing-south-road'
            ],
            'price' => '1000',
            'currency' => 'TWD'
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(200);
        Event::assertDispatched(OrderCreated::class, function ($event) use ($payload) {
            return $event->order->id === $payload['id'];
        });
    }

    // 測試是否有正確驗證必填欄位
    public function test_returns_422_when_required_fields_are_missing()
    {
        // 故意傳送空資料
        $response = $this->postJson('/api/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'id',
                'name',
                'address.city',
                'address.district',
                'address.street',
                'currency',
                'price',
            ]);
    }

    // 測試是否有正確檢查不支援的貨幣
    public function test_returns_422_when_currency_is_not_supported()
    {
        $payload = [
            'id' => 'A0000002',
            'name' => 'Test Hotel',
            'address' => [
                'city' => 'tokyo',
                'district' => 'shibuya',
                'street' => '1-1'
            ],
            'price' => '1000',
            'currency' => 'GBP',
        ];

        $response = $this->postJson('/api/orders', $payload);
        $response->assertStatus(422);
    }

    // 測試是否冪等性處理重複訂單 ID
    public function test_should_not_allow_duplicate_order_ids()
    {
        $payload = [
            'id' => 'A0000003',
            'name' => 'Unique Hotel',
            'address' => [
                'city' => 'kuala-lumpur',
                'district' => 'bukit-bintang',
                'street' => '2-2'
            ],
            'price' => '1500',
            'currency' => 'TWD',
        ];

        $payloadShouldNotInsert = [
            'id' => 'A0000003',
            'name' => 'Unique Hotel',
            'address' => [
                'city' => 'kuala-lumpur',
                'district' => 'bukit-bintang',
                'street' => '2-2'
            ],
            'price' => '1500',
            'currency' => 'USD',
        ];

        $this->postJson('/api/orders', $payload);
        $this->postJson('/api/orders', $payload);
        $this->postJson('/api/orders', $payloadShouldNotInsert);
        $this->assertDatabaseCount('orders_twd', 1);
        $this->assertDatabaseCount('orders_usd', 0);
    }

    // 測試是否有依照貨幣類型正確塞入不同表
    public function test_insert_to_differnt_tables_based_on_currency()
    {
        foreach (Currency::cases() as $currency) {
            $payload = [
                'id' => 'A' . $this->faker->unique()->numerify('#######'),
                'name' => $this->faker->company(),
                'address' => [
                    'city' => $this->faker->city(),
                    'district' => $this->faker->streetName(),
                    'street' => $this->faker->streetAddress(),
                ],
                'price' => $this->faker->numberBetween(100, 5000),
                'currency' => $currency,
            ];

            $response = $this->postJson('/api/orders', $payload);
            $response->assertStatus(200);

            // 驗證資料是否正確插入對應的資料表
            $tableName = match ($currency->value) {
                'TWD' => 'orders_twd',
                'USD' => 'orders_usd',
                'JPY' => 'orders_jpy',
                'MYR' => 'orders_myr',
                'RMB' => 'orders_rmb',
            };

            $this->assertDatabaseHas($tableName, [
                'id' => $payload['id'],
                'name' => $payload['name'],
                'address_city' => $payload['address']['city'],
                'address_district' => $payload['address']['district'],
                'address_street' => $payload['address']['street'],
                'price' => $payload['price'],
                'currency' => $payload['currency'],
            ]);
        }
    }

    // 測試是否能正確跨不同貨幣表查詢訂單
    public function test_can_query_orders_across_different_currency_tables()
    {
        // 跨表測試資料
        OrderTWD::factory()->create(['id' => 'TWD001', 'currency' => 'TWD']);
        OrderUSD::factory()->create(['id' => 'USD001', 'currency' => 'USD']);

        // 測試查 TWD 表
        $this->getJson('/api/orders/TWD001')
            ->assertStatus(200)
            ->assertJsonPath('data.id', 'TWD001');

        // 測試查 USD 表
        $this->getJson('/api/orders/USD001')
            ->assertStatus(200)
            ->assertJsonPath('data.id', 'USD001');
    }

    // 測試當 ID 不存在於任何表中時
    public function test_returns_404_when_order_not_found()
    {
        $response = $this->getJson('/api/orders/NO_EXIST');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Order not found');
    }

    // 測試當 ID 不存在於任何表中時
    public function test_returns_422_when_order_id_is_invalid()
    {
        $response = $this->getJson('/api/orders/TooLongOrderID123');

        $response->assertStatus(422);
    }
}
