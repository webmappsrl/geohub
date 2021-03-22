<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory()->create([
            'name' => 'Webmapp Team',
            'email' => 'team@webmapp.it',
            'email_verified_at' => now(),
            'password' => bcrypt('webmapp')
        ]);
    }
}
