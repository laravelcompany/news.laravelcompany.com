<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Publisher;
use App\Models\Source;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => "Stefan Bogdan",
            'email' => 'stefan@laravelcompany.com',
            'password' => Hash::make('secret'),
            'avatar_url' => 'https://avatars.githubusercontent.com/u/2276408?v=4',
        ]);

    }
}
