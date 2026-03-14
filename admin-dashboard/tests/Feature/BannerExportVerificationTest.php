<?php

namespace Tests\Feature;

use App\Services\ExportEngineService;
use App\Services\ExportFilterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BannerExportVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createExportSchema();
    }

    public function test_banner_export_is_restricted_to_active_placement_sizes(): void
    {
        DB::table('placements')->insert([
            ['site_id' => 1, 'width' => 300, 'height' => 250, 'is_active' => 1],
            ['site_id' => 1, 'width' => 728, 'height' => 90, 'is_active' => 0],
        ]);

        DB::table('v_exportable_ads')->insert([
            [
                'ad_id' => 1001,
                'advertiser_id' => 10,
                'advertiser_name' => 'Acme',
                'network' => 'cj',
                'site_id' => 1,
                'site_domain' => 'example.com',
                'advert_name' => 'Acme 300x250',
                'bannercode' => '<a href="#">Banner</a>',
                'imagetype' => 'html',
                'image_url' => '',
                'width' => 300,
                'height' => 250,
                'final_weight' => 2,
                'enable_stats' => 'Y',
                'show_everyone' => 'Y',
                'show_desktop' => 'Y',
                'show_mobile' => 'Y',
                'show_tablet' => 'Y',
                'show_ios' => 'Y',
                'show_android' => 'Y',
                'autodelete' => 'Y',
                'autodisable' => 'N',
                'budget' => 0,
                'click_rate' => 0,
                'impression_rate' => 0,
                'state_required' => 'N',
                'geo_cities' => 'a:0:{}',
                'geo_states' => 'a:0:{}',
                'geo_countries' => 'a:0:{}',
                'schedule_start' => 0,
                'schedule_end' => 2650941780,
            ],
            [
                'ad_id' => 1002,
                'advertiser_id' => 10,
                'advertiser_name' => 'Acme',
                'network' => 'cj',
                'site_id' => 1,
                'site_domain' => 'example.com',
                'advert_name' => 'Acme 728x90',
                'bannercode' => '<a href="#">Banner 2</a>',
                'imagetype' => 'html',
                'image_url' => '',
                'width' => 728,
                'height' => 90,
                'final_weight' => 2,
                'enable_stats' => 'Y',
                'show_everyone' => 'Y',
                'show_desktop' => 'Y',
                'show_mobile' => 'Y',
                'show_tablet' => 'Y',
                'show_ios' => 'Y',
                'show_android' => 'Y',
                'autodelete' => 'Y',
                'autodisable' => 'N',
                'budget' => 0,
                'click_rate' => 0,
                'impression_rate' => 0,
                'state_required' => 'N',
                'geo_cities' => 'a:0:{}',
                'geo_states' => 'a:0:{}',
                'geo_countries' => 'a:0:{}',
                'schedule_start' => 0,
                'schedule_end' => 2650941780,
            ],
        ]);

        $contract = ExportFilterService::normalize([
            'site_id' => 1,
            'export_type' => 'banner',
            'active_sizes_only' => false,
        ]);

        $preview = app(ExportEngineService::class)->buildPreview($contract);

        $this->assertSame(1, $preview['summary']['total_rows']);
        $this->assertSame(['300x250' => 1], $preview['summary']['grouped_by_dimensions']);
    }

    public function test_banner_export_normalizes_values_and_skips_invalid_rows(): void
    {
        DB::table('placements')->insert([
            ['site_id' => 1, 'width' => 300, 'height' => 250, 'is_active' => 1],
        ]);

        DB::table('v_exportable_ads')->insert([
            [
                'ad_id' => 2001,
                'advertiser_id' => 12,
                'advertiser_name' => 'Bravo',
                'network' => 'cj',
                'site_id' => 1,
                'site_domain' => 'example.com',
                'advert_name' => '',
                'bannercode' => '<a href="#">Valid HTML</a>',
                'imagetype' => '',
                'image_url' => '',
                'width' => 300,
                'height' => 250,
                'final_weight' => 0,
                'enable_stats' => 'invalid',
                'show_everyone' => 'Y',
                'show_desktop' => 'Y',
                'show_mobile' => 'Y',
                'show_tablet' => 'Y',
                'show_ios' => 'Y',
                'show_android' => 'Y',
                'autodelete' => '',
                'autodisable' => '',
                'budget' => 'abc',
                'click_rate' => null,
                'impression_rate' => null,
                'state_required' => '',
                'geo_cities' => '',
                'geo_states' => '',
                'geo_countries' => '',
                'schedule_start' => 100,
                'schedule_end' => 90,
            ],
            [
                // invalid dimensions -> dropped
                'ad_id' => 2002,
                'advertiser_id' => 12,
                'advertiser_name' => 'Bravo',
                'network' => 'cj',
                'site_id' => 1,
                'site_domain' => 'example.com',
                'advert_name' => 'Invalid Dimension',
                'bannercode' => '<a href="#">x</a>',
                'imagetype' => 'html',
                'image_url' => '',
                'width' => 0,
                'height' => 250,
                'final_weight' => 2,
                'enable_stats' => 'Y',
                'show_everyone' => 'Y',
                'show_desktop' => 'Y',
                'show_mobile' => 'Y',
                'show_tablet' => 'Y',
                'show_ios' => 'Y',
                'show_android' => 'Y',
                'autodelete' => 'Y',
                'autodisable' => 'N',
                'budget' => 0,
                'click_rate' => 0,
                'impression_rate' => 0,
                'state_required' => 'N',
                'geo_cities' => 'a:0:{}',
                'geo_states' => 'a:0:{}',
                'geo_countries' => 'a:0:{}',
                'schedule_start' => 0,
                'schedule_end' => 2650941780,
            ],
            [
                // empty creative payload -> dropped
                'ad_id' => 2003,
                'advertiser_id' => 12,
                'advertiser_name' => 'Bravo',
                'network' => 'cj',
                'site_id' => 1,
                'site_domain' => 'example.com',
                'advert_name' => 'No Creative',
                'bannercode' => '',
                'imagetype' => '',
                'image_url' => '',
                'width' => 300,
                'height' => 250,
                'final_weight' => 2,
                'enable_stats' => 'Y',
                'show_everyone' => 'Y',
                'show_desktop' => 'Y',
                'show_mobile' => 'Y',
                'show_tablet' => 'Y',
                'show_ios' => 'Y',
                'show_android' => 'Y',
                'autodelete' => 'Y',
                'autodisable' => 'N',
                'budget' => 0,
                'click_rate' => 0,
                'impression_rate' => 0,
                'state_required' => 'N',
                'geo_cities' => 'a:0:{}',
                'geo_states' => 'a:0:{}',
                'geo_countries' => 'a:0:{}',
                'schedule_start' => 0,
                'schedule_end' => 2650941780,
            ],
        ]);

        $contract = ExportFilterService::normalize([
            'site_id' => 1,
            'export_type' => 'banner',
        ]);

        $payload = app(ExportEngineService::class)->buildDownloadPayload($contract, 'example.com');

        $this->assertCount(1, $payload['rows']);

        $mapped = array_combine($payload['headers'], $payload['rows'][0]);
        $this->assertSame('Ad 2001', $mapped['advert_name']);
        $this->assertSame('', $mapped['imagetype']);
        $this->assertSame(2, $mapped['weight']);
        $this->assertSame('N', $mapped['enable_stats']);
        $this->assertSame('Y', $mapped['autodelete']);
        $this->assertSame('N', $mapped['autodisable']);
        $this->assertSame(0.0, $mapped['budget']);
        $this->assertSame(0.0, $mapped['click_rate']);
        $this->assertSame(0.0, $mapped['impression_rate']);
        $this->assertSame('N', $mapped['state_required']);
        $this->assertSame('a:0:{}', $mapped['geo_cities']);
        $this->assertSame('a:0:{}', $mapped['geo_states']);
        $this->assertSame('a:0:{}', $mapped['geo_countries']);
        $this->assertSame(100, $mapped['schedule_start']);
        $this->assertSame(2650941780, $mapped['schedule_end']);
    }

    public function test_banner_export_uses_dropdown_asset_mode_when_image_url_present(): void
    {
        DB::table('placements')->insert([
            ['site_id' => 1, 'width' => 300, 'height' => 250, 'is_active' => 1],
        ]);

        DB::table('v_exportable_ads')->insert([
            [
                'ad_id' => 3001,
                'advertiser_id' => 15,
                'advertiser_name' => 'Delta',
                'network' => 'flexoffers',
                'site_id' => 1,
                'site_domain' => 'example.com',
                'advert_name' => 'Delta 300x250',
                'bannercode' => '<a href="http://track.example.com/click"><img src="http://cdn.example.com/banner.jpg" /></a>',
                'imagetype' => '',
                'image_url' => 'http://cdn.example.com/banner.jpg',
                'width' => 300,
                'height' => 250,
                'final_weight' => 5,
                'enable_stats' => 'Y',
                'show_everyone' => 'Y',
                'show_desktop' => 'Y',
                'show_mobile' => 'Y',
                'show_tablet' => 'Y',
                'show_ios' => 'Y',
                'show_android' => 'Y',
                'autodelete' => 'Y',
                'autodisable' => 'N',
                'budget' => 0,
                'click_rate' => 0,
                'impression_rate' => 0,
                'state_required' => 'N',
                'geo_cities' => 'a:0:{}',
                'geo_states' => 'a:0:{}',
                'geo_countries' => 'a:0:{}',
                'schedule_start' => 0,
                'schedule_end' => 2650941780,
            ],
        ]);

        $contract = ExportFilterService::normalize([
            'site_id' => 1,
            'export_type' => 'banner',
        ]);

        $payload = app(ExportEngineService::class)->buildDownloadPayload($contract, 'example.com');

        $this->assertCount(1, $payload['rows']);

        $mapped = array_combine($payload['headers'], $payload['rows'][0]);

        // image_url present → dropdown asset mode
        $this->assertSame('dropdown', $mapped['imagetype']);
        $this->assertSame('http://cdn.example.com/banner.jpg', $mapped['image_url']);

        // bannercode should have %asset% instead of raw image URL in src
        $this->assertStringContainsString('src=&quot;%asset%&quot;', $mapped['bannercode']);
        $this->assertStringNotContainsString('src=&quot;http://cdn.example.com/banner.jpg&quot;', $mapped['bannercode']);
    }

    private function createExportSchema(): void
    {
        Schema::create('placements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->integer('width');
            $table->integer('height');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('v_exportable_ads', function (Blueprint $table) {
            $table->unsignedBigInteger('ad_id');
            $table->unsignedBigInteger('advertiser_id')->nullable();
            $table->string('advertiser_name')->nullable();
            $table->string('network')->nullable();
            $table->unsignedBigInteger('site_id');
            $table->string('site_domain')->nullable();
            $table->string('advert_name')->nullable();
            $table->text('bannercode')->nullable();
            $table->string('imagetype')->nullable();
            $table->text('image_url')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('final_weight')->nullable();
            $table->string('enable_stats')->nullable();
            $table->string('show_everyone')->nullable();
            $table->string('show_desktop')->nullable();
            $table->string('show_mobile')->nullable();
            $table->string('show_tablet')->nullable();
            $table->string('show_ios')->nullable();
            $table->string('show_android')->nullable();
            $table->string('autodelete')->nullable();
            $table->string('autodisable')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->decimal('click_rate', 12, 4)->nullable();
            $table->decimal('impression_rate', 12, 4)->nullable();
            $table->string('state_required')->nullable();
            $table->text('geo_cities')->nullable();
            $table->text('geo_states')->nullable();
            $table->text('geo_countries')->nullable();
            $table->unsignedBigInteger('schedule_start')->nullable();
            $table->unsignedBigInteger('schedule_end')->nullable();
        });
    }
}
