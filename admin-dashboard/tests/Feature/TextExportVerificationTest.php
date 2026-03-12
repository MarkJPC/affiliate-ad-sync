<?php

namespace Tests\Feature;

use App\Services\ExportEngineService;
use App\Services\ExportFilterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TextExportVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTextExportSchema();
    }

    public function test_text_export_applies_strict_eligibility_rules(): void
    {
        DB::table('sites')->insert([
            ['id' => 1, 'domain' => 'site-one.test'],
            ['id' => 2, 'domain' => 'site-two.test'],
        ]);

        DB::table('advertisers')->insert([
            ['id' => 10, 'name' => 'Allowed Brand', 'is_active' => 1, 'default_weight' => 3],
            ['id' => 11, 'name' => 'Blocked Brand', 'is_active' => 1, 'default_weight' => 2],
            ['id' => 12, 'name' => 'Inactive Brand', 'is_active' => 0, 'default_weight' => 2],
        ]);

        DB::table('site_advertiser_rules')->insert([
            ['site_id' => 1, 'advertiser_id' => 10, 'rule' => 'allowed'],
            ['site_id' => 1, 'advertiser_id' => 11, 'rule' => 'blocked'],
            ['site_id' => 1, 'advertiser_id' => 12, 'rule' => 'allowed'],
            ['site_id' => 2, 'advertiser_id' => 10, 'rule' => 'allowed'],
        ]);

        DB::table('ads')->insert([
            [
                'id' => 1001,
                'advertiser_id' => 10,
                'network' => 'cj',
                'creative_type' => 'text',
                'status' => 'active',
                'approval_status' => 'approved',
                'tracking_url' => 'https://example.com/a',
                'bannercode' => '<a href="#">Great Deal</a>',
                'weight_override' => null,
            ],
            [
                // wrong creative type
                'id' => 1002,
                'advertiser_id' => 10,
                'network' => 'cj',
                'creative_type' => 'banner',
                'status' => 'active',
                'approval_status' => 'approved',
                'tracking_url' => 'https://example.com/b',
                'bannercode' => '<a href="#">Banner</a>',
                'weight_override' => null,
            ],
            [
                // blocked advertiser for site 1
                'id' => 1003,
                'advertiser_id' => 11,
                'network' => 'impact',
                'creative_type' => 'text',
                'status' => 'active',
                'approval_status' => 'approved',
                'tracking_url' => 'https://example.com/c',
                'bannercode' => '<a href="#">Blocked</a>',
                'weight_override' => null,
            ],
            [
                // inactive advertiser
                'id' => 1004,
                'advertiser_id' => 12,
                'network' => 'awin',
                'creative_type' => 'text',
                'status' => 'active',
                'approval_status' => 'approved',
                'tracking_url' => 'https://example.com/d',
                'bannercode' => '<a href="#">Inactive</a>',
                'weight_override' => null,
            ],
            [
                // not approved
                'id' => 1005,
                'advertiser_id' => 10,
                'network' => 'cj',
                'creative_type' => 'text',
                'status' => 'active',
                'approval_status' => 'approved',
                'tracking_url' => 'https://example.com/e',
                'bannercode' => '<a href="#">Pending</a>',
                'weight_override' => null,
            ],
        ]);

        $contract = ExportFilterService::normalize([
            'site_id' => 1,
            'export_type' => 'text',
        ]);

        $preview = app(ExportEngineService::class)->buildPreview($contract);

        $this->assertSame(1, $preview['summary']['total_rows']);
        $this->assertSame(['cj' => 1], $preview['summary']['grouped_by_network']);
    }

    public function test_text_export_normalizes_rows_and_drops_invalid_links(): void
    {
        DB::table('sites')->insert([
            ['id' => 1, 'domain' => 'site-one.test'],
            ['id' => 2, 'domain' => 'site-two.test'],
            ['id' => 3, 'domain' => 'site-three.test'],
        ]);

        DB::table('advertisers')->insert([
            ['id' => 20, 'name' => '', 'is_active' => 1, 'default_weight' => 0],
        ]);

        DB::table('site_advertiser_rules')->insert([
            ['site_id' => 1, 'advertiser_id' => 20, 'rule' => 'allowed'],
            ['site_id' => 2, 'advertiser_id' => 20, 'rule' => 'allowed'],
            ['site_id' => 2, 'advertiser_id' => 20, 'rule' => 'allowed'],
            ['site_id' => 3, 'advertiser_id' => 20, 'rule' => 'allowed'],
        ]);

        DB::table('ads')->insert([
            [
                'id' => 2001,
                'advertiser_id' => 20,
                'network' => 'CJ',
                'creative_type' => 'text',
                'status' => 'active',
                'approval_status' => 'approved',
                'tracking_url' => 'https://example.com/ok',
                'bannercode' => '<a href="#">   </a>',
                'weight_override' => 0,
            ],
            [
                // empty affiliate link should be dropped
                'id' => 2002,
                'advertiser_id' => 20,
                'network' => 'CJ',
                'creative_type' => 'text',
                'status' => 'active',
                'approval_status' => 'approved',
                'tracking_url' => '',
                'bannercode' => '<a href="#">No Link</a>',
                'weight_override' => 5,
            ],
        ]);

        $contract = ExportFilterService::normalize([
            'site_id' => 1,
            'export_type' => 'text',
        ]);

        $payload = app(ExportEngineService::class)->buildDownloadPayload($contract, 'site-one.test');

        $this->assertCount(1, $payload['rows']);

        $mapped = array_combine($payload['headers'], $payload['rows'][0]);
        $this->assertSame('Advertiser 20', $mapped['advertiser_name']);
        $this->assertSame('Advertiser 20', $mapped['anchor_text']);
        $this->assertSame('https://example.com/ok', $mapped['affiliate_link']);
        $this->assertSame('site-one.test, site-three.test, site-two.test', $mapped['approved_sites']);
        $this->assertSame('cj', $mapped['network']);
        $this->assertSame(2, $mapped['weight']);
    }

    private function createTextExportSchema(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('advertiser_id');
            $table->string('network')->nullable();
            $table->string('creative_type');
            $table->string('status');
            $table->string('approval_status');
            $table->text('tracking_url')->nullable();
            $table->text('bannercode')->nullable();
            $table->integer('weight_override')->nullable();
        });

        Schema::create('advertisers', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('default_weight')->nullable();
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('domain');
        });

        Schema::create('site_advertiser_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('advertiser_id');
            $table->string('rule');
        });
    }
}
