<?php namespace Ballen\Senitor\Handlers;

use GuzzleHttp\Message\Response as HttpClientResponse;

class XmwsResponse
{

    /**
     * The Guzzle HTTP response object
     * @var \GuzzleHttp\Message\Response
     */
    protected $http_response_object;

    /**
     * The XMWS respsonse code
     * @var string
     */
    protected $xmws_response_code;

    /**
     * The array of XML tags under the <content> tag.
     * @var array
     */
    protected $xmws_content_array;

    /**
     * The string of XML data contained in the <content> tag.
     * @var string
     */
    protected $xmws_content_string;

    public function __construct(HttpClientResponse $response)
    {
        $this->http_response_object = $response;
        $this->checkErrors();
        $this->responseCodeToProperty();
        $this->contentToProperty();
    }

    /**
     * Accessor for the HTTP response object
     * @return \GuzzleHttp\Message\Response
     */
    public function response()
    {
        return $this->http_response_object;
    }

    /**
     * Checks the response XML message for a specific error code and throw
     * the associated XmwsErrorResponse exception.
     * @throws \Ballen\Senitor\Exceptions\XmwsErrorResponse
     * @return void
     */
    private function checkErrors()
    {
        $response_code = (int) $this->response()->xml()->response;
        if (!isset($response_code)) {
            throw new \Ballen\Senitor\Exceptions\XmwsErrorResponse("No XMWS response code was found!");
        }

        switch ($response_code) {
            case 1102:
                throw new \Ballen\Senitor\Exceptions\XmwsErrorResponse("The XMWS API module action was not found on the target server.");
            case 1103:
                throw new \Ballen\Senitor\Exceptions\XmwsErrorResponse("Server	API	key	validation	failed.");
            case 1104:
                throw new \Ballen\Senitor\Exceptions\XmwsErrorResponse("User authentication	required but not provided.");
            case 1105:
                throw new \Ballen\Senitor\Exceptions\XmwsErrorResponse("Username and password validation failed.");
            case 1106:
                throw new \Ballen\Senitor\Exceptions\XmwsErrorResponse("Request	not	valid, XMWS is expecting some missing request tags.");
            case 1107:
                throw new \Ballen\Senitor\Exceptions\XmwsErrorResponse("Modular	web	service	not	found for this module.");
        }
    }

    /**
     * Return the XMWS API status code.
     * @return int
     */
    public function statusCode()
    {
        return $this->xmws_response_code;
    }

    /**
     * Return the <content> data as an array.
     * @return array
     */
    public function asArray()
    {
        return $this->xmws_content_array;
    }

    /**
     * Returns the <content> data as a stdClass object.
     * @return stdClass
     */
    public function asObject()
    {
        return $this->arrayToObject($this->xmws_content_array);
    }

    /**
     * Return the JSON representation of the response from the web service.
     * @return string
     */
    public function asJson()
    {
        return json_encode($this->asObject());
    }

    /**
     * Return the plain text response (useful for responses with out tags)
     * @return string
     */
    public function asString()
    {
        if (isset($this->asArray()[0])) {
            return $this->xmws_content_string;
        } else {
            return null;
        }
    }

    /**
     * Return the raw XML response from the web service.
     * @return string
     */
    public function responseContent()
    {
        return $this->http_response_object->getBody();
    }

    /**
     * Converts the <response> tag value to an integer and stores as an object property.
     */
    private function responseCodeToProperty()
    {
        $xml_response = $this->http_response_object->xml();
        if (isset($xml_response['response'])) {
            $this->xmws_response_code = (int) $xml_response['response'];
        }
    }

    /**
     * Converts the <content> section to an array and stores as an object property.
     */
    private function contentToProperty()
    {
        $xml_response = json_decode(json_encode((array) $this->http_response_object->xml()), 1);
        if (isset($xml_response['content'])) {
            $this->xmws_content_array = $xml_response['content'];
            $this->xmws_content_string = $xml_response['content'];
        }
    }

    /**
     * Converts an array to a stdClass object.
     * @param array $array The array to convert.
     * @return stdClass
     */
    private function arrayToObject(array $array)
    {
        return json_decode(json_encode($array), false);
    }

    /**
     * Return the raw response if the __toString() method is invoked.
     * @return string
     */
    public function __toString()
    {
        return $this->responseContent();
    }
}
