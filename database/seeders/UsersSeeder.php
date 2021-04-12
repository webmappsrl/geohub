<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder {
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run() {
        User::factory()->create([
            'name' => 'Webmapp Team',
            'email' => 'team@webmapp.it',
            'email_verified_at' => now(),
            'password' => bcrypt('webmapp')
        ]);
        User::factory()->create([
            'name' => 'Alessio Piccioli',
            'email' => 'alessiopiccioli@webmapp.it',
            'email_verified_at' => now(),
            'password' => bcrypt('webmapp')
        ]);
        User::factory()->create([
            'name' => 'Andrea Del Sarto',
            'email' => 'andreadel84@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('webmapp')
        ]);
        User::factory()->create([
            'name' => 'Antonella Puglia',
            'email' => 'antonellapuglia@webmapp.it',
            'email_verified_at' => now(),
            'password' => bcrypt('webmapp')
        ]);
        User::factory()->create([
            'name' => 'Davide Pizzato',
            'email' => 'davidepizzato@webmapp.it',
            'email_verified_at' => now(),
            'password' => bcrypt('webmapp')
        ]);
        User::factory()->create([
            'name' => 'Marco Barbieri',
            'email' => 'marcobarbieri@webmapp.it',
            'email_verified_at' => now(),
            'password' => bcrypt('webmapp')
        ]);
        User::factory()->create([
            'name' => 'Pedram Katanchi',
            'email' => 'pedramkatanchi@webmapp.it',
            'email_verified_at' => now(),
            'password' => bcrypt('webmapp')
        ]);
        User::factory()->create([
            'name' => 'Laura Roth',
            'email' => 'lauraroth72@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('')
        ]);
    }
}
