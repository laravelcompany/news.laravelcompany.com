<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Admin User';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::factory()->admin()->create([
            'email' => 'stefan@izdrail.com',
            'password' => Hash::make('secret')
        ]);

        $this->info('Admin user created. Credentials:');

        $this->newLine();

        $this->info('Email: '.$user->email);

        $this->info('Password: password');
    }
}
