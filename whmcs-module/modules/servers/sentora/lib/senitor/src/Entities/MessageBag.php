<?php namespace Ballen\Senitor\Entities;

class MessageBag
{

    /**
     * Object storage for singleton class pattern.
     * @var \Ballen\Senitor\Entities\MessageBag
     */
    protected static $instance = null;

    /**
     * Item array
     * @var array 
     */
    private $items = [];

    /**
     * Generated XML tags with request data.
     * @var string
     */
    private $xml;

    protected function __construct()
    {
        
    }

    protected function __clone()
    {
        
    }

    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new MessageBag();
        }
        return static::$instance;
    }

    /**
     * Add an item to the message array.
     * @param string $key The array key of which will become the XML tag name.
     * @param string $value The value of the XML tag.
     * @return \Ballen\Senitor\Entities\MessageBag
     */
    public function addItem($key, $value = null)
    {
        $this->items[$key] = $value;
        return $this;
    }

    /**
     * Set/reset the current message bag array.
     * @param array $items Items (XML keys and values) to set.
     * @return \Ballen\Senitor\Entities\MessageBag
     */
    public function setItems(array $items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * Set the XML content directly, this will take presidence over the array items.
     * @param string $data Formamted XML tags.
     * @return \Ballen\Senitor\Entities\MessageBag
     * @throws \InvalidArgumentException
     */
    public function setRaw($data)
    {
        if (!is_string($data)) {
            throw new \InvalidArgumentException("The expected format should be an XML string.");
        }
        $this->items = $data;
        return $this;
    }

    /**
     * Resets the items and the xml data. Intended to be used to avoid the exception thrown by buildXml
     * @return void
     */
    public function reset(){
        $this->items = [];
        $this->xml = null;
    }

    /**
     * Return the XML block for the Message Bag.
     * @return string The XML string.
     */
    public final function getXml()
    {
        $this->buildXml();
        return $this->xml;
    }

    /**
     * Build the XML tags for the request from the list of items.
     * @return \Ballen\Senitor\Entities\MessageBag
     * @throws \Ballen\Senitor\Exceptions\XmlSetException
     * @throws \InvalidArgumentException
     */
    private function buildXml()
    {
        if (!is_null($this->xml)) {
            throw new \Ballen\Senitor\Exceptions\XmlSetException("XML data already exists, reset the bag or utilise getXml() method.");
        }
        $this->generateXml();
        return $this;
    }

    /**
     * Generate and store the XML block data from the message bag items.
     * @return void
     */
    private function generateXml()
    {
        $xml_block = (string) "";
        if (count($this->items) > 0) {
            foreach ($this->items as $key => $item) {
                $xml_block .= "<{$key}>$item</{$key}>";
            }
        }
        $this->xml = $xml_block;
    }
}
