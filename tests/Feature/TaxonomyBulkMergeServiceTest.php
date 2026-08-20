<?php

namespace Tests\Feature;

use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyTheme;
use App\Models\User;
use App\Services\TaxonomyBulkMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaxonomyBulkMergeServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaxonomyBulkMergeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaxonomyBulkMergeService();
    }

    private function createEcTrack(): EcTrack
    {
        return EcTrack::forceCreate([
            'name' => 'Test track',
            'description' => 'Test track description.',
            'user_id' => User::factory()->create()->id,
            'geometry' => DB::raw('SRID=4326;LINESTRING(11 43 0, 12 43 0, 12 44 0, 11 44 0)'),
        ])->refresh();
    }

    public function test_theme_merge_remaps_pivot_and_deletes_duplicates(): void
    {
        $main = TaxonomyTheme::factory()->create(['identifier' => 'main-theme']);
        $dup = TaxonomyTheme::factory()->create(['identifier' => 'dup-theme']);
        $track = $this->createEcTrack();

        DB::table('taxonomy_themeables')->insert([
            'taxonomy_theme_id' => $dup->id,
            'taxonomy_themeable_id' => $track->id,
            'taxonomy_themeable_type' => EcTrack::class,
        ]);

        $this->service->merge(
            collect([$main, $dup]),
            $main->id,
            'taxonomy_themeables',
            'taxonomy_theme_id',
            'taxonomy_themeable_id',
            'taxonomy_themeable_type'
        );

        $this->assertDatabaseMissing('taxonomy_themes', ['id' => $dup->id]);
        $this->assertDatabaseHas('taxonomy_themes', ['id' => $main->id]);
        $this->assertDatabaseHas('taxonomy_themeables', [
            'taxonomy_theme_id' => $main->id,
            'taxonomy_themeable_id' => $track->id,
            'taxonomy_themeable_type' => EcTrack::class,
        ]);
        $this->assertDatabaseMissing('taxonomy_themeables', [
            'taxonomy_theme_id' => $dup->id,
        ]);
    }

    public function test_theme_merge_keeps_main_pivot_on_conflict(): void
    {
        $main = TaxonomyTheme::factory()->create();
        $dup = TaxonomyTheme::factory()->create();
        $track = $this->createEcTrack();

        DB::table('taxonomy_themeables')->insert([
            [
                'taxonomy_theme_id' => $main->id,
                'taxonomy_themeable_id' => $track->id,
                'taxonomy_themeable_type' => EcTrack::class,
            ],
            [
                'taxonomy_theme_id' => $dup->id,
                'taxonomy_themeable_id' => $track->id,
                'taxonomy_themeable_type' => EcTrack::class,
            ],
        ]);

        $this->service->merge(
            collect([$main, $dup]),
            $main->id,
            'taxonomy_themeables',
            'taxonomy_theme_id',
            'taxonomy_themeable_id',
            'taxonomy_themeable_type'
        );

        $rows = DB::table('taxonomy_themeables')
            ->where('taxonomy_themeable_id', $track->id)
            ->where('taxonomy_themeable_type', EcTrack::class)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertEquals($main->id, $rows->first()->taxonomy_theme_id);
    }

    public function test_activity_merge_preserves_main_pivot_durations_on_conflict(): void
    {
        $main = TaxonomyActivity::factory()->create();
        $dup = TaxonomyActivity::factory()->create();
        $track = $this->createEcTrack();

        DB::table('taxonomy_activityables')->insert([
            [
                'taxonomy_activity_id' => $main->id,
                'taxonomy_activityable_id' => $track->id,
                'taxonomy_activityable_type' => EcTrack::class,
                'duration_forward' => 100,
                'duration_backward' => 90,
            ],
            [
                'taxonomy_activity_id' => $dup->id,
                'taxonomy_activityable_id' => $track->id,
                'taxonomy_activityable_type' => EcTrack::class,
                'duration_forward' => 50,
                'duration_backward' => 40,
            ],
        ]);

        $this->service->merge(
            collect([$main, $dup]),
            $main->id,
            'taxonomy_activityables',
            'taxonomy_activity_id',
            'taxonomy_activityable_id',
            'taxonomy_activityable_type'
        );

        $row = DB::table('taxonomy_activityables')
            ->where('taxonomy_activityable_id', $track->id)
            ->where('taxonomy_activity_id', $main->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertEquals(100, $row->duration_forward);
        $this->assertEquals(90, $row->duration_backward);
        $this->assertDatabaseMissing('taxonomy_activities', ['id' => $dup->id]);
    }

    public function test_poi_type_merge_remaps_pivot(): void
    {
        $main = TaxonomyPoiType::factory()->create();
        $dup = TaxonomyPoiType::factory()->create();
        $track = $this->createEcTrack();

        DB::table('taxonomy_poi_typeables')->insert([
            'taxonomy_poi_type_id' => $dup->id,
            'taxonomy_poi_typeable_id' => $track->id,
            'taxonomy_poi_typeable_type' => EcTrack::class,
        ]);

        $this->service->merge(
            collect([$main, $dup]),
            $main->id,
            'taxonomy_poi_typeables',
            'taxonomy_poi_type_id',
            'taxonomy_poi_typeable_id',
            'taxonomy_poi_typeable_type'
        );

        $this->assertDatabaseHas('taxonomy_poi_typeables', [
            'taxonomy_poi_type_id' => $main->id,
            'taxonomy_poi_typeable_id' => $track->id,
        ]);
        $this->assertDatabaseMissing('taxonomy_poi_types', ['id' => $dup->id]);
    }

    public function test_merge_rejects_main_not_in_selection(): void
    {
        $main = TaxonomyTheme::factory()->create();
        $dup = TaxonomyTheme::factory()->create();
        $other = TaxonomyTheme::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->service->merge(
            collect([$main, $dup]),
            $other->id,
            'taxonomy_themeables',
            'taxonomy_theme_id',
            'taxonomy_themeable_id',
            'taxonomy_themeable_type'
        );
    }
}
