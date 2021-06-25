<?php

namespace Tests\Unit\Commands;

use App\Console\Commands\ImportAndSync;
use Tests\TestCase;

class ImportImagesList extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testImportNoZipFile()
    {
        $file = base_path() . '/tests/Fixtures/test.jpg';
        $cmd = new ImportImagesList();
        $pathinfoFile = pathinfo($file);
        $this->assertEquals('jpg', $pathinfoFile['extension']);
    }

    public function testImportZipFile()
    {
        $file = base_path() . '/tests/Fixtures/test.zip';
        $cmd = new ImportImagesList();
        $pathinfoFile = pathinfo($file);
        $this->assertEquals('zip', $pathinfoFile['extension']);
    }
}
