<?php

namespace Magium\Configuration\View;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ViewConfiguration
{

    protected $request;
    protected $response;
    protected $layoutFile;
    protected $viewDirectory;
    protected $viewFile;
    protected $jqueryUrl;
    protected $provideWrapperHtml;
    protected $bootstrapJsUrl;
    protected $bootstrapCssUrl;

    /**
     * ViewConfiguration constructor.
     * @param ServerRequestInterface $request
     * @param MessageInterface $response
     * @param string $viewDirectory
     * @param string $layoutFile
     * @param string $viewFile
     * @param boolean $provideWrapperHtml
     * @param boolean $provideJqueryUrl
     * @throws InvalidViewConfigurationException
     */
    public function __construct(
        ServerRequestInterface $request,
        MessageInterface $response,
        $viewDirectory = __DIR__ . '/views' ,
        $layoutFile = 'layout.phtml',
        $viewFile = 'view.phtml',
        $provideWrapperHtml = true,
        $jqueryUrl = 'https://code.jquery.com/jquery-3.2.1.min.js',
        $bootstrapJsUrl = 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js',
        $bootstrapCssUrl = 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css'
    )
    {
        $this->setRequest($request);
        $this->setResponse($response);
        $this->setViewDirectory($viewDirectory);
        $this->setLayoutFile($layoutFile);
        $this->setViewFile($viewFile);
        $this->setProvideWrapperHtml($provideWrapperHtml);
        $this->setJqueryUrl($jqueryUrl);
        $this->setBootstrapJsUrl($bootstrapJsUrl);
        $this->setBootstrapCssUrl($bootstrapCssUrl);
    }

    /**
     * @return mixed
     */
    public function getBootstrapCssUrl()
    {
        return $this->bootstrapCssUrl;
    }

    /**
     * @param mixed $bootstrapCssUrl
     */
    public function setBootstrapCssUrl($bootstrapCssUrl)
    {
        $this->bootstrapCssUrl = $bootstrapCssUrl;
    }

    /**
     * @return mixed
     */
    public function getBootstrapJsUrl()
    {
        return $this->bootstrapJsUrl;
    }

    /**
     * @param mixed $bootstrapJsUrl
     */
    public function setBootstrapJsUrl($bootstrapJsUrl)
    {
        $this->bootstrapJsUrl = $bootstrapJsUrl;
    }



    /**
     * @return mixed
     */
    public function getProvideWrapperHtml()
    {
        return $this->provideWrapperHtml;
    }

    /**
     * @param mixed $provideWrapperHtml
     */
    public function setProvideWrapperHtml($provideWrapperHtml)
    {
        $this->provideWrapperHtml = $provideWrapperHtml;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param MessageInterface $response
     */
    public function setResponse(MessageInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getLayoutFile()
    {
        return $this->layoutFile;
    }

    /**
     * @param string $layoutFile
     * @throws InvalidViewConfigurationException
     */
    public function setLayoutFile($layoutFile)
    {
        $this->layoutFile = realpath($this->getViewDirectory() . DIRECTORY_SEPARATOR . $layoutFile);
        if (!is_file($this->layoutFile)) {
            throw new InvalidViewConfigurationException('Could not resolve base layout file: ' . $layoutFile);
        }
        $this->layoutFile = basename($this->layoutFile);
    }

    /**
     * @return string
     */
    public function getViewDirectory()
    {
        return $this->viewDirectory;
    }

    /**
     * @param string $viewDirectory
     * @throws InvalidViewConfigurationException
     */
    public function setViewDirectory($viewDirectory)
    {
        $this->viewDirectory = realpath($viewDirectory);
        if (!is_dir($this->viewDirectory)) {
            throw new InvalidViewConfigurationException('Could not resolve base view directory: ' . $viewDirectory);
        }
    }

    /**
     * @return string
     */
    public function getViewFile()
    {
        return $this->viewFile;
    }

    /**
     * @param string $viewFile
     * @throws InvalidViewConfigurationException
     */
    public function setViewFile($viewFile)
    {
        $this->viewFile = realpath($this->getViewDirectory() . DIRECTORY_SEPARATOR . $viewFile);
        if (!is_file($this->viewFile)) {
            throw new InvalidViewConfigurationException('Could not resolve base file file: ' . $viewFile);
        }
        $this->viewFile = basename($this->viewFile);
    }

    /**
     * @return mixed
     */
    public function getJqueryUrl()
    {
        return $this->jqueryUrl;
    }

    /**
     * @param mixed $jqueryUrl
     */
    public function setJqueryUrl($jqueryUrl)
    {
        $this->jqueryUrl = $jqueryUrl;
    }




}
