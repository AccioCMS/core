<?php
/**
 * This library write values to env file
 */

namespace Accio\App\Services;

use App\Models\Language;
use App\Models\User;
use App\Models\UserGroup;
use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\Driver\PDOException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Larapack\ConfigWriter\Repository;
use Accio\PackageServiceProvider;
use Mockery\Exception;

class Environment
{
    /**
     * Write env file configuration
     *
     * @param array $properties
     * @return bool
     */
    public function setEnv($properties){
        $envPath = app()->environmentFilePath();

        $contents = File::get($envPath);
        if(!$contents){
            throw new \Exception('Could not read env file');
        }

        foreach($properties as $key => $value){
            // Env value must be set so the already loaded file reflect changes
            $this->setEnvValue($key, $value);

            // Change content
            $contents = preg_replace('/' . $key . '=(.*)/', $key . '=' . $value, $contents);
        }

        if (File::put($envPath, $contents) === false)
        {
            throw new \Exception('Could not write env file');
        }

        return true;
    }

    /**
     * Set an environment variable (at runtime).
     *
     * This is done using:
     * - putenv,
     * - $_ENV,
     * - $_SERVER.
     *
     * The environment variable value is stripped of single and double quotes.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return void
     */
    private function setEnvValue($name, $value){
        // If PHP is running as an Apache module and an existing
        // Apache environment variable exists, overwrite it
        if (function_exists('apache_getenv') && function_exists('apache_setenv') && apache_getenv($name)) {
            apache_setenv($name, $value);
        }

        if (function_exists('putenv')) {
            putenv("$name=$value");
        }

        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}