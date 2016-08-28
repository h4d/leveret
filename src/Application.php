<?php

namespace H4D\Leveret;

use H4D\Leveret\Application\AclInterface;
use H4D\Leveret\Application\Acls;
use H4D\Leveret\Application\Config;
use H4D\Leveret\Application\Controller;
use H4D\Leveret\Application\Route;
use H4D\Leveret\Application\Router;
use H4D\Leveret\Application\ServiceContainerInterface;
use H4D\Leveret\Application\View;
use H4D\Leveret\Exception\AclException;
use H4D\Leveret\Exception\ApplicationException;
use H4D\Leveret\Exception\AuthException;
use H4D\Leveret\Exception\BadRequestException;
use H4D\Leveret\Exception\ConfigErrorException;
use H4D\Leveret\Exception\RouteNotFoundException;
use H4D\Leveret\Exception\ViewException;
use H4D\Leveret\Filter\FilterInterface;
use H4D\Leveret\Filter\Filters\DefaultFilter;
use H4D\Leveret\Http\Request;
use H4D\Leveret\Http\Response;
use H4D\Leveret\Http\Status;
use H4D\Leveret\Service\ServiceContainer;
use H4D\Leveret\Validation\ConstraintInterface;
use H4D\Leveret\Validation\ConstraintValidator;
use H4D\Patterns\Collections\SubscribersCollection;
use H4D\Patterns\Interfaces\EventInterface;
use H4D\Patterns\Interfaces\PublisherInterface;
use H4D\Patterns\Traits\SubscribersAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Retrinko\Ini\Exceptions\FileException;


class Application implements PublisherInterface
{
    use SubscribersAwareTrait;

    const ENV_PRODUCTION  = 'production';
    const ENV_DEVELOPMENT = 'development';
    const ENV_MAINTENANCE = 'maintenance';

    const AUTO_REQUEST_VALIDATION_MODE_NO_REQUEST_VALIDATION          = 'NO_VALIDATION';
    const AUTO_REQUEST_VALIDATION_MODE_REQUEST_VALIDATION_BEFORE_AUTH = 'VALIDATION_BEFORE_AUTH';
    const AUTO_REQUEST_VALIDATION_MODE_REQUEST_VALIDATION_AFTER_AUTH  = 'VALIDATION_AFTER_AUTH';

    const TRANSLATION_SERVICE_NAME     = 'Translator';
    const DATE_DECORATION_SERVICE_NAME = 'DateDecorator';

    /**
     * @var Router
     */
    protected $router;
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var Response
     */
    protected $response;
    /**
     * @var View
     */
    protected $view;
    /**
     * @var View
     */
    protected $layout;
    /**
     * @var string
     */
    protected $viewTemplatesDirectory;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Route
     */
    protected $currentRoute;
    /**
     * @var string
     */
    protected $layoutTemplateFile;
    /**
     * @var array
     */
    protected $requestConstraintsViolations;
    /**
     * @var string
     */
    protected $autoRequestValidationMode = self::AUTO_REQUEST_VALIDATION_MODE_NO_REQUEST_VALIDATION;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var FilterInterface
     */
    protected $defaultInputFilter;
    /**
     * @var ServiceContainerInterface
     */
    protected $serviceContainer;
    /**
     * @var Acls
     */
    protected $acls;

    /**
     * @param string $configFile
     */
    public function __construct($configFile = null)
    {
        // Load config file
        $this->loadConfig($configFile);

        // Init required stuff
        $this->logger = new NullLogger();
        $this->acls = new Acls();
        $this->serviceContainer = ServiceContainer::i();
        $this->subscribers = new SubscribersCollection();
        $this->request = new Request($_SERVER);
        $this->router = Router::i();
        $this->response = new Response();

        // Apply config
        $this->applyConfig();

        // Init
        $this->init();
    }

