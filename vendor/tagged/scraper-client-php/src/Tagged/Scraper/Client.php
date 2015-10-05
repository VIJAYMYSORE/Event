<?php

namespace Tagged\Scraper;

/**
 * Scraper client for PHP
 *
 * Usage:
 *
 * use Tagged\Scraper;
 *
 * $config = array(
 *   'host'     => 'localhost',
 *   'port'     => 3000,
 *   'timeout'  => 10000
 * );
 *
 * $client = new Client($config);
 * $url = 'http://www.youtube.com/watch?v=4HsAWuAFvpY';
 *
 * try {
 *   $data = $client->info($url);
 * } catch (ClientException) {
 *   // Unable to parse, timeout, or some other error
 * }
 *
 * echo $data->title;
 *
 */
class Client {
  /**
   * Creates a new scraper with provided configuration.
   *
   */
  const PATH = '/info';

  /**
   * Host name for scraper service, without scheme.
   * ex: scraper.tagged.com
   *
   * @var string
   */
  protected $host = 'localhost';

  /**
   * Port number for scraper service.
   *
   * @var integer
   */
  protected $port = 3000;

  /**
   * Maximum time to wait for API response, in ms.
   *
   * @var integer
   */
  protected $timeout = 10000;

  /**
   * Web service instance.
   *
   * @var \Pest
   */
  protected $webService;

  /**
   * Creates a new scraper with provided configuration.
   *
   */
  public function __construct($config = array()) {
    $this->setConfig($config);
  }

  /**
   * Sets many config options at once
   *
   * @var array $config
   * @return $this
   */
  public function setConfig(array $config) {
    foreach ($config as $key => $value) {
      $setter = 'set' . ucfirst($key);

      if (method_exists($this, $setter)) {
        $this->$setter($value);
      } else {
        trigger_error("Unsupported setter, '$key'", E_USER_WARNING);
      }
    }

    return $this;
  }

  /**
   * Returns the host name.
   *
   * @return string
   */
  public function getHost() {
    return $this->host;
  }

  /**
   * Sets the host name.
   *
   * @var string $host
   * @return $this
   */
  public function setHost($host) {
    $this->host = $host;
    $this->resetWebService();
    return $this;
  }

  /**
   * Returns the port.
   *
   * @return integer
   */
  public function getPort() {
    return $this->port;
  }

  /**
   * Sets the port.
   *
   * @var integer $port
   * @return $this
   */
  public function setPort($port) {
    $this->port = (int) $port;
    $this->resetWebService();
    return $this;
  }

  /**
   * Returns the timeout in ms.
   *
   * @return integer
   */
  public function getTimeout() {
    return $this->timeout;
  }

  /**
   * Sets the timeout in ms.
   *
   * @var integer $timeout
   * @return $this
   */
  public function setTimeout($timeout) {
    $this->timeout = (int) $timeout;
    return $this;
  }

  /**
   * Returns the web service.
   * Lazy loads an instance if not already loaded.
   *
   * @return \Pest
   */
  public function getWebService() {
    if (null === $this->webService) {
      $this->webService = new \PestJSON('http://' . $this->host . ':' . $this->port);
    }

    return $this->webService;
  }

  /**
   * Sets the web service to use.
   *
   * @var \Pest
   * @return $this
   */
  public function setWebService(\PestJSON $webService) {
    $this->webService = $webService;
    return $this;
  }

  /**
   * Clears the web service, allowing it to be reinstantiated on next
   * attempt to get info.
   *
   * @return $this
   */
  protected function resetWebService() {
    $this->webService = null;
    return $this;
  }

  /**
   * Scrapes the provided url.
   *
   * @var string
   * @throws \Tagged\ScraperException
   * @return object
   */
  public function info($url) {
    $webService = $this->getWebService();

    // Apply timeout
    $webService->curl_opts[CURLOPT_TIMEOUT] = $this->getTimeout();
    
    try {
      $data = $webService->get('/info?url=' . urlencode($url));
    } catch (\Pest_Exception $e) {
      throw new ClientException('REST client error, url: ' . $url . ' ' . $e->getMessage(), $e->getCode(), $e);
    } catch (\Exception $e) {
      throw new ClientException('Unable to get info from url: ' . $url . ' ' . $e->getMessage(), $e->getCode(), $e);
    }

    return $data;
  }
}
