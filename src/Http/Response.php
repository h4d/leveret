<?php

namespace H4D\Leveret\Http;

class Response
{
    /**
     * @var Status
     */
    protected $status = Status::HTTP_OK;
    /**
     * @var string
     */
    protected $body = '';
    /**
     * @var Headers
     */
    protected $headers;
    /**
     * @var string
     */
    protected $httpProtocolVersion = '1.1';
    /**
     * @param string $body
     * @param int $statusCode
     * @param array $headers
     */
    public function __construct($body = '', $statusCode = Status::HTTP_OK, $headers = array())
    {
        $this->body = (string)$body;
        $this->status = new Status($statusCode);
        $this->headers = new Headers($headers);
    }

    /**
     *
     * @return Response
     */
    public static function create()
    {
        return new self();
    }

    /**
     * @param $body
     * @param int $statusCode
     *
     * @return Response
     */
    public static function createSuccessReponse($body, $statusCode = Status::HTTP_OK)
    {
        return new self($body, $statusCode);
    }

    /**
     * @param string $errorDescription
     * @param int $statusCode
     *
     * @return Response
     */
    public static function createServerErrorResponse($errorDescription,
                                                     $statusCode = Status::HTTP_INTERNAL_SERVER_ERROR)
    {
        return new self($errorDescription, $statusCode);
    }

    /**
     * @param string $body
     * @param int $statusCode
     *
     * @return Response
     */
    public static function createTextResponse($body, $statusCode = Status::HTTP_OK)
    {
        return new self($body, $statusCode,
                        array(Headers::HEADER_CONTENT_TYPE => Headers::CONTENT_TYPE_TEXT_PLAIN));
    }

    /**
     * @param array $data
     * @param int $statusCode
     *
     * @return Response
     */
    public static function createJsonResponse(array $data, $statusCode = Status::HTTP_OK)
    {
        return new self(json_encode($data), $statusCode,
                        array(Headers::HEADER_CONTENT_TYPE => Headers::CONTENT_TYPE_JSON));
    }

    /**
     * @param \Exception $e
     * @param int $statusCode
     *
     * @param bool $trace
     *
     * @return Response
     */
    public static function createExceptionResponse(\Exception $e,
                                                   $statusCode = Status::HTTP_INTERNAL_SERVER_ERROR, $trace = false)
    {
        $msg = ($trace) ? $e->getMessage() . PHP_EOL . PHP_EOL .'Trace:' . PHP_EOL. $e->getTraceAsString() : $e->getMessage();
        return new self($msg,
                        $statusCode,
                        array(Headers::HEADER_CONTENT_TYPE => Headers::CONTENT_TYPE_TEXT_PLAIN));
    }

    /**
     * @param integer $statusCode
     *
     * @return $this
     * @throws \Exception
     */
    public function setStatusCode($statusCode)
    {
        $this->status->setStatusCode($statusCode);
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->status->getStatusCode();
    }

    /**
     * @param string $body
     * @param bool $append
     *
     * @return $this
     */
    public function setBody($body, $append = false)
    {
        if (false == $append)
        {
            $this->body = (string)$body;
        }
        else
        {
            $this->append($body);
        }

        return $this;
    }

    /**
     * @param bool $inSingleLine
     * @param string $eolReplacement
     *
     * @return string
     */
    public function getBody($inSingleLine = false, $eolReplacement = '[-EOL-]')
    {
        return (true == $inSingleLine) ? $this->stringToSingleLine($this->body, $eolReplacement) : $this->body;
    }

    /**
     * @param string $string
     * @param string $eolReplacement
     *
     * @return string
     */
    protected function stringToSingleLine($string, $eolReplacement)
    {
        return trim(str_replace(array(PHP_EOL), array($eolReplacement), $string));
    }

    /**
     * @param $string
     *
     * @return $this
     */
    public function append($string)
    {
        $this->body .= (string)$string;

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders($headers = array())
    {
        $this->headers->setHeaders($headers);
        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function addHeader($key, $value)
    {
        $this->headers->addHeader($key, $value);
        return $this;
    }

    /**
     * @return $this
     */
    public function resetHeaders()
    {
        $this->headers->resetHeaders();
        return $this;
    }

    /**
     * @return Headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return $this
     */
    public function sendHeaders()
    {
        if (false == headers_sent())
        {
            // status
            header(sprintf('HTTP/%s %s %s', $this->httpProtocolVersion, $this->status->getStatusCode(),
                           $this->status->getStatusText()), true, $this->status->getStatusCode());
            // headers
            foreach ($this->headers->getHeaders() as $key => $values)
            {
                foreach ($values as $value)
                {
                    header($key.': '.$value, false, $this->status->getStatusCode());
                }
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function send()
    {
        if ($this->isEmpty())
        {
            $this->headers
                ->removeHeader('Content-Type')
                ->removeHeader('Content-Length');
            $this->setBody('');
        }
        $this->sendHeaders();
        echo $this->body;
        return $this;
    }

    /**
     * Is response informative?
     *
     * @return bool
     *
     * @api
     */
    public function isInformational()
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
     * Is response successful?
     *
     * @return bool
     *
     * @api
     */
    public function isSuccessful()
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * Is the response a redirect?
     *
     * @return bool
     *
     * @api
     */
    public function isRedirection()
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * Is there a client error?
     *
     * @return bool
     *
     * @api
     */
    public function isClientError()
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * Was there a server side error?
     *
     * @return bool
     *
     * @api
     */
    public function isServerError()
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }

    /**
     * Is the response OK?
     *
     * @return bool
     *
     * @api
     */
    public function isOk()
    {
        return 200 === $this->getStatusCode();
    }

    /**
     * Is the response forbidden?
     *
     * @return bool
     *
     * @api
     */
    public function isForbidden()
    {
        return 403 === $this->getStatusCode();
    }

    /**
     * Is the response a not found error?
     *
     * @return bool
     *
     * @api
     */
    public function isNotFound()
    {
        return 404 === $this->getStatusCode();
    }

    /**
     * Is the response empty?
     *
     * @return bool
     *
     * @api
     */
    public function isEmpty()
    {
        return in_array($this->getStatusCode(), array(204, 304));
    }

}