<?php

namespace Tests\Feature;

use App\Jobs\DeleteEcTrackElasticIndexJob;
use App\Jobs\TrackPBFJob;
use App\Jobs\UpdateEcTrackElasticIndexJob;
use App\Models\App;
use App\Models\Layer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use ReflectionMethod;
use Tests\TestCase;

class DraftExclusionPbfAndIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_pbf_sql_excludes_draft_tracks()
    {
        $user = User::factory()->create();
        $app = App::factory()->create(['user_id' => $user->id]);
        Layer::factory()->create(['app_id' => $app->id]);

        $job = new TrackPBFJob(
            7, // z: TrackPBFJob viene usato da z > zoomTreshold
            0,
            0,
            $app->id,
            $user->id
        );

        $method = new ReflectionMethod(TrackPBFJob::class, 'generateSQL');
        $method->setAccessible(true);

        $sql = $method->invoke($job, [
            'xmin' => 0,
            'ymin' => 0,
            'xmax' => 1,
            'ymax' => 1,
        ]);

        $this->assertIsString($sql);
        $this->assertStringContainsString('AND ec.draft = false', $sql);
        $this->assertStringContainsString("SELECT ST_AsMVT(mvtgeom.*, 'ec_tracks')", $sql);
    }

    public function test_elastic_index_job_skips_draft_and_dispatches_delete()
    {
        Bus::fake();

        $draftTrack = new class
        {
            public int $id = 123;

            public bool $draft = true;

            public function getLayersByApp(): array
            {
                return [26 => [188, 236]];
            }

            public function elasticIndex($indexName, $layerIds): void
            {
                throw new \Exception('elasticIndex non dovrebbe essere chiamato per draft=true');
            }
        };

        (new UpdateEcTrackElasticIndexJob($draftTrack))->handle();

        Bus::assertDispatched(DeleteEcTrackElasticIndexJob::class, function ($job) {
            $layersProp = new \ReflectionProperty($job, 'ecTrackLayers');
            $layersProp->setAccessible(true);
            $idProp = new \ReflectionProperty($job, 'id');
            $idProp->setAccessible(true);

            return $layersProp->getValue($job) === [26 => [188, 236]]
                && $idProp->getValue($job) === 123;
        });
    }

    public function test_elastic_index_job_indexes_when_not_draft_and_does_not_dispatch_delete()
    {
        Bus::fake();
        config(['services.elastic.prefix' => 'geohub_app']);

        $indexed = ['called' => false, 'calls' => 0, 'indexName' => null, 'layerIds' => null];

        $nonDraftTrack = new class($indexed)
        {
            public int $id = 456;

            public bool $draft = false;

            private $indexed;

            public function __construct(&$indexed)
            {
                $this->indexed = &$indexed;
            }

            public function getLayersByApp(): array
            {
                return [26 => [188, 236]];
            }

            public function elasticIndex($indexName, $layerIds): void
            {
                $this->indexed['called'] = true;
                $this->indexed['calls']++;
                $this->indexed['indexName'] = $indexName;
                $this->indexed['layerIds'] = $layerIds;
            }
        };

        (new UpdateEcTrackElasticIndexJob($nonDraftTrack))->handle();

        Bus::assertNotDispatched(DeleteEcTrackElasticIndexJob::class);
        $this->assertTrue($indexed['called']);
        $this->assertSame(1, $indexed['calls']);
        $this->assertEquals('geohub_app_26', $indexed['indexName']);
        $this->assertEquals([188, 236], $indexed['layerIds']);
    }
}
