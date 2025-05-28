<?php

namespace AuroraWebSoftware\FilamentLoginKit\Commands;

use Illuminate\Console\Command;

class FilamentLoginKitCommand extends Command
{
    public $signature = 'skeleton';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
