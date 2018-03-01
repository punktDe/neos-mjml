<?php
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
use Neos\Flow\Exception;
use Neos\Flow\Package\PackageManagerInterface;
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
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @param string $mjmlSource
     * @return string
     * @throws Exception
     * @throws InvalidConfigurationException
     */
    public function compile(string $mjmlSource): string
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = proc_open($this->getMjmlBinaryPath() . ' -i -s', $descriptorspec, $pipes);

        if (!is_resource($process)) {
            throw new Exception('Could not open the mjml proccess', 1519111810);
        }

        fwrite($pipes[0], $mjmlSource);
        fclose($pipes[0]);

        $compiledHtml = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $status = proc_close($process);

        if($status !== 0) {
            $errorMessage = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            throw new Exception(sprintf('The mjml process exited with an error %s and message "%s": ' , $status, $errorMessage), 1519112326);
        }

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
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
