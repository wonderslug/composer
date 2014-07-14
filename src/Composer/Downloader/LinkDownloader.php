<?php
 /**
  * @version   $Id$
  * @author    RocketTheme http://www.rockettheme.com
  * @copyright Copyright (C) 2007 - ${copyright_year} RocketTheme, LLC
  * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
  */

namespace Composer\Downloader;

 
use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;
use Gantry\Core\Platform\Playground\Cache;

class LinkDownloader implements DownloaderInterface {

    protected $io;
    protected  $config;
    protected $eventDispatcher;
    protected $cache;
    protected $rfs;
    protected $filesystem;

    protected $outputProgress;
    /**
     * Constructor.
     *
     * @param IOInterface      $io              The IO instance
     * @param Config           $config          The config
     * @param EventDispatcher  $eventDispatcher The event dispatcher
     * @param Cache            $cache           Optional cache instance
     * @param RemoteFilesystem $rfs             The remote filesystem
     * @param Filesystem       $filesystem      The filesystem
     */
    public function __construct(IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null, Cache $cache = null, RemoteFilesystem $rfs = null, Filesystem $filesystem = null)
    {
        $this->io = $io;
        $this->config = $config;
        $this->eventDispatcher = $eventDispatcher;
        $this->rfs = $rfs ?: new RemoteFilesystem($io, $config);
        $this->filesystem = $filesystem ?: new Filesystem();
        $this->cache = $cache;
    }

    /**
     * Returns installation source (either source or dist).
     *
     * @return string "source" or "dist"
     */
    public function getInstallationSource()
    {
        return "dist";
;    }

    /**
     * Downloads specific package into specific folder.
     *
     * @param PackageInterface $package package instance
     * @param string           $path    download path
     */
    public function download(PackageInterface $package, $path)
    {
        if (file_exists($path) || is_dir($path))
        {
            rmdir($path);
        }
        symlink(dirname(realpath($package->getDistUrl())),$path);
        if ($this->io->isVerbose())
        {
            $template = 'Linked project <info>%s</info> (<comment>%s</comment>) to path <info>%s</info>';
            $this->io->write(sprintf($template, $package->getName(), $package->getDistUrl(), $path));
        }
    }

    /**
     * Updates specific package in specific folder from initial to target version.
     *
     * @param PackageInterface $initial initial package
     * @param PackageInterface $target  updated package
     * @param string           $path    download path
     */
    public function update(PackageInterface $initial, PackageInterface $target, $path)
    {
        return;
    }

    /**
     * Removes specific package from specific folder.
     *
     * @param PackageInterface $package package instance
     * @param string           $path    download path
     */
    public function remove(PackageInterface $package, $path)
    {
        if (is_link($path)) {
            unlink($path);
        }
        else
        {
            throw new \RuntimeException('Could not completely delete '.$path.', not a symlink and is a registered linked project.');
        }
    }

    /**
     * Sets whether to output download progress information or not
     *
     * @param  bool $outputProgress
     *
     * @return DownloaderInterface
     */
    public function setOutputProgress($outputProgress)
    {
        $this->outputProgress = $outputProgress;

        return $this;
    }
}
 