<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OutSourceImporterListStorageCSVTest extends TestCase
{
    /**
     * @test
     */
    public function when_access_file_esercizi_csv_return_200()
    {
        $path = Storage::disk('local')->path('/importer/parco-maremma/esercizi.csv');
        $this->assertFileExists($path,"given filename doesn't exists");
    }
}
