<?php

namespace H4D\Leveret\Application;

use H4D\Leveret\Application;
use H4D\Leveret\Http\Request;
use H4D\Leveret\Http\Response;
use H4D\Leveret\Http\Status;
use H4D\Patterns\Interfaces\EventInterface;
use H4D\Patterns\Interfaces\PublisherInterface;
use H4D\Patterns\Collections\SubscribersCollection;
use H4D\Patterns\Traits\SubscribersAwareTrait;
use Psr\Log\LoggerInterface;

class Controller implements PublisherInterface
{
    use SubscribersAwareTrait;

    /**
     * @var string
     */
    protected $name;
    /**
     * @var Application
     */
    protected $app;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Route
     */
    protected $route;
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
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->logger = $app->getLogger();
        $this->response = $app->getResponse();
        $this->request = $app->getRequest();
        $this->view = $app->getView();
        $this->layout = $app->getLayout();
        $this->route = $app->getCurrentRoute();
        $this->subscribers = new SubscribersCollection();
        $this->init();
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
    public function getName()
    {
        return is_string($this->name) ? $this->name : 'UnnamedController';
    }

    /**
     * @return Application
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
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
     */
    public function setResponse($response)
    {
        $this->app->setResponse($response);
    }

    /**
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->getApp()->setContentType($contentType);
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->getApp()->getContentType();
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
     */
    public function setView($view)
    {
        $this->view = $view;
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
     */
    public function setLayout($view)
    {
        $this->layout = $view;
    }

    /**
     * @param string $template
     */
    public function useLayout($template)
    {
        $this->getApp()->useLayout($template);
    }

    public function disableLayout()
    {
        $this->getApp()->disableLayout();
    }
    /**
     * @return bool
     */
    public function isValidRequest()
    {
        return $this->getApp()->isValidRequest();
    }

    /**
     * @return array
     */
    public function getRequestValidationErrorMessages()
    {
        return $this->getApp()->getRequestConstraintsViolationMessages();
    }

    /**
     * @param string $separator
     *
     * @return string
     */
    public function getRequestValidationErrorMessagesAsString($separator = PHP_EOL)
    {
        return $this->getApp()->getRequestConstraintsViolationMessagesAsString($separator);
    }

    /**
     * @param string $template
     */
    public function render($template)
    {
        $this->getApp()->render($template);
    }

    public function init()
    {

    }

    /**
     * This method is executed before controllers action
     * @return void
     */
    public function preDispatch()
    {

    }

    /**
     * This method is executed after controllers action
     * @return void
     */
    public function postDispatch()
    {

    }

    /**
     * @param string $url
     * @param int $statusCode
     */
    protected function redirect($url, $statusCode = Status::HTTP_SEE_OTHER)
    {
        $this->getApp()->redirect($url, $statusCode);
    }

    /**
     * @param EventInterface $event
     */
    public function publish(EventInterface $event)
    {
        // Notify to controller subscribers.
        foreach ($this->getSubscribers() as $subscriber)
        {
            try
            {
                $subscriber->update($event, $this);
            }
            catch (\Exception $e)
            {
                $this->getLogger()->error('Exception publishing controller event!',
                                          ['event' => get_class($event),
                                           'subscriber' => get_class($subscriber),
                                           'exception' => get_class($e),
                                           'exceptionMsg' => $e->getMessage(),
                                           'exceptionCode' => $e->getCode(),
                                           'exceptionFile' => $e->getFile(),
                                           'exceptionLine' => $e->getLine()]);
            }
        }
        $this->getApp()->publish($event);
    }

}