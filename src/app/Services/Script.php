<?php

namespace Accio\App\Services;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;

/**
 * Class which loads a shell script template and parses any variables.
 */
class Script
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var mixed
     */
    private $script;

    /**
     * File path
     *
     * @var
     */
    private $filePath;

    /**
     * Parser constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Parse a string to replace the tokens.
     *
     * @param string $script
     * @param array  $tokens
     *
     * @return $this
     */
    public function parseString($script, array $tokens = [])
    {
        $values = array_values($tokens);

        $tokens = array_map(
            function ($token) {
                return '{{' . strtolower($token) . '}}';
            }, array_keys($tokens)
        );


        $this->script = str_replace($tokens, $values, $script);
        return $this;
    }

    /**
     * Load a file and parse the the content.
     *
     * @param string $file
     * @param array  $tokens
     *
     * @return $this
     */
    public function parseFile($file, array $tokens = [])
    {
        $this->filePath = $file;
        $template = accioPath('resources/scripts/' . str_replace('.', '/', $this->filePath) . '.sh');

        if ($this->filesystem->exists($template)) {
            $this->parseString($this->filesystem->get($template), $tokens);
            return $this;
        }

        throw new RuntimeException('Template ' . $template . ' does not exist');
    }

    /**
     * Run command
     *
     * @param  Command $command
     * @return bool
     */
    public function run(Command $command)
    {
        if($this->script) {
            $process = new Process($command);
            $process->setCommandLine($this->script);
            $process->setTimeout(null);
            $process->run();

            if (!$process->isSuccessful()) {
                $command->error($process->getErrorOutput());
            } else {
                $command->info($process->getOutput());
                return true;
            }
        }

        return false;
    }
}
