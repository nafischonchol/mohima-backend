<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    use HasFactory;

    protected $table = 'seo_settings';

    protected $fillable = [
        'google_search_console_id',
        'bing_webmaster_id',
        'baidu_webmaster_id',
        'yandex_webmaster_id',
        'robots_txt',
        'sitemap_enabled',
        'robots_meta_content',
        'meta_pixel_id',
        'meta_capi_token',
        'google_analytics_id',
        'tiktok_pixel_id',
    ];

    protected $casts = [
        'sitemap_enabled' => 'boolean',
        'robots_meta_content' => 'array',
    ];
}
