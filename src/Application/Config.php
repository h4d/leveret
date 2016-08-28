<?php

namespace H4D\Leveret\Application;

use H4D\Leveret\Application;
use H4D\Leveret\Http\Headers;
use Retrinko\Ini\IniFile;

class Config extends IniFile
{

    const SECTION_APPLICATION = 'application';
    const SECTION_VIEWS       = 'views';
    const SECTION_ROUTES      = 'routes';

    /**
     * @var string
     */
    protected $iniFilePath;
    /**
     * @var array
     */
    protected $parsedConfigFileRoutes;

    /**
     * Config constructor.
     *
     * @param null|string $file
     */
    public function __construct($file)
    {
        parent::__construct($file);
        $this->iniFilePath = $file;
    }

    /**
     * @param string $file
     *
     * @return Config
     */
    public static function load($file)
    {
        return new self($file);
    }

    /**
     * @return string
     */
    public function getConfigFilePath()
    {
        return $this->iniFilePath;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->get(self::SECTION_APPLICATION, 'environment', Application::ENV_DEVELOPMENT);
    }
    /**
     * @return string
     */
    public function getApplicationPath()
    {
        return $this->get(self::SECTION_APPLICATION, 'path', './');
    }

    /**
     * @return string
     */
    public function getApplicationName()
    {
        return $this->get(self::SECTION_APPLICATION, 'name', 'UnNamedApp');
    }

    /**
     * @return string
     */
    public function getViewsPath()
    {
        return $this->get(self::SECTION_VIEWS, 'path', './');
    }

    /**
     * @return string
     */
    public function getErrorHandler()
    {
        return $this->get(self::SECTION_APPLICATION, 'errorHandler', 'errorHandler');
    }

    /**
     * @return string
     */
    public function getDefaultContentType()
    {
        return $this->get(self::SECTION_APPLICATION, 'defaultContentType',
                          Headers::CONTENT_TYPE_TEXT_HTML);
    }

    /**
     * @return int
     */
    public function getDefaultInputFilterType()
    {
        return $this->get(self::SECTION_APPLICATION, 'defaultInputFilterType', FILTER_UNSAFE_RAW);
    }

    /**
     * @return boolean
     */
    public function getRegisterRoutesDefinedInConfigFile()
    {
        return $this->get(self::SECTION_APPLICATION, 'registerRoutesDefinedInConfigFile', false);
    }

    /**
     * @return string
     */
    public function getMaintenanceTemplate()
    {
        return $this->get(self::SECTION_VIEWS, 'maintenanceTemplate', '');
    }

    /**
     * @return array
     */
    protected function parseConfigFileRoutes()
    {
        $parsedRoutes = [];
        if ($this->hasSection(self::SECTION_ROUTES))
        {
            $routes = $this->getSection(self::SECTION_ROUTES)->toArray();
            foreach($routes as $name=>$data)
            {
                $parsedRoutes[$name] = Application\Config\Route::create($data);
            }
        }

        return $parsedRoutes;
    }

    /**
     * @return Application\Config\Route[]
     */
    public function getConfigFileRoutes()
    {
        if (is_null($this->parsedConfigFileRoutes))
        {
            $this->parsedConfigFileRoutes = $this->parseConfigFileRoutes();
        }

        return $this->parsedConfigFileRoutes;
    }

}