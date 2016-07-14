<?php

namespace H4D\Leveret\Http;

use H4D\Leveret\Filter\FilterInterface;
use H4D\Leveret\Filter\Filters\DefaultFilter;

class Request
{
    const METHOD_DELETE = 'DELETE';
    const METHOD_GET    = 'GET';
    const METHOD_PATCH  = 'PATCH';
    const METHOD_POST   = 'POST';
    const METHOD_PUT    = 'PUT';

    /**
     * @var array
     */
    protected $rawRequest;
    /**
     * @var array
     */
    protected $params;
    /**
     * @var array
     */
    protected $filters = array();
    /**
     * @var DefaultFilter
     */
    protected $defaultFilter;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var array
     */
    protected $urlParts;
    /**
     * @var array
     */
    protected $pathParts;
    /**
     * @var Headers
     */
    protected $headers;

    /**
     * @param array $requestData ($_SERVER)
     */
    public function __construct(array $requestData)
    {
        $this->rawRequest = $requestData;
    }

    /**
     * @param array $filters
     *
     * @return $this
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @param FilterInterface $filter
     *
     * @return $this
     */
    public function setDefaultFilter(FilterInterface $filter)
    {
        $this->defaultFilter = $filter;

        return $this;
    }

    /**
     * @return DefaultFilter
     */
    public function getDefaultFilter()
    {
        return $this->defaultFilter;
    }

    /**
     * @return string
     */
    public function getRemoteAddress()
    {
        return isset($this->rawRequest['REMOTE_ADDR']) ? $this->rawRequest['REMOTE_ADDR'] : '';
    }

    /**
     * @return string
     */
    public function getForwardedAddress()
    {
        return isset($this->rawRequest['HTTP_X_FORWARDED_FOR']) ? $this->rawRequest['HTTP_X_FORWARDED_FOR'] : '';
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return isset($this->rawRequest['REQUEST_METHOD']) ? $this->rawRequest['REQUEST_METHOD'] :
            self::METHOD_GET;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        $path = parse_url($this->rawRequest['REQUEST_URI'], PHP_URL_PATH);

        return !empty($path) ? ('/' != $path) ? rtrim($path, '/') : $path : '/';
    }

    /**
     * @return array
     */
    public function getPathParts()
    {
        if (is_null($this->pathParts))
        {
            $this->pathParts = explode('/', trim($this->getPath(), '/'));
        }

        return $this->pathParts;
    }

    /**
     * @param int $index
     * @param string $default
     *
     * @return string|null
     */
    public function getPathPart($index, $default = null)
    {
        return isset($this->getPathParts()[$index]) ? $this->getPathParts()[$index] : $default;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if (is_null($this->url))
        {
            $this->url = $this->getProtocol().'://'.$this->getHost() . $this->getPath();
        }

        return $this->url;
    }

    /**
     * @return array
     */
    public function getUrlParts()
    {
        if (is_null($this->urlParts))
        {
            $this->urlParts = array_merge(array($this->getProtocol(), $this->getHost()), $this->getPathParts());
        }

        return $this->urlParts;
    }

    /**
     * @param int $index
     * @param string $default
     *
     * @return string|null
     */
    public function getUrlPart($index, $default = null)
    {
        return isset($this->getUrlParts()[$index]) ? $this->getUrlParts()[$index] : $default;
    }

    /**
     * @param bool $returnArray
     *
     * @return array|string
     */
    public function getQuery($returnArray = true)
    {
        $queryString = isset($this->rawRequest['QUERY_STRING']) ? $this->rawRequest['QUERY_STRING'] : '';
        if (true == $returnArray)
        {
            parse_str($queryString, $query);
        }
        else
        {
            $query = $queryString;
        }

        return $this->getDefaultFilter()->filter($query);
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return isset($this->rawRequest['HTTP_HOST']) ? $this->rawRequest['HTTP_HOST'] : '';
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return isset($this->rawRequest['SERVER_PORT']) ? $this->rawRequest['SERVER_PORT'] : '';
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return (false !== stripos($_SERVER['SERVER_PROTOCOL'], 'https')) ? 'https' : 'http';
    }

    /**
     * @return bool
     */
    public function hasAuth()
    {
        return isset($this->rawRequest['PHP_AUTH_USER']) && '' != $this->rawRequest['PHP_AUTH_USER'];
    }

