<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_place_order_using_cart_ids_and_carts_are_deleted()
    {
        $client = Client::create([
            'name' => 'John Customer',
            'contact_name' => 'John Customer',
            'username' => 'johncustomer',
            'email' => 'john@customer.com',
            'phone' => '01711000000',
            'password' => bcrypt('password'),
            'company_name' => 'Customer Corp',
            'status' => 'approved',
        ]);

        $category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU-01',
            'price' => 500.00,
            'purchase_price' => 300.00,
            'stock' => 10,
        ]);

        $cart = Cart::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $payload = [
            'name' => 'John Customer',
            'phone' => '01711000000',
            'address' => 'Dhaka, Bangladesh',
            'cart_ids' => [$cart->id],
            'payment_method' => 'cod',
        ];

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/customer/me/orders', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('resources.grand_total', 1000);

        // Verify stock is decremented
        $this->assertEquals(8, $variant->fresh()->stock);

        // Verify cart items are deleted after order placement
        $this->assertDatabaseMissing('carts', [
            'id' => $cart->id,
        ]);
    }
}
