<?php namespace Ballen\Senitor\Entities;

use Ballen\Senitor\Entities\Target;
use Ballen\Senitor\Entities\MessageBag;

class Transmission
{

    /**
     * The generated XML transmission message.
     * @var string
     */
    private $transmission;

    /**
     * The module name of which we will send the 'transmission' to.
     * @var string
     */
    private $module;

    /**
     * The module's web service method to call.
     * @var string
     */
    private $endpoint;

    /**
     * The XMWS/Sentora Server target object.
     * @var \Ballen\Senitor\Entities\Target
     */
    private $target;

    /**
     * The <content> tag data (as an XML string).
     * @var string 
     */
    private $content;

    /**
     * Create a new transmission message (XMWS XML request)
     * @param \Ballen\Senitor\Entities\Target $target
     * @param string $endpoint The endpoint action/request.
     * @param \Ballen\Senitor\Entities\MessageBag $request
     */
    public function __construct(Target $target, $module, $endpoint, MessageBag $request)
    {
        if (empty($request)) {
            throw new \Ballen\Senitor\Exceptions\InvalidXmwsEndpoint("The XMWS endpoint cannot be empty.");
        }
        $this->target = $target;
        $this->module = $module;
        $this->endpoint = $endpoint;
        $this->content = $request->getXml();
        $this->transmission = $this->buildXml($target, $request);
    }

    /**
     * Build the transmission message XML
     * @param \Ballen\Senitor\Entities\MessageBag $request
     * @return type
     */
    private function buildXml()
    {
        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<xmws>',
            $this->getAuthBlockXml($this->target),
            '<request>' . $this->getEndpoint() . '</request>',
            '<content>' . $this->getContentXml() . '</content>',
            '</xmws>'
        ];
        return implode(PHP_EOL, $xml);
    }

    /**
     * Retrieve the generated XML XMWS authentication block
     * @param \Ballen\Senitor\Entities\Target $target
     * @return string
     */
    private function getAuthBlockXml()
    {
        return $this->target->getAuthBlock();
    }

    /**
     * Retrieve the content "<content>" tags as generated XML.
     * @return string
     */
    private function getContentXml()
    {
        return $this->content;
    }

    /**
     * Retrieve the Target XMWS host
     * @return string
     */
    public function getTarget()
    {
        return $this->target->getServer();
    }

    /**
     * Retrieve the module name of the request.
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Retreieve the endpoint/action request.
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Output the generated XMWS message in XML format.
     * @return string
     */
    public function __toString()
    {
        return $this->transmission;
    }
}
