<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_b2b_client_can_register()
    {
        $payload = [
            'contact_name' => 'Jane Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'phone' => '01700000001',
            'password' => 'secret123',
            'company_name' => 'Jane Corp',
            'country' => 'BD',
            'company_address' => '123 Business St',
            'position' => 'Manager',
            'business_type' => 'retail',
            'hear_about_us' => 'social',
            'interested_categories' => ['Electronics'],
            'business_introduction' => 'We sell electronics',
            'nda_agreed' => true,
        ];

        $response = $this->postJson('/customer/register', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('resources.username', 'janedoe')
            ->assertJsonPath('resources.status', 'pending');

        $this->assertDatabaseHas('clients', [
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('client_details', [
            'company_name' => 'Jane Corp',
            'contact_name' => 'Jane Doe',
        ]);
    }

    public function test_approved_client_can_login()
    {
        $client = Client::create([
            'name' => 'Approved Customer',
            'username' => 'approveduser',
            'email' => 'approved@example.com',
            'phone' => '01700000002',
            'password' => Hash::make('password123'),
            'address' => 'Test Address',
            'status' => 'approved',
            'is_active' => true,
        ]);

        $response = $this->postJson('/customer/login', [
            'login' => 'approveduser',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'resources' => [
                    'token',
                    'client' => ['id', 'name', 'email', 'username', 'phone', 'address', 'status'],
                ],
            ]);
    }

    public function test_pending_client_cannot_login()
    {
        Client::create([
            'name' => 'Pending Customer',
            'username' => 'pendinguser',
            'email' => 'pending@example.com',
            'phone' => '01700000003',
            'password' => Hash::make('password123'),
            'address' => 'Test Address',
            'status' => 'pending',
            'is_active' => false,
        ]);

        $response = $this->postJson('/customer/login', [
            'login' => 'pendinguser',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
