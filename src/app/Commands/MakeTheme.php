<?php

namespace Accio\App\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Accio\App\Models\SettingsModel;

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
        $authEnabled = $this->choice('Include user account functionality?', ['Yes', 'No'], 1);
        $setAsActive = $this->choice('Do you want to make this theme active after its creation?', ['Yes', 'No'], 0);

        $sourceDir = stubPath('DummyTheme', false);
        $destinationDir = base_path('themes') . '/' . $namespace;

        $this->comment("Creating theme...");
        $success = File::copyDirectory($sourceDir, $destinationDir);

        if (!$success) {
            $this->error("Theme could not be moved to " . $destinationDir . ". Check you have all permissions needed!");
        }

        $this->dummyReplacements = [
            'DummyTitle' => $title,
            'DummyNamespace' => $namespace,
            'DummyOrganisation' => $organisation,
            'DummyAuthor' => $authorName,
            'DummyEmail' => $authorEmail,
        ];

        // Remove Auth if not needed in this theme
        if($authEnabled == 'No'){
            File::deleteDirectory($destinationDir.'/controllers/Auth');
            File::deleteDirectory($destinationDir.'/views/auth');
        }

        // Replace DummyTheme string in Controllers
        $this->replaceDummy($destinationDir . '/controllers');

        // Replace DummyTheme string in views
        $this->replaceDummy($destinationDir . '/views');

        // Replace DummyTheme string in config
        $this->replaceDummyInFile($destinationDir.'/config.json');

        $this->info("Theme created successfully!");

        // Set as theme as active
        if($setAsActive == 'Yes'){
            $this->comment("Activating theme...");
            SettingsModel::setSetting('activeTheme', $namespace);
            $this->info('Theme activated');
        }

    }

    /**
     * Replace dummy strings in a directory's files
     *
     * @param $directory
     *
     * @return $this
     */
    private function replaceDummy($directory){
        $files = File::allFiles($directory);
        foreach ($files as $file) {
            $this->replaceDummyInFile($file->getPathName());
        }

        return $this;
    }

    /**
     * Replace dummy content in a file
     * @param $filePath
     */
    private function replaceDummyInFile($filePath){
        $fileSource = File::get($filePath);
        $fileContent = str_replace(array_keys($this->dummyReplacements),array_values($this->dummyReplacements), $fileSource);
        $writeFile = File::put($filePath, $fileContent);
        if ($writeFile === false) {
            die("Error writing to file " . $filePath);
        }
    }
}
