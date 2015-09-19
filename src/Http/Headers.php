<?php

namespace H4D\Leveret\Http;

class Headers
{
    const HEADER_CONTENT_TYPE     = 'Content-Type';
    const CONTENT_TYPE_TEXT_HTML  = 'text/html';
    const CONTENT_TYPE_TEXT_PLAIN = 'text/plain';
    const CONTENT_TYPE_JSON       = 'application/json';

    /**
     * @var array
     */
    protected $headers = array();

    /**
     * @param array $headers
     */
    public function __construct(array $headers = array())
    {
        $this->setHeaders($headers);
    }

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->resetHeaders();
        foreach($headers as $key => $value)
        {
            $this->addHeader($key, $value);
        }

        return $this;
    }

    /**
     * @param string $key
     * @param string $default
     *
     * @return array
     */
    public function getHeader($key, $default = '')
    {
        return (isset($this->headers[$key])) ? $this->headers[$key] : $default;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $key
     * @param string|array $value
     *
     * @return $this
     */
    public function addHeader($key, $value)
    {
        if (is_array($value))
        {
            foreach($value as $val)
            {
                $this->addHeader($key, $val);
            }
        }
        if (is_string($value))
        {
            $this->headers[$this->parseKey($key)][] = $value;
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function removeHeader($key)
    {
        if(isset($this->headers[$key]))
        {
            unset($this->headers[$key]);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function resetHeaders()
    {
        $this->headers = array();

        return $this;
    }

    /**
     * @param string $contentType
     *
     * @return $this
     */
    public function setContentType($contentType = self::CONTENT_TYPE_TEXT_HTML)
    {
        $this->removeHeader(self::HEADER_CONTENT_TYPE);
        $this->addHeader(self::HEADER_CONTENT_TYPE, $contentType);

        return $this;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->getHeader(self::HEADER_CONTENT_TYPE, self::CONTENT_TYPE_TEXT_HTML);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function parseKey($key)
    {
        $key = strtolower($key);
        $key = str_replace(array('-', '_'), ' ', $key);
        $key = preg_replace('#^http #', '', $key);
        $key = ucwords($key);
        $key = str_replace(' ', '-', $key);

        return $key;
    }

}