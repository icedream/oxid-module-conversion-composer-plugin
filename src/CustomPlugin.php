<?php

/**
 * Copyright (c) 2020 Carl Kittelberger <icedream@icedream.pw>
 * 
 * This software is released under the MIT License.
 * https://opensource.org/licenses/MIT
 */

namespace Icedream\Composer\Custom;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class CustomPlugin implements PluginInterface
{
  /**
   * @var string
   */
  public static $oldPathRepositoryClass;

  public function activate(Composer $composer, IOInterface $io)
  {
    $installer = new CustomInstaller($io, $composer);
    // $composer->getInstallationManager()->addInstaller($installer);
    $composer->getRepositoryManager()->setRepositoryClass('oxid-package', CustomRepository::class);
    // $newRepositories = [];
    // var_dump($composer->getConfig());
    $extra = $composer->getPackage()->getExtra();
    if (!empty($extra['oxid-package-paths'])) {
      $io->write("<info>extra.oxid-package-paths</info>");
      $oxidPackagePaths = $extra['oxid-package-paths'];
      if (!is_array($oxidPackagePaths)) {
        throw new \UnexpectedValueException('"extra.oxid-package-paths" must be an array of paths');
      }
      foreach ($oxidPackagePaths as $path) {
        $io->write(
          "<info>Registering oxid-package repo $path</info>"
        );
        // $newRepositories[] = [
        //   'type' => 'oxid-package',
        //   'url' => $path,
        // ];
        $composer->getRepositoryManager()->addRepository(
          $composer->getRepositoryManager()->createRepository('oxid-package', ['url' => $path])
        );
      }
    }
    // $composer->getConfig()->merge([
    //   'repositories' => $newRepositories,
    // ]);
  }

  public function deactivate(Composer $composer, IOInterface $io)
  {
  }

  public function uninstall(Composer $composer, IOInterface $io)
  {
  }
}
