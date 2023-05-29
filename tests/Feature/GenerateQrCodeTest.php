<?php

namespace Tests\Feature;

use App\Models\App;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GenerateQrCodeTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test if the function generateQrCode() is working
     * @test
     * @return void
     */
    public function test_genereateQrCode_fill_the_qr_code_column_in_database()
    {
        //create an app
        $app = App::factory()->create();

        //run the function
        $app->generateQrCode();

        //check if the qr code is generated
        $this->assertNotNull($app->qr_code);
        $this->assertNotEmpty($app->qr_code);

        //delete the created file
        unlink(public_path('images/app-qr-codes/' . $app->name . '.svg'));
    }


    /**
     * Test if the function generateQrCode() return a valid svg
     * @test
     * @return void
     */
    public function test_generateQrCode_return_a_valid_svg()
    {
        //create an app
        $app = App::factory()->create();

        //run the function
        $app->generateQrCode();

        //check if the qr code field is a valid svg
        $this->assertStringStartsWith('<?xml', $app->qr_code);
        $this->assertStringContainsString('<svg', $app->qr_code);


        //delete the created file
        unlink(public_path('images/app-qr-codes/' . $app->name . '.svg'));
    }

    /**
     * Test if the function generateQrCode() save the svg in the correct directory
     * @test
     * @return void
     */
    public function test_generateQrCode_create_the_correct_directory()
    {
        //create an app
        $app = App::factory()->create();

        //run the function
        $app->generateQrCode();

        //check if the directory is created
        $this->assertDirectoryExists(public_path('images/app-qr-codes'));

        //check if the svg file is correctly saved
        $this->assertFileExists(public_path('images/app-qr-codes/' . $app->name . '.svg'));

        //delete the created file
        unlink(public_path('images/app-qr-codes/' . $app->name . '.svg'));
    }
}
