<?php

/**
 * Copyright (c) 2020 Carl Kittelberger <icedream@icedream.pw>
 * 
 * This software is released under the MIT License.
 * https://opensource.org/licenses/MIT
 */

namespace Icedream\Composer\Custom;

use Jawira\CaseConverter\Convert;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDaoInterface;

class OxidPackage
{
  const metadataFileName = 'metadata.php';

  /**
   * @var string
   */
  protected $basePath;

  /**
   * @var ModuleConfigurationDaoInterface
   */
  private $moduleConfigurationDao;

  public function __construct(string $basePath)
  {
    $this->basePath = $basePath;

    // $container = BootstrapContainerFactory::getBootstrapContainer();
    $container = ContainerFactory::getInstance()->getContainer();
    $this->moduleConfigurationDao = $container->get(ModuleConfigurationDaoInterface::class);
  }

  protected function getMetadataFilePath(): string
  {
    return $this->basePath . DIRECTORY_SEPARATOR . self::metadataFileName;
  }

  public function getMetadata(): ModuleConfiguration
  {
    $normalizedMetadata = $this->moduleConfigurationDao->get($this->basePath);
    return $normalizedMetadata;
  }

  private static function convertModuleIdToComposerPackageName(string $id): string
  {
    $vendor = 'oxid-modules';
    $id = $id;
    if (strpos($id, '/') > 0) {
      list($vendor, $id) = explode('/', $id, 2);
    }
    $id = (new Convert($id))->toKebab();
    return "${vendor}/${id}";
  }

  public function generateComposerConfig(): array
  {
    $metadata = $this->getMetadata();
    $package = [
      'author' => $metadata->getAuthor(),
      'description' => $metadata->getDescription(),
      'name' => self::convertModuleIdToComposerPackageName($metadata->getId()),
      'type' => 'oxideshop-module',
      'homepage' => $metadata->getUrl(),
      'version' => $metadata->getVersion(),
    ];
    if (!empty($metadata->getAuthor())) {
      $package['authors'] = [
        $metadata->getAuthor(),
      ];
    }
    if (!empty($metadata->getEmail())) {
      $package['support'] = [
        'email' => $metadata->getEmail(),
      ];
    }
    $package['extra'] = [
      'oxideshop' => [
        'target-directory' => $metadata->getId(),
      ]
    ];
    return $package;
  }
}
