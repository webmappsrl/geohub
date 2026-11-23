<?php

namespace Tests\Unit\Traits;

use App\Exceptions\CurlRequestException;
use App\Providers\CurlServiceProvider;
use App\Traits\ImporterAndSyncTrait;
use Exception;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Retry policy required by the overpass-api.de operators (OSM community forum, Aug 2026):
 * wait at least 30s after a failure (we use 40s), and do not keep hitting the server after a 406 or a ban,
 * since "clients that repeatedly run into a 429 or 406 will be more quickly banned".
 */
class CurlRequestOverpassRetryTest extends TestCase
{
    private const URL = 'https://overpass-api.de/api/interpreter?data=test';

    private const CSV = "@id\t@timestamp\n14395346629\t2026-11-21T05:57:23Z\n";

    private function importer()
    {
        return new class
        {
            use ImporterAndSyncTrait;

            public $waits = [];

            protected function waitBeforeOverpassRetry(int $seconds): void
            {
                $this->waits[] = $seconds;
            }
        };
    }

    /** @test */
    public function first_attempt_success_does_not_wait()
    {
        $this->mock(CurlServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('exec')->once()->andReturn(self::CSV);
        });
        $importer = $this->importer();

        $result = $importer->curlRequestOverpass(self::URL, 'node');

        $this->assertSame(['node/14395346629'], array_keys($result));
        $this->assertSame([], $importer->waits);
    }

    /** @test */
    public function too_many_requests_is_retried_after_forty_seconds()
    {
        $this->mock(CurlServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('exec')->twice()->andReturnUsing(
                function () {
                    throw new CurlRequestException('HTTP Error 429', 429);
                },
                function () {
                    return self::CSV;
                }
            );
        });
        $importer = $this->importer();

        $result = $importer->curlRequestOverpass(self::URL, 'node');

        $this->assertCount(1, $result);
        $this->assertSame([40], $importer->waits);
    }

    /** @test */
    public function server_errors_back_off_exponentially_from_forty_seconds()
    {
        $this->mock(CurlServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('exec')->times(3)->andThrow(new CurlRequestException('HTTP Error 504', 504));
        });
        $importer = $this->importer();

        try {
            $importer->curlRequestOverpass(self::URL, 'node');
            $this->fail('Expected an exception after the last attempt');
        } catch (Exception $e) {
            $this->assertStringContainsString('HTTP Error 504', $e->getMessage());
        }

        // 40s and 80s between attempts, then 40s before giving up so the next query of the run is spaced too.
        $this->assertSame([40, 80, 40], $importer->waits);
    }

    /** @test */
    public function not_acceptable_is_not_retried()
    {
        $this->mock(CurlServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('exec')->once()->andThrow(new CurlRequestException('HTTP Error 406', 406));
        });
        $importer = $this->importer();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('HTTP Error 406');

        try {
            $importer->curlRequestOverpass(self::URL, 'node');
        } finally {
            // No retry: only the 40s pause that keeps the next query of the run away from this one.
            $this->assertSame([40], $importer->waits);
        }
    }

    /** @test */
    public function connection_refused_is_not_retried()
    {
        $this->mock(CurlServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('exec')->once()->andThrow(
                new CurlRequestException('cURL Error #7: Failed to connect to overpass-api.de port 443: Connection refused', 0, CURLE_COULDNT_CONNECT)
            );
        });
        $importer = $this->importer();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Connection refused');

        try {
            $importer->curlRequestOverpass(self::URL, 'node');
        } finally {
            $this->assertSame([40], $importer->waits);
        }
    }
}
