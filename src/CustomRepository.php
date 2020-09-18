<?php

/**
 * Copyright (c) 2020 Carl Kittelberger <icedream@icedream.pw>
 * 
 * This software is released under the MIT License.
 * https://opensource.org/licenses/MIT
 */

namespace Icedream\Composer\Custom;

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Repository\PathRepository;
use Composer\Util\Filesystem;

class CustomRepository extends PathRepository
{
  /**
   * @var string
   */
  private $url;

  /**
   * @var ArrayLoader
   */
  private $loader;

  public function __construct(array $repoConfig, IOInterface $io, Config $config)
  {
    parent::__construct($repoConfig, $io, $config);

    $this->url = $repoConfig['url'];
    $this->loader = new ArrayLoader(null, true);
    $this->options = isset($repoConfig['options']) ? $repoConfig['options'] : array();
    if (!isset($this->options['relative'])) {
      $filesystem = new Filesystem();
      $this->options['relative'] = !$filesystem->isAbsolutePath($this->url);
    }
  }

  protected function initialize()
  {
    parent::initialize();

    echo self::class . "::initialize" . PHP_EOL;

    $urlMatches = $this->getUrlMatches();

    if (empty($urlMatches)) {
      if (preg_match('{[*{}]}', $this->url)) {
        $url = $this->url;
        while (preg_match('{[*{}]}', $url)) {
          $url = dirname($url);
        }
        // the parent directory before any wildcard exists, so we assume it is correctly configured but simply empty
        if (is_dir($url)) {
          return;
        }
      }

      throw new \RuntimeException('The `url` supplied for the path (' . $this->url . ') repository does not exist');
    }

    foreach ($urlMatches as $url) {
      $path = realpath($url) . DIRECTORY_SEPARATOR;
      $copyThisFolderPath = $path . 'copy_this';
      if (file_exists($copyThisFolderPath) && is_dir($copyThisFolderPath)) {
        $metadataFilePaths = glob($path . '**' . DIRECTORY_SEPARATOR . 'metadata.php');
      } else {
        $metadataFilePaths = [$path . 'metadata.php'];
      }

      foreach ($metadataFilePaths as $metadataFilePath) {
        if (!file_exists($metadataFilePath)) {
          continue;
        }

        $module = new OxidPackage($path);
        $package = $module->generateComposerConfig();

        $package['dist'] = array(
          'type' => 'path',
          'url' => $url,
          'reference' => sha1(json_encode($package) . serialize($this->options)),
        );

        $package = $this->loader->load($package);
        $this->addPackage($package);
      }
    }
  }

  /**
   * Get a list of all (possibly relative) path names matching given url (supports globbing).
   *
   * @return string[]
   */
  private function getUrlMatches()
  {
    $flags = GLOB_MARK | GLOB_ONLYDIR;

    if (defined('GLOB_BRACE')) {
      $flags |= GLOB_BRACE;
    } elseif (strpos($this->url, '{') !== false || strpos($this->url, '}') !== false) {
      throw new \RuntimeException('The operating system does not support GLOB_BRACE which is required for the url ' . $this->url);
    }

    // Ensure environment-specific path separators are normalized to URL separators
    return array_map(function ($val) {
      return rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $val), '/');
    }, glob($this->url, $flags));
  }
}
