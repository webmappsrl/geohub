<?php

namespace Tests\Feature\Api\App;

use App\Models\App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppElbrusImagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function check_that_if_app_is_not_defined_the_request_return_404()
    {
        $result = $this->get(route("api.app.elbrus.icon", ['id' => 0]));
        $this->assertEquals(404, $result->getStatusCode());
    }

    /**
     * @test
     */
    public function check_that_if_icon_is_not_defined_the_request_return_404()
    {
        $user = User::factory()->create();
        $app = App::factory()->create([
            'user_id' => $user->id,
        ]);
        $result = $this->get(route("api.app.elbrus.icon", ['id' => $app->id]));
        $this->assertEquals(404, $result->getStatusCode());
    }

    /**
     * @test
     */
    public function check_that_if_icon_is_defined_the_request_return_200()
    {

        $stub = base_path() . '/tests/Feature/Stubs/1024x1024.png';
        $path = sys_get_temp_dir() . '/icon.png';

        copy($stub, $path);

        $image = new UploadedFile($path, 'icon.png', 'image/png', null, true);
        Storage::disk('public')->put('api/app/elbrus/0/resources/icon.png', $image->getContent());

        $user = User::factory()->create();
        $app = App::factory()->create([
            'user_id' => $user->id,
            'icon' => $image,
        ]);

        $this->assertFileExists(Storage::disk('public')->path('api/app/elbrus/0/resources/icon.png'));
        $this->get(route("api.app.elbrus.icon", ['id' => $app->id]));

        Storage::disk('public')->delete('api/app/elbrus/0/resources/icon.png');
        $this->assertFileDoesNotExist(Storage::disk('public')->path('api/app/elbrus/0/resources/icon.png'));
    }
}
