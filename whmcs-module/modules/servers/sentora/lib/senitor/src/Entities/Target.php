<?php namespace Ballen\Senitor\Entities;

use Ballen\Senitor\Entities\AuthBlock;

class Target
{

    /**
     * The server's HTTP address eg. https://cp.domain.com/
     * @var string
     */
    private $server;

    /**
     * The username to connect to the web service with.
     * @var string
     */
    private $user;

    /**
     * The password to connect to the web service with.
     * @var string 
     */
    private $pass;

    /**
     * The API key for the Sentora server.
     * @var string 
     */
    private $key;

    public function __construct($server, $user, $pass, $key)
    {
        $this->server = $this->formatServerAddress($server);
        $this->user = $user;
        $this->pass = $pass;
        $this->key = $key;
    }

    /**
     * Attempts authentication against the XMWS host server.
     */
    public function checkAuth()
    {
        
    }

    /**
     * Return the Sentora server address.
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Return the username to use for the XMWS session.
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Return the password to use for the XMWS session.
     * @return string
     */
    public function getPassword()
    {
        return $this->pass;
    }

    /**
     * Return the API key to use for the XMWS session.
     * @return string
     */
    public function getApiKey()
    {
        return $this->key;
    }

    /**
     * Return the AuthBlock object
     * @return \Ballen\Senitor\Entities\AuthBlock
     */
    public function getAuthBlock()
    {
        return (new AuthBlock($this))->getXmlBlock();
    }

    /**
     * Validates and formats the supplied Server address.
     * @param string $server The server name formatted like 'https://cp.domain.com'
     * @return string The formatted string (will remove any trailing slashes)
     * @throws Ballen\Senitor\Exceptions\InvalidXmwsTargetAddress
     */
    private function formatServerAddress($server)
    {
        if (!(substr($server, 0, 7) == "http://" || substr($server, 0, 8) == "https://")) {
            throw new \Ballen\Senitor\Exceptions\InvalidXmwsTargetAddress("The Sentora Server address you supplied (" . $server . ") is invalid, it must start with 'https://' or 'http://'.");
        }
        return rtrim($server, '/');
    }
}
