<?php

namespace Accio\App\Commands;

use Accio\App\Services\DummyTheme;

use Illuminate\Console\Command;


class MakeTheme extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:theme';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new theme';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Replacement strings are saved here
     *
     * @var array $dummyReplacements
     */
    private $dummyReplacements = [];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $title = $this->ask('Theme Title');
        $namespace = $this->ask('Theme namespace (same as its directory name)');
        $organisation = $this->ask('Organisation name');
        $authorName = $this->ask('Author name');
        $authorEmail = $this->ask('Author email');
        $authEnabled = $this->choice('Include user account functionality?', ['No', 'Yes'], 0);
        $setAsActive = $this->choice('Do you want to make this theme active after its creation?', ['No', 'Yes'], 0);

        $this->info("Creating theme...");

        $dummyTheme = new DummyTheme(
            [
            'title' => $title,
            'namespace' => $namespace,
            'organisation' => $organisation,
            'authorName' => $authorName,
            'authorEmail' => $authorEmail,
            'auth' => $authEnabled,
            'activate' => $setAsActive,
            ]
        );

        $dummyTheme->make();

        $this->info("Theme created successfully!");
    }

}
