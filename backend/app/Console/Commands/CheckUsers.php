<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckUsers extends Command
{
    protected $signature = 'check:users';
    protected $description = 'Check users table for name column data';

    public function handle()
    {
        $users = User::all();

        $this->info('=== Users in Database ===');
        $this->line('');

        if ($users->isEmpty()) {
            $this->warn('No users found in database');
            return;
        }

        foreach ($users as $user) {
            $this->line("ID: {$user->id}");
            $this->line("  firebase_uid: {$user->firebase_uid}");
            $this->line("  name: " . ($user->name ?? '(null)'));
            $this->line("  email: " . ($user->email ?? '(null)'));
            $this->line("  created_at: {$user->created_at}");
            $this->line('---');
        }

        $this->info('Total users: ' . $users->count());
    }
}
