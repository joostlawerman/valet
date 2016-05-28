<?php

namespace Valet;

use Exception;
use DomainException;
use Symfony\Component\Process\Process;

class PhpFpm
{
    public $brew, $cli, $files;

    public $taps = [
        'homebrew/dupes', 'homebrew/versions', 'homebrew/homebrew-php'
    ];

    public $configLocations = [
        'php70' => '/usr/local/etc/php/7.0/php-fpm.d/www.conf',
        'php56' => '/usr/local/etc/php/5.6/php-fpm.conf',
        'php55' => '/usr/local/etc/php/5.6/php-fpm.conf'
    ];

    /**
     * Create a new PHP FPM class instance.
     *
     * @param  Brew  $brew
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    public function __construct(Brew $brew, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->brew = $brew;
        $this->files = $files;
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    public function install()
    {
        if ($this->brew->hasInstalledPhp()) {
            $this->brew->ensureInstalled('php70', $this->taps);
        }

        $this->files->ensureDirExists('/usr/local/var/log', user());

        $this->updateConfiguration();

        $this->restart();
    }

    /**
     * Update the PHP FPM configuration to use the current user.
     *
     * @return void
     */
    public function updateConfiguration()
    {
        $contents = $this->files->get($this->fpmConfigPath());

        $contents = preg_replace('/^user = .+$/m', 'user = '.user(), $contents);
        $contents = preg_replace('/^group = .+$/m', 'group = staff', $contents);

        $this->files->put($this->fpmConfigPath(), $contents);
    }

    /**
     * Restart the PHP FPM process.
     *
     * @return void
     */
    public function restart()
    {
        $this->stop();

        $this->brew->restartLinkedPhp();
    }

    /**
     * Stop the PHP FPM process.
     *
     * @return void
     */
    public function stop()
    {
        $this->brew->stopPhp();
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    public function fpmConfigPath()
    {
        $linkedPhp = $this->brew->linkedPhp();
        
        if (isset($this->configLocations[$linkedPhp])) {
            return $this->configLocations[$linkedPhp];
        }

        throw new DomainException('Unable to find php-fpm config.');
    }
}
