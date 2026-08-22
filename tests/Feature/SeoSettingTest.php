<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoSettingTest extends TestCase
{
    use RefreshDatabase;

    private function authenticateAdmin()
    {
        $adminUser = Admin::create([
            'name' => 'John Admin',
            'email' => 'john@admin.com',
            'password' => bcrypt('password'),
            'phone' => '1234567891',
            'is_active' => true,
        ]);

        $this->actingAs($adminUser, 'sanctum');

        return $adminUser;
    }

    public function test_can_retrieve_default_seo_settings()
    {
        $this->authenticateAdmin();

        $response = $this->getJson('/admin/seo-settings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('resources.google_search_console_id', null)
            ->assertJsonPath('resources.yandex_webmaster_id', null)
            ->assertJsonPath('resources.meta_pixel_id', null)
            ->assertJsonPath('resources.meta_capi_token', null)
            ->assertJsonPath('resources.google_analytics_id', null)
            ->assertJsonPath('resources.tiktok_pixel_id', null)
            ->assertJsonPath('resources.robots_txt', "User-agent: *\nDisallow: /admin/")
            ->assertJsonPath('resources.sitemap_enabled', false);
    }

    public function test_can_update_seo_settings()
    {
        $this->authenticateAdmin();

        $response = $this->postJson('/admin/seo-settings', [
            'google_search_console_id' => 'google-key-123',
            'bing_webmaster_id' => 'bing-key-456',
            'baidu_webmaster_id' => 'baidu-key-789',
            'yandex_webmaster_id' => 'yandex-key-abc',
            'robots_txt' => 'Disallow: /temp/',
            'sitemap_enabled' => true,
            'robots_meta_content' => [
                'noindex' => true,
                'nofollow' => false,
                'noarchive' => false,
                'nosnippet' => false,
                'noimageindex' => false,
            ],
            'meta_pixel_id' => 'pixel-123',
            'meta_capi_token' => 'capi-token-456',
            'google_analytics_id' => 'ga-789',
            'tiktok_pixel_id' => 'tiktok-abc',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('resources.google_search_console_id', 'google-key-123')
            ->assertJsonPath('resources.yandex_webmaster_id', 'yandex-key-abc')
            ->assertJsonPath('resources.meta_pixel_id', 'pixel-123')
            ->assertJsonPath('resources.meta_capi_token', 'capi-token-456')
            ->assertJsonPath('resources.google_analytics_id', 'ga-789')
            ->assertJsonPath('resources.tiktok_pixel_id', 'tiktok-abc')
            ->assertJsonPath('resources.sitemap_enabled', true)
            ->assertJsonPath('resources.robots_meta_content.noindex', true);

        $this->assertDatabaseHas('seo_settings', [
            'google_search_console_id' => 'google-key-123',
            'yandex_webmaster_id' => 'yandex-key-abc',
            'meta_pixel_id' => 'pixel-123',
            'meta_capi_token' => 'capi-token-456',
            'google_analytics_id' => 'ga-789',
            'tiktok_pixel_id' => 'tiktok-abc',
            'sitemap_enabled' => true,
        ]);
    }

    public function test_can_retrieve_404_logs_and_auto_populates()
    {
        $this->authenticateAdmin();

        $response = $this->getJson('/admin/seo-settings/404-logs');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertGreaterThan(0, count($response->json('resources')));
        $this->assertDatabaseHas('seo_404_logs', [
            'path' => '/wp-admin/',
        ]);
    }

    public function test_can_clear_404_logs()
    {
        $adminUser = $this->authenticateAdmin();

        // Ensure logs are seeded/created first
        $this->getJson('/admin/seo-settings/404-logs');
        $this->assertDatabaseHas('seo_404_logs', ['path' => '/wp-admin/']);

        $response = $this->deleteJson('/admin/seo-settings/404-logs');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('seo_404_logs', ['path' => '/wp-admin/']);
    }
}