    /**
     * @param string $configFile
     *
     * @return $this
     * @throws FileException
     */
    protected function loadConfig($configFile)
    {
        $configFile = is_null($configFile) ? (__DIR__ . '/Defaults/config/config.ini') : $configFile;
        $this->config = Config::load($configFile);

        return $this;
    }

    /**
     * @return $this
     * @throws ConfigErrorException
     */
    protected function applyConfig()
    {
        // Set app name
        $this->setName($this->getConfig()->getApplicationName());

        // Set error handler
        $errorHandler = $this->getConfig()->getErrorHandler();
        set_error_handler(array(get_class($this), $errorHandler));

        // Set content type
        $this->setContentType($this->getConfig()->getDefaultContentType());

        // Set paths
        $this->setViewTemplatesDirectory($this->getConfig()->getViewsPath());

        // Default input filter
        $this->defaultInputFilter = new DefaultFilter($this->getConfig()->getDefaultInputFilterType());
        $this->getRequest()->setDefaultFilter($this->defaultInputFilter);

        return $this;
    }

    protected function init()
    {
        // Init services
        $this->initServices();
        // Init ACLs
        $this->initAcls();
        // Init routes
        $this->initRoutesDefinedInConfigFile();
        $this->initRoutes();
        // Init views
        $this->initView();
        $this->initLayout();
    }

    /**
     * Method for registering app services.
     */
    protected function initServices()
    {

    }

    /**
     * Method for registering app ACLs.
     */
    protected function initAcls()
    {

    }

    /**
     * Method for registering app routes.
     */
    protected function initRoutes()
    {

    }

    /**
     * Register routes defined in the app config file
     */
    protected function initRoutesDefinedInConfigFile()
    {
        if (true == $this->getConfig()->getRegisterRoutesDefinedInConfigFile())
        {
            $configFileRoutes = $this->getConfig()->getConfigFileRoutes();
            foreach($configFileRoutes as $name=>$configFileRoute)
            {
                if ($configFileRoute->isControllerActionCallback())
                {
                    $this->registerRoute($configFileRoute->getMethod(), $configFileRoute->getPattern())
                         ->useController($configFileRoute->getCallbackControllerName(),
                                         $configFileRoute->getCallbackActionName())
                         ->setName($name);
                }
                else
                {
                    if (method_exists($this, $configFileRoute->getCallbackApplicationMethodName()))
                    {
                        $this->registerRoute($configFileRoute->getMethod(), $configFileRoute->getPattern())
                             ->setAction(function () use ($configFileRoute)
                             {
                                 call_user_func([$this,
                                                 $configFileRoute->getCallbackApplicationMethodName()]);
                             })
                             ->setName($name);
                    }
                }
            }
        }
    }

    protected function initView()
    {
        $this->view = new View();
        if ($this->isServiceRegistered(self::TRANSLATION_SERVICE_NAME))
        {
            $this->view->setTranslator($this->getService(self::TRANSLATION_SERVICE_NAME));
        }
        if ($this->isServiceRegistered(self::DATE_DECORATION_SERVICE_NAME))
        {
            $this->view->setDateDecorator($this->getService(self::DATE_DECORATION_SERVICE_NAME));
        }

    }

