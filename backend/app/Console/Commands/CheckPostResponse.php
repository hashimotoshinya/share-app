<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Post;

class CheckPostResponse extends Command
{
    protected $signature = 'check:post-response';
    protected $description = 'Check what post API returns';

    public function handle()
    {
        $post = Post::with('user:id,name')->first();

        if (!$post) {
            $this->warn('No posts found');
            return;
        }

        $this->info('=== Post Data Structure ===');
        $this->line(json_encode($post->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info('=== User relationship ===');
        $this->line('User object: ' . json_encode($post->user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info('=== Direct access ===');
        $this->line('post.user->name = ' . ($post->user?->name ?? 'NULL'));
        $this->line('post.user->id = ' . ($post->user?->id ?? 'NULL'));
    }
}
