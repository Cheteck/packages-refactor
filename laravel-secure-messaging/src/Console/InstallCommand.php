<?php

namespace Acme\SecureMessaging\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messaging:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Secure Messaging package resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Installing Secure Messaging Package...');

        $this->comment('Publishing configuration...');
        $this->callSilent('vendor:publish', [
            '--provider' => "Acme\SecureMessaging\SecureMessagingServiceProvider",
            '--tag' => "messaging-config",
            // '--force' => $this->option('force'), // Add if you want a --force option
        ]);
        $this->info('Configuration published.');

        $this->comment('Publishing migrations...');
        $this->callSilent('vendor:publish', [
            '--provider' => "Acme\SecureMessaging\SecureMessagingServiceProvider",
            '--tag' => "messaging-migrations",
            // '--force' => $this->option('force'),
        ]);
        $this->info('Migrations published.');

        if ($this->confirm('Would you like to run the migrations now?', true)) {
            $this->comment('Running migrations...');
            $this->call('migrate');
            $this->info('Migrations executed.');
        }

        $this->comment('Publishing broadcast channels file...');
        $this->callSilent('vendor:publish', [
            '--provider' => "Acme\SecureMessaging\SecureMessagingServiceProvider",
            '--tag' => "messaging-channels",
             // '--force' => $this->option('force'),
        ]);
        $this->info('Broadcast channels file published to routes/channels_secure_messaging.php.');
        $this->line('Please ensure you include this file in your App\\Providers\\BroadcastServiceProvider boot method.');
        $this->line("Example: require base_path('routes/channels_secure_messaging.php');");


        $this->info('Secure Messaging package installed successfully.');
        $this->warn('Please configure your .env file, user model, and broadcasting driver as per the documentation.');
    }
}
