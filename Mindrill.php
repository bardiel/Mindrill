<?php

/**
  * A minimal Mandrill API PHP implementation
  *
  * @package Mandrill
  *
  * @author  Darren Scerri <darrenscerri@gmail.com>
  *
  * @version 1.0
  *
  */
class Mindrill
{
  private $api_key;
  private $base = 'http://mandrillapp.com/api/';
  private $version = '1.0';
  private $suffix = '.json';

  public $lastError; //last error recieved from API

  /**
   * API Constructor. If set to test automatically, will return an Exception if the ping API call fails
   *
   * @param mixed $options Could be the string of the API key or an array with configurations
   * @param bool $test=true Whether to test API connectivity on creation
   *
   * If $options is array it could be: 
   * @param string $api_key API key
   * @param string $version API version (currently only 1.0 exists)
   */
  public function __construct($options, $test=true)
  {
    $default_options = array(
      'api_key' => $this->api_key,
      'version' => $this->version,
    );

    if(is_array($options)) {
      $options = array_merge($default_options, $options);
      $this->api_key = $options['api_key'];
      $this->version = $optinos['version'];
    } else {
      $this->api_key = $options;
    }

    $this->base .= $this->version;

    if ($test === true && !$this->test())
    {
      throw new Exception('Cannot connect or authenticate with the Mandrill API');
    }
  }

  /**
   * Perform an API call.
   *
   * @param string $url='/users/ping' Endpoint URL. Will automatically add '.json' if necessary (both '/users/ping' and '/users/ping.jso'n are valid)
   * @param array $params=array() An associative array of parameters
   *
   * @return mixed Automatically decodes JSON responses. If the response is not JSON, the response is returned as is
   */
  public function call($url='/users/ping', $params=array())
  {
    if (is_null($params))
    {
      $params = array();
    }

    $url = strtolower($url);

    if (substr_compare($url, $this->suffix, -strlen($this->suffix), strlen($this->suffix)) !== 0)
    {
      $url .= $this->suffix;
    }

    $params = array_merge($params, array('key'=>$this->api_key));

    $json = json_encode($params);

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL, "{$this->base}{$url}");
    curl_setopt($ch,CURLOPT_POST,count($params));
    curl_setopt($ch,CURLOPT_POSTFIELDS,$json);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                                                                           
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($json)));

    $result = curl_exec($ch);
    curl_close($ch);
    $decoded = json_decode($result, true, 1024);

    if(is_null($decoded)) {
      $this->lastError = array('message' => $result);
      return false;
    }
    if(isset($decoded['status']) && $decoded['status'] == 'error') {
      $this->lastError = $decoded;
      return false;
    }

    return $decoded;

  }

  /**
   * Tests the API using /users/ping
   *
   * @return bool Whether connection and authentication were successful
   */
  public function test()
  {
    return $this->call('/users/ping') === 'PONG!';
  }
}