<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 17/05/2018
 * Time: 1:11 PM
 */

namespace Accio\App\Services;


use Accio\App\Models\SettingsModel;
use Cz\Git\GitRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class DummyTheme
{

    /**
     * @var
     */
    private $tmpDirectory;

    /**
     * @var
     */
    private $destinationDir;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var string
     */
    private $gitURL = 'https://github.com/AccioCMS/default-theme.git';

    /**
     * DummyTheme constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Make theme.
     *
     * @return boolean
     * @throws \Cz\Git\GitException
     * @throws \Exception
     */
    public function make()
    {
        $this->setDummyReplacements()
            ->cloneFromGit()
            ->moveTheme()
            ->authFunctionality()
            ->replaceDummyContent()
            ->activate();

        return true;
    }

    /**
     * Set dummy replacements.
     *
     * @return $this
     */
    private function setDummyReplacements()
    {
        $this->dummyReplacements = [
          'DefaultTitle' => $this->getAttribute('title'),
          'DefaultTheme' => $this->getAttribute('namespace'),
          'DefaultOrganisation' => $this->getAttribute('organisation'),
          'DefaultAuthor' => $this->getAttribute('authorName'),
          'DefaultEmail' => $this->getAttribute('authorEmail'),
        ];
        return $this;
    }

    /**
     * Deletes auth controller and view if theme should not include auth functionalities.
     *
     * @return $this
     */
    private function authFunctionality()
    {
        if($this->getAttribute('activate')) {
            File::deleteDirectory($this->destinationDir.'/controllers/Auth');
            File::deleteDirectory($this->destinationDir.'/views/auth');
        }
        return $this;
    }
    /**
     * Get attribute
     *
     * @param  $key
     * @return string
     */
    private function getAttribute($key)
    {
        return (isset($this->attributes[$key]) && $this->attributes[$key]) ? $this->attributes[$key] : '';
    }

    /**
     * CLone form git.
     *
     * @throws \Cz\Git\GitException
     * @return $this;
     */
    private function cloneFromGit()
    {
        $this->tmpDirectory = tmpPath().'/'.time();
        GitRepository::cloneRepository($this->gitURL, $this->tmpDirectory);
        return $this;
    }

    /**
     * Move theme to themes directory
     *
     * @return $this
     * @throws \Exception
     */
    public function moveTheme()
    {
        $this->destinationDir = base_path('themes') . '/' . $this->getAttribute('namespace');

        // remove hidden directories
        File::deleteDirectory($this->tmpDirectory.'/.git');
        File::deleteDirectory($this->tmpDirectory.'/.idea');

        $success = File::copyDirectory($this->tmpDirectory, $this->destinationDir);
        if (!$success) {
            throw new \Exception("Theme could not be moved to " . $this->destinationDir . ". Check you have all permissions needed!");
        }else{
            File::deleteDirectory($this->tmpDirectory);
        }

        return $this;
    }

    /**
     * Replace dummy strings
     *
     * @return $this
     * @throws \Exception
     */
    private function replaceDummyContent()
    {
        // Replace DummyTheme string in Controllers
        $this->replaceDummy($this->destinationDir . '/controllers');

        // Replace DummyTheme string in views
        $this->replaceDummy($this->destinationDir . '/views');

        // Replace DummyTheme string in config
        $this->replaceDummyInFile($this->destinationDir.'/config.json');

        return $this;
    }

    /**
     * Activate theme.
     *
     * @return $this
     */
    private function activate()
    {
        if($this->getAttribute('activate')) {
            SettingsModel::setSetting('activeTheme', $this->getAttribute('namespace'));
        }
        return $this;
    }
    /**
     * Replace dummy strings in a directory's files
     *
     * @param $directory
     *
     * @return $this
     */
    private function replaceDummy($directory)
    {
        $files = File::allFiles($directory);
        foreach ($files as $file) {
            $this->replaceDummyInFile($file->getPathName());
        }

        return $this;
    }

    /**
     * Replace dummy content in a file
     *
     * @param $filePath
     */
    private function replaceDummyInFile($filePath)
    {
        $fileSource = File::get($filePath);
        $fileContent = str_replace(array_keys($this->dummyReplacements), array_values($this->dummyReplacements), $fileSource);
        $writeFile = File::put($filePath, $fileContent);
        if ($writeFile === false) {
            throw new \Exception("Error writing to file " . $filePath);
        }
    }
}