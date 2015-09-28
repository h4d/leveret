<?php

namespace H4D\Leveret\Application;

use H4D\Leveret\Application;
use H4D\Leveret\Exception\FileNotFoundException;
use H4D\Leveret\Exception\FileNotReadableException;
use H4D\Leveret\Http\Headers;

class Config
{
    /**
     * @var string
     */
    protected $environment = Application::ENV_PRODUCTION;
    /**
     * @var string
     */
    protected $applicationPath;
    /**
     * @var string
     */
    protected $viewsPath;
    /**
     * @var string
     */
    protected $errorHandler;
    /**
     * @var string
     */
    protected $defaultContentType = Headers::CONTENT_TYPE_TEXT_HTML;

    /**
     * @param array $data
     */
    protected function __construct(array $data)
    {
        $this->environment = $data['application']['environment'];
        $this->applicationPath = $data['application']['path'];
        $this->errorHandler = $data['application']['errorHandler'];
        $this->viewsPath = $data['views']['path'];
        $this->defaultContentType = $data['application']['defaultContentType'];
    }

    /**
     * @param string $configFile Config file path
     *
     * @return Config
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     */
    public static function load($configFile)
    {
        if (!is_file($configFile))
        {
            throw new FileNotFoundException(sprintf('File "%s" not found.', $configFile));
        }
        if (!is_readable($configFile))
        {
            throw new FileNotReadableException(sprintf('File "%s" is not readable.', $configFile));
        }
        $data = parse_ini_file($configFile, true);
        return new self($data);
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @param string $environment
     *
     * @return $this
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * @return string
     */
    public function getApplicationPath()
    {
        return $this->applicationPath;
    }

    /**
     * @param string $applicationPath
     *
     * @return $this
     */
    public function setApplicationPath($applicationPath)
    {
        $this->applicationPath = $applicationPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getViewsPath()
    {
        return $this->viewsPath;
    }

    /**
     * @param string $viewsPath
     *
     * @return $this
     */
    public function setViewsPath($viewsPath)
    {
        $this->viewsPath = $viewsPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getErrorHandler()
    {
        return $this->errorHandler;
    }

    /**
     * @param string $errorHandler
     *
     * @return $this
     */
    public function setErrorHandler($errorHandler)
    {
        $this->errorHandler = $errorHandler;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultContentType()
    {
        return $this->defaultContentType;
    }

    /**
     * @param string $defaultContentType
     *
     * @return $this
     */
    public function setDefaultContentType($defaultContentType)
    {
        $this->defaultContentType = $defaultContentType;
        return $this;
    }



}