    /**
     * @return string
     */
    public function getAuthUser()
    {
        return isset($this->rawRequest['PHP_AUTH_USER']) ? $this->rawRequest['PHP_AUTH_USER'] : '';
    }

    /**
     * @return string
     */
    public function getAuthPassword()
    {
        return isset($this->rawRequest['PHP_AUTH_PW']) ? $this->rawRequest['PHP_AUTH_PW'] : '';
    }

    /**
     * @return bool
     */
    public function isDelete()
    {
        return ($this->getMethod() == self::METHOD_DELETE);
    }

    /**
     * @return bool
     */
    public function isGet()
    {
        return ($this->getMethod() == self::METHOD_GET);
    }

    /**
     * @return bool
     */
    public function isPatch()
    {
        return ($this->getMethod() == self::METHOD_PATCH);
    }

    /**
     * @return bool
     */
    public function isPost()
    {
        return ($this->getMethod() == self::METHOD_POST);
    }

    /**
     * @return bool
     */
    public function isPut()
    {
        return ($this->getMethod() == self::METHOD_PUT);
    }

    /**
     * @return bool
     */
    public function isAjaxRequest()
    {
        return (!empty($this->rawRequest['HTTP_X_REQUESTED_WITH'])
                && 'xmlhttprequest' == strtolower($this->rawRequest['HTTP_X_REQUESTED_WITH']));
    }

    /**
     * @return array
     */
    public function getPost()
    {
        $post = array();
        if(true == $this->isPost() || true == $this->isPut() || true == $this->isDelete() || true == $this->isPatch())
        {
            $post = $this->getParams();
        }

        return $post;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        // Standard web servers
        if (isset($this->rawRequest['CONTENT_TYPE']))
        {
            $contentType = $this->rawRequest['CONTENT_TYPE'];
        }
        // Built-in PHP web server
        elseif (isset($this->rawRequest['HTTP_CONTENT_TYPE']))
        {
            $contentType = $this->rawRequest['HTTP_CONTENT_TYPE'];
        }
        else
        {
            $contentType = Headers::CONTENT_TYPE_TEXT_HTML;
        }

        return $contentType;
    }

    /**
     *
     * @return array
     */
    public function getParams()
    {
        if(is_null($this->params))
        {
            $queryString = file_get_contents('php://input');
            if (Headers::CONTENT_TYPE_JSON == $this->getContentType())
            {
                $decodedQueryString = json_decode($queryString, true);
                if (is_array($decodedQueryString))
                {
                    $this->params = $decodedQueryString;
                }
                else
                {
                    $this->params = array();
                }
            }
            else
            {
                parse_str($queryString, $this->params);
            }

            // Apply default filter
            foreach($this->params as $paramName=>$value)
            {
                $this->params[$paramName] = $this->getDefaultFilter()->filter($value);
            }

            // Apply custom filters
            if (is_array($this->filters) && count($this->filters)>0)
            {
                foreach($this->filters as $paramName=>$filters)
                {
                    if(isset($this->params[$paramName]))
                    {
                        foreach($filters as $filter)
                        {
                            if ($filter instanceof FilterInterface)
                            {
                                $this->params[$paramName] = $filter->filter($this->params[$paramName]);
                            }
                            elseif(is_callable($filter))
                            {
                                $this->params[$paramName] = $filter($this->params[$paramName]);
                            }
                        }
                    }
                }
            }
        }

        return $this->params;
    }

    /**
     * @param string $name
     * @param mixed $defaut
     *
     * @return mixed
     */
    public function getParam($name, $defaut = null)
    {
        return (isset($this->getParams()[$name]) ? $this->getParams()[$name] : $defaut);
    }

    /**
     * @return Headers
     */
    public function getHeaders()
    {
        if (is_null($this->headers))
        {
            $this->headers = new Headers($this->rawRequest);
        }

        return $this->headers;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('(%s) %s://%s%s%s (PARAMS: %s)',
                       $this->getMethod(),
                       $this->getProtocol(),
                       $this->getHost(),
                       $this->getPath(),
                       ('' != $this->getQuery(false)) ? '?'.$this->getQuery(false) : '',
                       json_encode($this->getParams()));
    }

}