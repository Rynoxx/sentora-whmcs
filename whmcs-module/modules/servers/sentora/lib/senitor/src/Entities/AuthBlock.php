<?php namespace Ballen\Senitor\Entities;

use Ballen\Senitor\Entities\Target;

class AuthBlock
{

    /**
     * Object storage for XMWS Target Entity.
     * @var \Ballen\Senitor\Entities\Target
     */
    private $target;

    public function __construct(Target $target)
    {
        $this->target = $target;
    }

    /**
     * Retrieve the XML authentication block for the XMWS XML request.
     * @return string
     */
    public function getXmlBlock()
    {
        return $this->build();
    }

    /**
     * Builds the XML configuration block for the XMWS transmission message.
     * @return string
     */
    private function build()
    {
        $conflines = [
            'apikey' => $this->target->getApiKey(),
            'authuser' => $this->target->getUser(),
            'authpass' => $this->target->getPassword(),
        ];

        $xmlcontent = "";
        foreach ($conflines as $ckey => $cvalue) {
            $xmlcontent .= "<{$ckey}>{$cvalue}</{$ckey}>" . PHP_EOL;
        }
        return $xmlcontent;
    }

    /**
     * Returns the generated XML auth block tags.
     * @return string
     */
    public function __toString()
    {
        return $this->getXmlBlock();
    }
}
