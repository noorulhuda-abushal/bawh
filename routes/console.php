<?php

use App\Support\Ai;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * 🚀 Used when the website starts on Render (docker/start.sh):
 * - if the database is empty, fill it with the demo content
 * - if ADMIN_EMAIL and ADMIN_PASSWORD are set, make sure that admin account exists
 */
Artisan::command('bawh:setup', function () {
    if (App\Models\User::count() === 0) {
        $this->call('db:seed', ['--class' => 'BawhDemoSeeder', '--force' => true]);
    }

    $email = env('ADMIN_EMAIL');
    $password = env('ADMIN_PASSWORD');

    if ($email && $password) {
        $admin = App\Models\User::firstOrCreate(
            ['email' => $email],
            ['name' => env('ADMIN_NAME', 'Admin'), 'password' => $password]
        );
        $admin->is_admin = true;
        $admin->save();
        $this->info('🛡️ Admin ready: ' . $email);
    }
})->purpose('Prepare Bawh when it starts on the internet');

/*
 * 🛡️ Make a user admin: php artisan bawh:admin email@example.com
 */
Artisan::command('bawh:admin {email}', function (string $email) {
    $user = App\Models\User::where('email', $email)->first();

    if (! $user) {
        $this->error('✗ No user with this email: ' . $email);

        return;
    }

    $user->is_admin = true;
    $user->save();
    $this->info('🛡️ ' . $user->name . ' is now an admin. Open /admin');
})->purpose('Make a user an admin');

/*
 * ✨ Check if the AI works: php artisan ai:check
 * It shows which AI is used, which model, and the AI's answer (or the exact error).
 * It never prints the key itself.
 */
Artisan::command('ai:check', function () {
    $ai = Ai::provider();

    if (! $ai) {
        $this->error('✗ No AI key in the .env file. Add GROQ_API_KEY=gsk_... then run: php artisan config:clear');

        return;
    }

    $this->info('✓ AI: ' . $ai['name'] . ' (key starts with: ' . substr($ai['key'], 0, 4) . '…)');
    $this->info('✓ Model: ' . $ai['model']);
    $this->line('… asking ' . $ai['name']);

    try {
        $response = Ai::send('Reply in a few words only.', 'Say: Bawh AI works', 30);
    } catch (Throwable $e) {
        $this->error('✗ Could not reach ' . $ai['name'] . ': ' . $e->getMessage());

        return;
    }

    if ($response->successful()) {
        $this->info('🎉 AI works! ' . $ai['name'] . ' said: ' . Ai::textOf($response));
    } else {
        $this->error('✗ ' . $ai['name'] . ' answered with error ' . $response->status());
        $this->line($response->body());
    }
})->purpose('Check if the AI works');