    protected function initLayout()
    {
        $this->layout = new View();
        if ($this->isServiceRegistered(self::TRANSLATION_SERVICE_NAME))
        {
            $this->layout->setTranslator($this->getService(self::TRANSLATION_SERVICE_NAME));
        }
        if ($this->isServiceRegistered(self::DATE_DECORATION_SERVICE_NAME))
        {
            $this->layout->setDateDecorator($this->getService(self::DATE_DECORATION_SERVICE_NAME));
        }
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Convert errors into Exceptions
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     *
     * @return bool
     * @throws \Exception
     */
    public static function errorHandler($errno, $errstr = null, $errfile = null, $errline = null)
    {
        if(!($errno & error_reporting()))
        {
            return true;
        }
        throw new ApplicationException(sprintf('Error %s on file %s [%s] - %s',
                                               $errno, $errfile, $errline, $errstr));
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->getConfig()->getEnvironment();
    }

    /**
     * @return bool
     */
    public function inDevelopmentEnvironment()
    {
        return self::ENV_DEVELOPMENT == $this->getEnvironment();
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return $this
     * @throws \Exception
     */
    public function setLogger($logger)
    {
        if (false == $logger instanceof LoggerInterface)
        {
            throw new ApplicationException(
                sprintf('Error setting application logger. "%s" is not an instance of ' .
                        'Psr\Log\LoggerInterface.', get_class($logger)));
        }

        $this->logger = $logger;

        return $this;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     *
     * @return $this
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return View
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param View $view
     *
     * @return $this
     */
    public function setLayout($view)
    {
        $this->layout = $view;

        return $this;
    }

    /**
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param View $view
     *
     * @return $this
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->getResponse()->getHeaders()->getContentType();
    }

    /**
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->getResponse()->getHeaders()->setContentType($contentType);
    }

    /**
     * @param string $viewTemplatesDirectory
     *
     * @return $this
     * @throws \Exception
     */
    public function setViewTemplatesDirectory($viewTemplatesDirectory)
    {
        if(!is_dir($viewTemplatesDirectory))
        {
            throw new ApplicationException(sprintf('Ivalid templates path: "%s" is not a directory.',
                                                   $viewTemplatesDirectory));
        }
        $this->viewTemplatesDirectory = $viewTemplatesDirectory;

        return $this;
    }

    /**
     * @param string $method
     * @param string $route
     *
     * @return Route
     */
    public function registerRoute($method, $route)
    {
        return $this->router->registerRoute($method, $route);
    }

    protected function preRoute()
    {

    }

    protected function postRoute()
    {

    }

    /**
     * @throws AuthException
     */
    protected function authenticate()
    {
        if ($this->getCurrentRoute()->hasAuthRequirements())
        {
            if(false == $this->getRequest()->hasAuth())
            {
                throw new AuthException('Authentication required!');
            }
            else
            {
                $authenticator = $this->getCurrentRoute()->getAuthenticator();
                $user = $this->getRequest()->getAuthUser();
                $pass = $this->getRequest()->getAuthPassword();
                $remoteAdress = $this->getRequest()->getRemoteAddress();
                if (false == $authenticator->authenticate($user, $pass, $remoteAdress))
                {
                    throw new AuthException(sprintf('Authentication failed! (%s)',
                                                    $authenticator->getMessage()));
                }
            }
        }
    }

    /**
     * @param string $url
     * @param int $statusCode
     */
    public function redirect($url, $statusCode = Status::HTTP_SEE_OTHER)
    {
        if (strpos($url, 'http') !== 0)
        {
            $url = sprintf('%s://%s/%s',
                           $this->request->getProtocol(),
                           $this->request->getHost(),
                           ltrim($url, '/'));
        }
        header('Location: ' . $url, true, $statusCode);
        die();
    }

    /**
     * @param Route $route
     *
     * @throws AclException
     */
    protected function checkAcls($route)
    {
        if (!$this->getAcls()->isEmpty())
        {
            $controllerAcls = $routeAcls = [];
            // Get ACLs for controllers
            if (true == $route->hasController())
            {
                $controllerAcls = $this->getAcls()
                                       ->getAclsForController($route->getControllerClassName(),
                                                              $route->getControllerActionName());

            }

            // Get ACLs for routes
            if (Route::DEFAULT_ROUTE_NAME != $route->getName() && !empty($route->getName()))
            {
                $routeAcls = $this->getAcls()->getAclsForRoute($route->getName());
            }

            $acls = array_merge($controllerAcls, $routeAcls);
            if (count($acls) > 0)
            {
                /** @var AclInterface $acl */
                foreach ($acls as $acl)
                {
                    $isAllowed = $acl->isAllowed();
                    if (false === $isAllowed)
                    {
                        $msg = $acl->hasMessage()
                            ? sprintf('Access not allowed: %s', $acl->getMessage())
                            : 'Access not allowed!';
                        $this->logger->debug($msg, ['acl' => get_class($acl)]);
                        if ($acl->hasRedirectUrl())
                        {
                            $this->logger->debug('ACL redirection!', ['acl' => get_class($acl),
                                                                      'url' => $acl->getRedirectUrl()]);
                            $this->redirect($acl->getRedirectUrl(), Status::HTTP_SEE_OTHER);
                        }
                        else
                        {
                            throw new AclException($msg);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Route $route
     *
     * @throws ApplicationException
     */
    protected function dispatchRoute($route)
    {
        if (true == $route->hasController())
        {
            $controllerName = $route->getControllerClassName();
            if(!class_exists($controllerName))
            {
                throw new ApplicationException(sprintf('Controller class "%s" does not exist.',
                                                       $controllerName));
            }
            $controllerInstancce = new $controllerName($this);
            if (false == $controllerInstancce instanceof Controller)
            {
                throw new ApplicationException(sprintf('Controller "%s" is not an instance of H4D\Leveret\Application\Controller.',
                                                       $controllerName));
            }
            if (false == method_exists($controllerInstancce, $route->getControllerActionName()))
            {
                throw new ApplicationException(sprintf('Method "%s" is not defined in controller "%s".',
                                                       $route->getControllerActionName(),
                                                       $controllerName));
            }

            // Call controller preDispatch method if exists
            if (true == method_exists($controllerInstancce, 'preDispatch'))
            {
                call_user_func(array($controllerInstancce, 'preDispatch'));
            }

            // Call controller action
            call_user_func_array(array($controllerInstancce,
                                       $route->getControllerActionName()),
                                 $route->getParams());

            // Call controller postDispatch method if exists
            if (true == method_exists($controllerInstancce, 'postDispatch'))
            {
                call_user_func(array($controllerInstancce, 'postDispatch'), array());
            }

        }
        elseif(is_callable($route->getAction()))
        {
            call_user_func_array($route->getAction(), $route->getParams());
        }
        else
        {
            throw new ApplicationException(sprintf('No action defined for route %s.', $route->getPattern()));
        }
    }

    /**
     * @param Route $route
     */
    protected function preDispatchRoute($route)
    {
        if($route->hasPreDispatchActions())
        {
            foreach($route->getPreDispatchActions() as $action)
            {
                call_user_func_array($action, array($route, $this));
            }
        }
    }

    /**
     * @param Route $route
     */
    protected function postDispatchRoute($route)
    {
        if($route->hasPostDispatchActions())
        {
            foreach($route->getPostDispatchActions() as $action)
            {
                call_user_func_array($action, array($route, $this));
            }
        }
    }

    /**
     * @throws AuthException
     * @throws BadRequestException
     */
    public function authenticateAndValidateRequest()
    {
        switch($this->getAutoRequestValidationMode())
        {
            case self::AUTO_REQUEST_VALIDATION_MODE_REQUEST_VALIDATION_BEFORE_AUTH:
                $this->validateRequest($this->getRequest(),
                                       $this->getCurrentRoute()->getRequestConstraints(), true);
                $this->authenticate();
                break;

            case self::AUTO_REQUEST_VALIDATION_MODE_REQUEST_VALIDATION_AFTER_AUTH:
                $this->authenticate();
                $this->validateRequest($this->getRequest(),
                                       $this->getCurrentRoute()->getRequestConstraints(), true);
                break;

            case self::AUTO_REQUEST_VALIDATION_MODE_NO_REQUEST_VALIDATION:
            default:
                $this->authenticate();
                break;
        }
    }

    protected function dispatch()
    {
        $this->resolveRoute();
        $this->getLogger()->info(sprintf('Dispatching route: %s',
                                         $this->getCurrentRoute()->getPattern()),
                                 $this->getCurrentRoute()->getNamedParams());
        // Checks ACLs
        $this->checkAcls($this->getCurrentRoute());
        // Authentication & Request validation
        $this->authenticateAndValidateRequest();
        // Predispatch
        $this->preDispatchRoute($this->getCurrentRoute());
        // Dispatch
        $this->dispatchRoute($this->getCurrentRoute());
        // Postdispatch
        $this->postDispatchRoute($this->getCurrentRoute());
    }

    /**
     * @return Route
     */
    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }

    /**
     * @param Route $route
     *
     * @return $this
     */
    protected function setCurrentRoute($route)
    {
        $this->currentRoute = $route;
        return $this;
    }

    /**
     * @return Route
     * @throws RouteNotFoundException
     */
    protected function resolveRoute()
    {
        $this->preRoute();
        $this->setCurrentRoute($this->router->resolve($this->getRequest()));
        $this->postRoute();
        // Add request's filters
        $this->getRequest()->setFilters($this->getCurrentRoute()->getRequestFilters());

        return $this->getCurrentRoute();
    }

    /**
     * @throws Exception\RouteNotFoundException
     */
    public function run()
    {
        try
        {
            $this->dispatch();
        }
        catch(RouteNotFoundException $e)
        {
            $this->getLogger()->warning($e->getMessage());
            $this->setResponse(Response::createExceptionResponse($e, Status::HTTP_NOT_FOUND));
        }
        catch(BadRequestException $e)
        {
            $this->getLogger()->warning($e->getMessage());
            $this->setResponse(Response::createExceptionResponse($e, Status::HTTP_BAD_REQUEST));
        }
        catch(AclException $e)
        {
            $this->getLogger()->warning($e->getMessage());
            $this->setResponse(Response::createExceptionResponse($e, Status::HTTP_UNAUTHORIZED));
        }
        catch(AuthException $e)
        {
            $this->getLogger()->warning($e->getMessage());
            $this->setResponse(Response::createExceptionResponse($e, Status::HTTP_UNAUTHORIZED));
        }
        catch(ApplicationException $e)
        {
            $this->getLogger()->error('Application exception!: ' . $e->getMessage());
            $this->setResponse(Response::createExceptionResponse($e, Status::HTTP_INTERNAL_SERVER_ERROR,
                                                                 $this->inDevelopmentEnvironment()));
        }
        catch(ViewException $e)
        {
            $this->getLogger()->error('View exception!: ' . $e->getMessage());
            $this->setResponse(Response::createExceptionResponse($e, Status::HTTP_INTERNAL_SERVER_ERROR,
                                                                 $this->inDevelopmentEnvironment()));
        }
        catch(\Exception $e)
        {
            $this->getLogger()->critical($e->getMessage());
            $this->setResponse(Response::createExceptionResponse($e, Status::HTTP_INTERNAL_SERVER_ERROR,
                                                                 $this->inDevelopmentEnvironment()));
        }
        // Send response
        $this->sendResponse();
    }

    /**
     * @return $this
     */
    protected function sendResponse()
    {
        $this->getResponse()->send();
        $this->getLogger()->notice(sprintf('Response sent (status: %s)',
                                           $this->getResponse()->getStatusCode()));
        $this->getLogger()->debug(sprintf('Sent (status: %s): %s',
                                           $this->getResponse()->getStatusCode(),
                                           $this->getResponse()->getBody(true)));
        return $this;
    }

    /**
     * @param string $template
     *
     * @return string
     * @throws \Exception
     */
    protected function getTemplateFileFullPath($template)
    {
        if(is_file($template))
        {
            $templateFile = $template;
        }
        elseif(is_file($this->viewTemplatesDirectory . '/' . $template))
        {
            $templateFile = $this->viewTemplatesDirectory . '/' . $template;
        }
        else
        {
            throw new ApplicationException(sprintf('Template "%s" not found in: [%s, %s].',
                                                   $template, __DIR__,
                                                   $this->viewTemplatesDirectory));
        }

        if(!is_readable($templateFile))
        {
            throw new ApplicationException(sprintf('Template file "%s" is not readable.',
                                                   $templateFile));
        }

        return $templateFile;
    }

    /**
     * @param string $template
     *
     * @throws \Exception
     */
    public function render($template)
    {
        $contents = $this->getView()
                         ->render($this->getTemplateFileFullPath($template));
        if (!is_null($this->layoutTemplateFile))
        {
            $contents = $this->getLayout()
                ->addVar('contents', $contents)
                ->render($this->layoutTemplateFile);
        }
        $this->getResponse()->setBody($contents);
    }

    /**
     * @return $this
     */
    public function disableLayout()
    {
        $this->layoutTemplateFile = null;
        return $this;
    }

    /**
     * @param string $template
     *
     * @return $this
     * @throws ApplicationException
     */
    public function useLayout($template)
    {
        $this->layoutTemplateFile = $this->getTemplateFileFullPath($template);
        return $this;
    }

    /**
     * @param Request $request
     * @param array $constraints
     * @param bool $throwsExceptions
     *
     * @return array Constraints violation messages
     * @throws BadRequestException
     */
    protected function validateRequest(Request $request, array $constraints,
                                       $throwsExceptions = false )
    {
        $this->requestConstraintsViolations = array();
        $requestParams = array_merge($request->getParams(),
                                     $request->getQuery(),
                                     $this->getCurrentRoute()->getNamedParams());

        // Check required params
        $requiredParams = $this->getCurrentRoute()->getRequiredParams();
        if (count($requiredParams)>0)
        {
            foreach($requiredParams as $requiredParamName)
            {
                if (!array_key_exists($requiredParamName, $requestParams))
                {
                    $this->requestConstraintsViolations[$requiredParamName] = ['Required param missing!'];
                }
            }
        }

        // Validate request params
        foreach($constraints as $paramName=>$paramsConstraints)
        {
            if (array_key_exists($paramName, $requestParams))
            {
                $paramIsRequired = in_array($paramName, $requiredParams);
                $paramIsEmpty = empty($requestParams[$paramName]);
                if ($paramIsRequired || !$paramIsEmpty)
                {
                    $violations = $this->validateParam($requestParams[$paramName], $paramsConstraints);
                    if (count($violations)>0)
                    {
                        $this->requestConstraintsViolations[$paramName] = $violations;
                    }
                }
            }
        }

        if(true == $throwsExceptions && count($this->requestConstraintsViolations)>0)
        {
            throw new BadRequestException($this->getRequestConstraintsViolationMessagesAsString());
        }
        return $this->requestConstraintsViolations;
    }

    /**
     * @param mixed $paramValue
     * @param ConstraintInterface[] $paramsConstraints
     *
     * @return array Constraints violation messages
     */
    protected function validateParam($paramValue, $paramsConstraints)
    {
        $validator = new ConstraintValidator();
        $violations = [];
        foreach($paramsConstraints as $paramConstraint)
        {
            $isValid = $validator->validate($paramValue, $paramConstraint);
            if (false == $isValid)
            {
                $violations = array_merge($violations, $validator->getConstraintViolations());
            }
        }
        return $violations;
    }

    /**
     * @param Request $request If null use current app. request
     * @param array $constraints If null use current route constraints
     *
     * @return bool
     */
    public function isValidRequest(Request $request = null, array $constraints = null)
    {
        $request = is_null($request) ? $this->getRequest() : $request;
        $constraints = is_null($constraints) ? $this->getCurrentRoute()->getRequestConstraints() : $constraints;
        $violations = $this->validateRequest($request, $constraints);

        return !(count($violations)>0);
    }

    /**
     * @return array
     */
    public function getRequestConstraintsViolations()
    {
        return is_array($this->requestConstraintsViolations)
            ? $this->requestConstraintsViolations : [];
    }

    /**
     * @return array
     */
    public function getRequestConstraintsViolationMessages()
    {
        return $this->getRequestConstraintsViolations();
    }

    /**
     * @param string $separator
     *
     * @return string
     */
    public function getRequestConstraintsViolationMessagesAsString($separator = PHP_EOL)
    {
        $aux = array();
        if (is_array($this->getRequestConstraintsViolationMessages()))
        {
            foreach($this->getRequestConstraintsViolationMessages() as $key=>$msgs)
            {
                foreach($msgs as $msg)
                {
                    $aux[] = $key.': '.$msg;
                }
            }
        }
        return implode($separator, $aux);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getAutoRequestValidationMode()
    {
        return $this->autoRequestValidationMode;
    }

    /**
     * @param string $autoRequestValidationMode
     *
     * @return $this
     */
    public function setAutoRequestValidationMode($autoRequestValidationMode)
    {
        $this->autoRequestValidationMode = $autoRequestValidationMode;
        return $this;
    }

    public function renderAppInfo()
    {
        $this->setContentType('text/html');
        $this->getView()->addVar('app', $this);
        $this->render(__DIR__.'/Defaults/views/app-info.phtml');
    }

    /**
     * @param EventInterface $event
     */
    public function publish(EventInterface $event)
    {
        foreach ($this->getSubscribers() as $subscriber)
        {
            try
            {
                $subscriber->update($event, $this);
            }
            catch (\Exception $e)
            {
                $this->getLogger()->error('Exception publishing application event!',
                                          ['event' => get_class($event),
                                           'subscriber' => get_class($subscriber),
                                           'exception' => get_class($e),
                                           'exceptionMsg' => $e->getMessage(),
                                           'exceptionCode' => $e->getCode(),
                                           'exceptionFile' => $e->getFile(),
                                           'exceptionLine' => $e->getLine()]);
            }
        }
    }

    /**
     * @param ServiceContainerInterface $serviceContainer
     *
     * @return $this
     */
    public function setServiceContainer(ServiceContainerInterface $serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;

        return $this;
    }

    /**
     * @param string $serviceName
     *
     * @return mixed
     */
    public function getService($serviceName)
    {
        return $this->serviceContainer->get($serviceName);
    }

    /**
     * @param string $serviceName
     * @param mixed $value
     * @param bool $singleton
     */
    public function registerService($serviceName, $value, $singleton = false)
    {
        $this->serviceContainer->register($serviceName, $value, $singleton);
    }

    /**
     * @param string $serviceName
     *
     * @return bool
     */
    public function isServiceRegistered($serviceName)
    {
        return $this->serviceContainer->isRegistered($serviceName);
    }

    /**
     * @return ServiceContainerInterface
     */
    public function getServiceContainer()
    {
        return $this->serviceContainer;
    }

    /**
     * @return Acls
     */
    protected function getAcls()
    {
        return $this->acls;
    }

    /**
     * @param AclInterface $acl
     * @param string $controllerName
     * @param array $applyToActions
     * @param array $excludedActions
     *
     * @return $this
     */
    public function registerAclForController(AclInterface $acl,
                                             $controllerName,
                                             $applyToActions = ['*'],
                                             $excludedActions = [])
    {
        $this->getAcls()->addAclForController($acl, $controllerName, $applyToActions, $excludedActions);

        return $this;
    }


    /**
     * @param AclInterface $acl
     * @param string $routeName
     *
     * @return $this
     */
    public function registerAclForRoute(AclInterface $acl, $routeName)
    {
        $this->getAcls()->addAclForRoute($acl, $routeName);

        return $this;
    }

}

