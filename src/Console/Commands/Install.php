<?php

namespace Flamix\App24Core\Console\Commands;

use Bitrix24\User\User;
use Flamix\App24Core\Models\Portals;
use Illuminate\Console\Command;
use Flamix\App24Core\App24;

class Install extends Command
{
    protected $signature = 'app24:install';
    protected $description = 'Installing the application...';

    public function handle()
    {
        $this->info($this->description);
        // Settings
        $this->call('vendor:publish', [
            '--provider' => 'Flamix\Settings\ServiceProvider'
        ]);
        // App24 Core
        $this->call('vendor:publish', [
            '--provider' => 'Flamix\App24Core\App24ServiceProvider'
        ]);

        $this->info('Finish! Please, run "php artisan migrate" and add env settings for your app24!');
    }
}