<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestAuth extends Command
{
    protected $signature = 'test:auth';

    protected $description = 'Test Firebase Auth';

    public function handle()
    {
        try {
            $auth = app('firebase.auth');
            $this->line('Firebase Auth resolved successfully: ' . get_class($auth));
        } catch (\Exception $e) {
            $this->line('Error resolving firebase.auth: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
        }
    }
}
