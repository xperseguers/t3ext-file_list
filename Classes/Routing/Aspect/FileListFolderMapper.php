<?php
declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
 *           _controller: 'File::list'
 *           _arguments:
 *             path: path
 *       defaultController: 'File::list'
 *       requirements:
 *         path: '[a-zA-Z0-9_\-\/].*'
 *       aspects:
 *         path:
 *           type: FileListFolderMapper
 */
class FileListFolderMapper implements StaticMappableAspectInterface
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
