<?php
declare(strict_types=1);

namespace PunktDe\Neos\Mjml;

/*
 * This file is part of the PunktDe.Eel.Mjml package.
 *
 * This package is open source software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Exception;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Files;
use Neos\Flow\Annotations as Flow;

class MjmlHelper implements ProtectedContextAwareInterface
{

    /**
     * @var string
     * @Flow\InjectConfiguration(path="mjmlBinPath")
     */
    protected $mjmlBinPath;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;


    /**
     * @param string $mjmlSource
     * @return string
     * @throws Exception
     * @throws InvalidConfigurationException
     * @throws \Neos\Utility\Exception\FilesException
     */
    public function compile(string $mjmlSource): string
    {
        $htmlFile = $this->createTemporaryFile('html');
        $mjmlFile = $this->createTemporaryFile('mjml');

        file_put_contents($mjmlFile, $mjmlSource);

        shell_exec(escapeshellarg($this->getMjmlBinaryPath()) . ' -r ' . escapeshellarg($mjmlFile) . ' -o ' . escapeshellarg($htmlFile));

        $compiledHtml = file_get_contents($htmlFile);

        Files::unlink($htmlFile);
        Files::unlink($mjmlFile);

        return $compiledHtml;
    }

    /**
     * @throws InvalidConfigurationException
     * @throws Exception
     * @return string
     */
    protected function getMjmlBinaryPath(): string
    {
        $packageResourcePath = $this->packageManager->getPackage('PunktDe.Neos.Mjml')->getResourcesPath();

        $mjmlBinPath = str_replace('{PACKAGERESOURCEROOT}', $packageResourcePath, $this->mjmlBinPath);

        if (!is_file($mjmlBinPath)) {
            throw new InvalidConfigurationException(sprintf('The mjml binary in the configured path "%s" was not found', $mjmlBinPath), 1519111452);
        }

        if (!is_executable($mjmlBinPath)) {
            throw new Exception(sprintf('The mjml binary in the configured path "%s" is not executable', $mjmlBinPath), 1519111547);
        }

        return $mjmlBinPath;
    }


    /**
     * @param $prefix
     * @return bool|string
     * @throws \Neos\Utility\Exception\FilesException
     */
    protected function createTemporaryFile($prefix)
    {
        $items = [FLOW_PATH_TEMPORARY_BASE, $this->bootstrap->getContext(), 'Mjml'];
        $path = Files::concatenatePaths($items);

        Files::createDirectoryRecursively($path);

        $temporaryFileName = tempnam($path, $prefix);

        return $temporaryFileName;
    }

    /**
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
