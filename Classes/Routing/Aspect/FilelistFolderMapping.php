<?php

declare(strict_types=1);

namespace Causal\FileList\Routing\Aspect;

use TYPO3\CMS\Core\Routing\Aspect\AspectTrait;
use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;

/**
 * Example:
 *   routeEnhancers:
 *     FileListPlugin:
 *       type: Extbase
 *       extension: FileList
 *       plugin: Filelist
 *       routes:
 *         - routePath: '/directory{path}'
 *         _controller: 'File::list'
 *         _arguments:
 *         path: path
 *       defaultController: 'File::list'
 *       defaults:
 *         path: '/fileadmin/downloads'
 *       requirements:
 *         path: '[a-zA-Z0-9_\-\/].*'
 *       aspects:
 *         path:
 *           type: FilelistFolderMapper
 */
class FilelistFolderMapper implements StaticMappableAspectInterface
{
    use AspectTrait;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @throws \InvalidArgumentException
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function generate(string $value): string
    {
        return $value;
    }

    public function resolve(string $value): string
    {
        return $value;
    }
}
