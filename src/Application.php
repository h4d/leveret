<?php

namespace H4D\Leveret;

use H4D\Leveret\Application\Config;
use H4D\Leveret\Application\Controller;
use H4D\Leveret\Application\Route;
use H4D\Leveret\Application\Router;
use H4D\Leveret\Application\View;
use H4D\Leveret\Exception\ApplicationException;
use H4D\Leveret\Exception\AuthException;
use H4D\Leveret\Exception\ConfigErrorException;
use H4D\Leveret\Exception\RouteNotFoundException;
use H4D\Leveret\Exception\ViewException;
use H4D\Leveret\Http\Request;
use H4D\Leveret\Http\Response;
use H4D\Leveret\Http\Status;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ValidatorBuilder;

class Application
{
    const ENV_PRODUCTION  = 'production';
    const ENV_DEVELOPMENT = 'development';

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
    protected $name;
    /**
     * @param string $configFile
     */
    public function __construct($configFile = null)
    {
        $this->logger = new NullLogger();
        $this->init();
        $this->loadConfig($configFile);
        $this->applyConfig();

    }

    /**
     * @param $configFile
     *
     * @return $this
     * @throws Exception\FileNotFoundException
     * @throws Exception\FileNotReadableException
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
        // Set error handler
        $errorHandler = $this->config->getErrorHandler();
        set_error_handler(array(get_class(), $errorHandler));

        // Ser content type
        $this->setContentType($this->config->getDefaultContentType());

        // Set paths
        $this->setViewTemplatesDirectory($this->config->getViewsPath());

        return $this;
    }

    /**
     * @return $this
     */
    protected function init()
    {
        $this->request = new Request($_SERVER);
        $this->router = Router::i();
        $this->response = new Response();
        $this->view = new View();
        $this->layout = new View();
        $this->initRoutes();

        return $this;
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
        return $this->config->getEnvironment();
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

    public function initRoutes()
    {

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
                    throw new AuthException('Authentication failed!');
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
                throw new ApplicationException(sprintf('Controller class "%s" do not exist.',
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

    protected function dispatch()
    {
        $this->resolveRoute();
        $this->getLogger()->info(sprintf('Dispatching route: %s',
                                         $this->getCurrentRoute()->getPattern()),
                                 $this->getCurrentRoute()->getParams());
        // Authentication
        $this->authenticate();
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
     * @param array $constraints [Constraint]
     *
     * @return array [ConstraintViolationInterface]
     */
    protected function validateRequest(Request $request = null, array $constraints = null)
    {
        $this->requestConstraintsViolations = array();

        $validator = (new ValidatorBuilder())->getValidator();
        $requestParams = $request->getParams();
        foreach($constraints as $paramName=>$paramsConstraints)
        {
            if (isset($requestParams[$paramName]))
            {
                /** @var Constraint  $constraint */
                $violations = $validator->validateValue($requestParams[$paramName], $paramsConstraints);
                if (count($violations)>0)
                {
                    $this->requestConstraintsViolations[$paramName][] = $violations;
                }
            }
        }
        return $this->requestConstraintsViolations;
    }

    /**
     * @param Request $request If null use current app. request
     * @param array $constraints If null use current route constraints
     *
     * @return bool
     */
    public function isValidRequest(Request $request = null, array $constraints = null)
    {
        if (!is_null($this->getRequestConstraintsViolations()))
        {
            $request = is_null($request) ? $this->getRequest() : $request;
            $constraints = is_null($constraints) ? $this->getCurrentRoute()->getRequestConstraints() : $constraints;

            $this->validateRequest($request, $constraints);
        }
        return !(count($this->getRequestConstraintsViolations())>0);
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
        $messasges = array();
        if (is_array($this->requestConstraintsViolations))
        {
            foreach($this->requestConstraintsViolations as $key=>$violations)
            {
                /** @var ConstraintViolationList $violationList */
                foreach($violations as $violationList)
                {
                    foreach($violationList as $violation)
                    {
                        $messasges[$key][] = $violation->getMessage();
                    }

                }
            }
        }
        return $messasges;
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

}

