<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestEnv extends Command
{
    protected $signature = 'test:env';

    protected $description = 'Test environment variables';

    public function handle()
    {
        $this->line('FIREBASE_PROJECT: ' . (env('FIREBASE_PROJECT') ?? 'NOT SET'));
        $this->line('FIREBASE_PROJECT_ID: ' . (env('FIREBASE_PROJECT_ID') ?? 'NOT SET'));
        $this->line('FIREBASE_CREDENTIALS: ' . (env('FIREBASE_CREDENTIALS') ?? 'NOT SET'));
        $this->line('FIREBASE_DATABASE_URL: ' . (env('FIREBASE_DATABASE_URL') ?? 'NOT SET'));

        $this->line("\nFrom config:");
        $this->line('firebase.default: ' . config('firebase.default'));
        $this->line('firebase.projects.app.project_id: ' . config('firebase.projects.app.project_id'));
        $this->line('firebase.projects.app.credentials: ' . config('firebase.projects.app.credentials'));
    }
}
