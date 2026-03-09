<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExportControllerRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_preview_returns_empty_message_when_no_rows_match(): void
    {
        $user = User::query()->create([
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => 'password',
        ]);

        DB::table('sites')->insert([
            'id' => 1,
            'name' => 'Site One',
            'domain' => 'site-one.test',
            'is_active' => 1,
        ]);

        $response = $this->actingAs($user)->getJson(route('api.export.preview', [
            'site_id' => 1,
            'export_type' => 'text',
        ]));

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('message', 'No rows matched the current filters.');
        $response->assertJsonPath('summary.total_rows', 0);
    }

    public function test_preview_rejects_invalid_dimensions(): void
    {
        $user = User::query()->create([
            'name' => 'Tester',
            'email' => 'validator@example.com',
            'password' => 'password',
        ]);

        DB::table('sites')->insert([
            'id' => 1,
            'name' => 'Site One',
            'domain' => 'site-one.test',
            'is_active' => 1,
        ]);

        $response = $this->actingAs($user)->getJson(route('api.export.preview', [
            'site_id' => 1,
            'export_type' => 'banner',
            'dimensions' => '300-250',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['dimensions']);
    }

    public function test_download_streams_csv_and_logs_export_metadata(): void
    {
        $user = User::query()->create([
            'name' => 'Exporter',
            'email' => 'exporter@example.com',
            'password' => 'password',
        ]);

        DB::table('sites')->insert([
            'id' => 1,
            'name' => 'Site One',
            'domain' => 'site-one.test',
            'is_active' => 1,
        ]);

        DB::table('placements')->insert([
            'site_id' => 1,
            'width' => 300,
            'height' => 250,
            'is_active' => 1,
        ]);

        DB::table('v_exportable_ads')->insert([
            'ad_id' => 9001,
            'advertiser_id' => 44,
            'advertiser_name' => 'Acme Co',
            'network' => 'cj',
            'site_id' => 1,
            'site_domain' => 'site-one.test',
            'advert_name' => 'Acme 300x250',
            'bannercode' => '<a href="#">Acme</a>',
            'imagetype' => 'html',
            'image_url' => '',
            'width' => 300,
            'height' => 250,
            'final_weight' => 3,
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
        ]);

        $response = $this->actingAs($user)->post(route('export.download'), [
            'site_id' => 1,
            'export_type' => 'banner',
        ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('x-export-row-count', '1');
        $response->assertHeader('x-export-empty', '0');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('advert_name,bannercode,imagetype,image_url,width,height', $csv);
        $this->assertStringContainsString('Acme 300x250', $csv);

        $this->assertDatabaseHas('export_logs', [
            'site_id' => 1,
            'ads_exported' => 1,
            'exported_by' => 'exporter@example.com',
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamp('last_ad_review_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('domain');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->string('filename');
            $table->unsignedInteger('ads_exported');
            $table->dateTime('exported_at');
            $table->string('exported_by')->nullable();
        });

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

        Schema::create('site_advertiser_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('advertiser_id');
            $table->string('rule');
        });
    }
}
