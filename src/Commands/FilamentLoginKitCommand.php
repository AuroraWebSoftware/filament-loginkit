<?php

namespace AuroraWebSoftware\FilamentLoginKit\Commands;

use Illuminate\Console\Command;

class FilamentLoginKitCommand extends Command
{
    protected $signature = 'filament-loginkit:install';
    protected $description = 'Install and set up Filament Loginkit.';

    public function handle()
    {
        $this->info('Filament Loginkit Installation Wizard');
        $this->line('----------------------------------------');

        $this->call('vendor:publish', [
            '--provider' => "AuroraWebSoftware\FilamentLoginKit\FilamentLoginKitServiceProvider",
            '--tag' => 'filament-loginkit-config',
        ]);
        $this->info('Config file published.');

        $this->call('vendor:publish', [
            '--provider' => "AuroraWebSoftware\FilamentLoginKit\FilamentLoginKitServiceProvider",
            '--tag' => 'filament-loginkit-migrations',
        ]);
        $this->info('Migrations published.');

        $this->call('vendor:publish', [
            '--provider' => "AuroraWebSoftware\FilamentLoginKit\FilamentLoginKitServiceProvider",
            '--tag' => 'filament-loginkit-assets',
        ]);
        $this->info('Assets published.');

        $this->call('fortify:install');
        $this->info('Fortify install command executed.');

        $this->call('filament-phone-input:install');
        $this->info('filament-phone-input installed.');

        $this->call('filament:assets');
        $this->info('Filament assets built.');

        if ($this->confirm('Run the migrations now?', true)) {
            $this->call('migrate');
            $this->info('Migrations executed.');
        }

        $this->line('----------------------------------------');
        $this->info('Filament Loginkit installation completed!');
        $this->line('You may now start using Filament Loginkit in your project.');
    }
}
