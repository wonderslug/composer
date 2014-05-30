<?php
/**
 * @version   $Id$
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - ${copyright_year} RocketTheme, LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 */

namespace Composer\Repository;


use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\PackageInterface;
use Symfony\Component\Finder\SplFileInfo;

class LinkedProjectsRepository extends ArrayRepository
{
    protected $config;
    protected $package;
    protected $loader;
    protected $path;
    protected $io;

    /**
     * Initializes filesystem repository.
     *
     * @param array $config package definition
     */
    public function __construct(array $repoConfig, IOInterface $io)
    {
        // make sure the path config option is with the project type
        if (!isset($repoConfig['path'])) {
            throw new \UnexpectedValueException('The path option is missing for the Project repository type');
        }
        // check if its a full path or a relative
        $path         = $repoConfig['path'];
        $runningPath  = dirname(Factory::getComposerFile());
        $project_path = null;
        if (realpath($path)) {
            // its a full path use it
            $this->path = $path;
        } elseif (realpath($runningPath . DIRECTORY_SEPARATOR . $path)) {
            // relative path thats there.
            $this->path = $runningPath . DIRECTORY_SEPARATOR . $path;
        } else {
            throw new \UnexpectedValueException('Invalid path given for Project repository: ' . $repoConfig['path']);
        }

        $this->loader = new ArrayLoader();
        $this->io     = $io;
    }

    /**
     * Initializes repository (reads file, or remote address).
     */
    protected function initialize()
    {
        parent::initialize();
        $rdi = new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS);
        $rii = new \RecursiveIteratorIterator($rdi);
        $rrei = new \RegexIterator($rii, '/^.+composer\.json$/i', \RecursiveRegexIterator::GET_MATCH);
        foreach($rrei as $composer_file)
        {
            $file_path= $composer_file[0];
            $dir_path = dirname($file_path);
            $package = $this->getComposerInformation($file_path);
            if (!$package) {
                if ($this->io->isVerbose()) {
                    $this->io->write("File <comment>{$file_path}</comment> doesn't seem to hold a package");
                }
                return;
            }
            if ($this->io->isVerbose()) {
                $template = 'Found package <info>%s</info> (<comment>%s</comment>) in file <info>%s</info>';
                $this->io->write(sprintf($template, $package->getName(), $package->getPrettyVersion(), $file_path));
            }
            $this->addPackage($package);
        }
        return;
    }

    /**
     * @param string $file
     *
     * @return PackageInterface|boolean
     */
    private function getComposerInformation($file)
    {
        $json            = file_get_contents($file);
        $package         = JsonFile::parseJson($json, $file);
        $package['dist'] = array(
            'type'   => 'link',
            'url'    => $file,
            'shasum' => sha1_file($file)
        );
        $package   = $this->loader->load($package);
        return $package;
    }

}