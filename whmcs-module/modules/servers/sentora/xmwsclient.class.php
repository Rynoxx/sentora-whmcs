<?php

/**
 * The official PHP XMWS API Client
 * @author Bobby Allen (ballen@bobbyallen.me)
 * @link https://github.com/bobsta63/xmws-php-client
 * @license http://opensource.org/licenses/MIT MIT
 * @version 1.1.0
 */
class xmwsclient {

    public $module = null;
    public $method = null;
    public $username = null;
    public $password = null;
    public $serverkey = null;
    public $wsurl = null;
    public $data = null;

    /**
     * Initiate XMWS server and user connection settings.
     * @param string $wsurl The URL to the ZPanelX server. (eg. http://localhost/zpanelx/ or http://cp.yourdomain.com/)
     * @param string $key The API key that is configured on the ZPanelX server (enables API access)
     * @param string $user The ZPX user of which to execute the requests as.
     * @param string $pass The corresponding user's password.
     * @return void
     */
    public function __construct($wsurl, $key, $user = '', $pass = '') {
        $this->username = $user;
        $this->password = $pass;
        $this->serverkey = $key;
        $this->wsurl = $wsurl;
    }

    /**
     * Configures the request module and method ready for the request actioner.
     * @param string $module The name of the module
     * @param string $method The web service method of which to call in the module's 'code/webservice.ext.php' file.
     * @return void
     */
    public function setRequestType($module, $method) {
        $this->module = $module;
        $this->method = $method;
    }

    /**
     * Sets the request data portion of the request XML
     * @param string $data XML tags that should be present in the <content> part of the XMWS webservice request.
     * @return void
     */
    public function setRequestData($data) {
        $this->data = $data;
    }

    /**
     * Automatically prepares and formats the XMWS XML request message based on your preset variables.
     * @return string The formatted XML message ready to post.
     */
    public function buildRequest() {
        $request_template = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                "<xmws>" .
                "\t<apikey>" . $this->serverkey . "</apikey>\n" .
                "\t<request>" . $this->method . "</request>\n" .
                "\t<authuser>" . $this->username . "</authuser>\n" .
                "\t<authpass>" . $this->password . "</authpass>\n" .
                "\t<content>" . $this->data . "</content>" .
                "</xmws>";
        return $request_template;
    }

    /**
     * The main Request class that initiates the connection and request to the web service.
     * @param type $post_xml
     * @return type 
     */
    public function request($post_xml) {
        $full_wsurl = $this->wsurl . "/api/" . $this->module;
        return $this->postRequest($full_wsurl, $post_xml);
    }

    /**
     * This takes a raw XMWS XML repsonse and converts it to a usable PHP array.
     * @param string $xml The 
     * @return type 
     */
    public function responseToArray($xml) {
        return array('response' => $this->GetXMLTagValue($xml, 'response'), 'data' => $this->GetXMLTagValue($xml, 'content'));
    }

    /**
     * Returns the value between a given XML tag.
     * @param string $xml
     * @param type $tag
     * @return type 
     */
    public function getXMLTagValue($xml, $tag) {
        $xml = " " . $xml;
        $ini = strpos($xml, '<' . $tag . '>');
        if ($ini == 0)
            return "";
        $ini += strlen('<' . $tag . '>');
        $len = strpos($xml, '</' . $tag . '>', $ini) - $ini;
        return substr($xml, $ini, $len);
    }

    /**
     * A simple POST class that attempts to POST data simply.
     * @param string $url URL to the XMWS web service controller.
     * @param string $data The data to post.
     * @param string $optional_headers Optional if you need to send additonal headers.
     * @return string The XML repsonse. 
     */
    public function postRequest($url, $data, $optional_headers = null) {
        $params = array('http' => array(
                'method' => 'POST',
                'content' => $data
        ));
        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp) {
            die("Problem reading data from " . $url . "");
        }
        $response = @stream_get_contents($fp);
        if ($response == false) {
            die("Problem reading data from " . $url . "");
        }
        return $response;
    }

    /**
     * Simply outputs the contents of the response as a PHP array (using print_r())
     * @param string $xml 
     */
    public function showXMLAsArrayData($xml) {
        echo "<pre>";
        print_r($this->ResponseToArray($xml));
        echo "</pre>";
    }

    /**
     * A simple way to build an XML section for the <content> tag, perfect for multiple data lines etc.
     * @param string $name The name of the section <tag>.
     * @param array $tags An associated array of the tag names and values to be added.
     * @return string A formatted XML section block which can then be used in the <content> tag if required.
     */
    public function newXMLContentSection($name, $tags) {
        $xml = "\t<" . $name . ">\n";
        foreach ($tags as $tagname => $tagval) {
            $xml .="\t\t<" . $tagname . ">" . $tagval . "</" . $tagname . ">\n";
        }
        $xml .= "\t</" . $name . ">\n";
        return $xml;
    }

    /**
     * Takes an XML string and converts it into a usable PHP array.
     * @param $contents string The XML content to convert to a PHP array.
     * @param $get_arrtibutes bool Retieve the tag attrubtes too?
     * @param $priotiry string
     * @return array
     */
    static function xmlDataToArray($contents, $get_attributes = 1, $priority = 'tag') {
        if (!function_exists('xml_parser_create')) {
            return array();
        }
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);
        if (!$xml_values)
            return; //Hmm...
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();
        $current = & $xml_array;
        $repeated_tag_index = array();
        foreach ($xml_values as $data) {
            unset($attributes, $value);
            extract($data);
            $result = array();
            $attributes_data = array();
            if (isset($value)) {
                if ($priority == 'tag') {
                    $result = $value;
                } else {
                    $result['value'] = $value;
                }
            }
            if (isset($attributes) and $get_attributes) {
                foreach ($attributes as $attr => $val) {
                    if ($priority == 'tag') {
                        $attributes_data[$attr] = $val;
                    } else {
                        $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                    }
                }
            }
            if ($type == "open") {
                $parent[$level - 1] = & $current;
                if (!is_array($current) or ( !in_array($tag, array_keys($current)))) {
                    $current[$tag] = $result;
                    if ($attributes_data)
                        $current[$tag . '_attr'] = $attributes_data;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    $current = & $current[$tag];
                }else {
                    if (isset($current[$tag][0])) {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level] ++;
                    } else {
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        );
                        $repeated_tag_index[$tag . '_' . $level] = 2;
                        if (isset($current[$tag . '_attr'])) {
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                    $current = & $current[$tag][$last_item_index];
                }
            } elseif ($type == "complete") {
                if (!isset($current[$tag])) {
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $attributes_data)
                        $current[$tag . '_attr'] = $attributes_data;
                }else {
                    if (isset($current[$tag][0]) and is_array($current[$tag])) {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        if ($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag . '_' . $level] ++;
                    } else {
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        );
                        $repeated_tag_index[$tag . '_' . $level] = 1;
                        if ($priority == 'tag' and $get_attributes) {
                            if (isset($current[$tag . '_attr'])) {
                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset($current[$tag . '_attr']);
                            }
                            if ($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag . '_' . $level] ++; //0 and 1 index is already taken
                    }
                }
            } elseif ($type == 'close') {
                $current = & $parent[$level - 1];
            }
        }
        return ($xml_array);
    }

}
