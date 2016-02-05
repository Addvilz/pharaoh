<?php

namespace Addvilz\Pharaoh;

use Symfony\Component\Finder\Finder;

class Builder
{
    /**
     * @var string
     */
    private $pharName;

    /**
     * @var string
     */
    private $destinationPath;

    /**
     * @var string
     */
    private $rootPath;

    /**
     * @var Finder
     */
    private $finders;

    /**
     * @var string[]
     */
    private $files;

    /**
     * @param $pharName
     * @param $destinationPath
     * @param $rootPath
     */
    public function __construct($pharName, $destinationPath, $rootPath)
    {
        if (empty($pharName)) {
            throw new \InvalidArgumentException('Argument $pharName can not be empty');
        }

        if (empty($destinationPath)) {
            throw new \InvalidArgumentException('Argument $destinationPath can not be empty');
        }

        if (empty($rootPath)) {
            throw new \InvalidArgumentException('Argument $rootPath can not be empty');
        }

        $this->pharName = $pharName;
        $this->destinationPath = $destinationPath;
        $this->rootPath = $rootPath;
    }

    /**
     * @param Finder $finder
     * @return $this
     */
    public function addFinder(Finder $finder)
    {
        $this->finders[] = $finder;
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function addFile($path)
    {
        if (empty($path)) {
            throw new \InvalidArgumentException('Argument $path can not be empty');
        }
        $this->files[] = $path;
        return $this;
    }

    /**
     * @param string $indexFilePath
     * @param \Phar|null $phar
     * @return $this
     */
    public function build($indexFilePath, \Phar $phar = null)
    {
        if (null === $phar) {
            $phar = $this->createDefaultPhar();
        }
        $phar->startBuffering();

        foreach ($this->finders as $finder) {
            foreach ($finder as $file) {
                $this->addFileToPhar($file, $phar);
            }
        }

        foreach ($this->files as $file) {
            $this->addFileToPhar($file, $phar);
        }

        $this->addIndex($phar, $indexFilePath);

        $this->addStub($phar);
        $phar->stopBuffering();
        $phar->compressFiles(\Phar::GZ);

        return $this;
    }

    /**
     * @param $filePath
     * @param \Phar $phar
     */
    private function addFileToPhar($filePath, \Phar $phar)
    {

        $path = str_replace(
            rtrim($this->rootPath, '/') . DIRECTORY_SEPARATOR,
            '',
            (new \SplFileInfo($filePath))->getRealPath()
        );

        $phar->addFile($filePath, $path);
    }

    /**
     * @return \Phar
     */
    private function createDefaultPhar()
    {
        $phar = new \Phar(
            sprintf('%s/%s', $this->destinationPath, $this->pharName),
            0,
            $this->pharName
        );

        $phar->setSignatureAlgorithm(\Phar::SHA256);

        return $phar;
    }

    /**
     * @param \Phar $phar
     */
    private function addStub(\Phar $phar)
    {
        $phar->setStub(<<<EOF
#!/usr/bin/env php
<?php
Phar::mapPhar('$this->pharName');
require 'phar://$this->pharName/__app_index';
__HALT_COMPILER();
EOF
        );
    }

    /**
     * @param \Phar $phar
     * @param string $indexFilePath
     */
    private function addIndex(\Phar $phar, $indexFilePath)
    {
        $content = file_get_contents($indexFilePath);
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('__app_index', $content);
    }
}