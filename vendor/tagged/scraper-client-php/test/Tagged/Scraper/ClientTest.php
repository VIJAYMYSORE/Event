<?php

namespace Tagged\Scraper;

use PHPUnit_Framework_TestCase;
use Exception;
use Pest_Exception;

class ClientTest extends PHPUnit_Framework_TestCase {
  public function testHostHasSaneDefault() {
    $scraper = new Client();
    $this->assertEquals($scraper->getHost(), 'localhost');
  }

  public function testHostCanBeSetDuringConstruction() {
    $host = 'test.com';
    $scraper = new Client(array('host' => $host));
    $this->assertEquals($scraper->getHost(), $host);
  }

  public function testPortHasSaneDefault() {
    $scraper = new Client();
    $this->assertEquals($scraper->getPort(), 3000);
  }

  public function testPortCanBeSetDuringConstruction() {
    $port = 123;
    $scraper = new Client(array('port' => $port));
    $this->assertEquals($scraper->getPort(), $port);
  }

  /**
   * @dataProvider dataOptionChanges
   */
  public function testWebServiceIsResetWhenOptionIsChanged($option, $value1, $value2) {
    $scraper = new Client(array($option => $value1));
    $webService1 = $scraper->getWebService();
    $setter = 'set' . ucfirst($option);
    $scraper->$setter($value2);
    $webService2 = $scraper->getWebService();
    $this->assertNotSame($webService1, $webService2);
  }

  public function testTimeoutHasSaneDefault() {
    $scraper = new Client();
    $this->assertEquals($scraper->getTimeout(), 10000);
  }

  public function testMaxTimeoutCanBeSetDuringConstruction() {
    $timeout = 1;
    $scraper = new Client(array('timeout' => $timeout));
    $this->assertEquals($scraper->getTimeout(), $timeout);
  }

  /**
   * @dataProvider dataValidResponses
   */
  public function testInfoMakesApiCallWithEncodedUrl($response) {
    $url = 'http://www.example.com/test';
    $host = 'test.com';

    $webService = $this->getMockBuilder('PestJSON')
      ->setConstructorArgs(array('http://' . $host))
      ->getMock();

    $webService->expects($this->once())
      ->method('get')
      ->with($this->equalTo('/info?url=http%3A%2F%2Fwww.example.com%2Ftest'))
      ->will($this->returnValue($response));

    $scraper = new Client(array('host' => $host));
    $scraper->setWebService($webService);

    $data = $scraper->info($url);
  }

  /**
   * @dataProvider dataValidResponses
   */
  public function testInfoPassesTimeoutToWebService($response) {
    $url = 'http://www.example.com/test';
    $host = 'test.com';
    $timeout = 123;

    $webService = $this->getMockBuilder('PestJSON')
      ->setConstructorArgs(array('http://' . $host))
      ->getMock();

    $webService->expects($this->once())
      ->method('get')
      ->with($this->equalTo('/info?url=http%3A%2F%2Fwww.example.com%2Ftest'))
      ->will($this->returnValue($response));

    $scraper = new Client(array('host' => $host));
    $scraper->setTimeout($timeout);
    $scraper->setWebService($webService);

    $data = $scraper->info($url);
    $this->assertEquals($webService->curl_opts[CURLOPT_TIMEOUT], $timeout);
  }

  /**
   * @dataProvider dataValidResponses
   */
  public function testInfoDecodesJsonResponse($response) {
    $url = 'http://www.example.com/test';
    $host = 'test.com';

    $webService = $this->getMockBuilder('PestJSON')
      ->setConstructorArgs(array('http://' . $host))
      ->getMock();

    $webService->expects($this->once())
      ->method('get')
      ->will($this->returnValue($response));

    $scraper = new Client(array('host' => $host));
    $scraper->setWebService($webService);

    $data = $scraper->info($url);

    $this->assertEquals($data->type, 'youtube');
  }

  /**
   * @expectedException Tagged\Scraper\ClientException
   */
  public function testInfoThrowsExceptionWhenWebServiceThrowsException() {
    $url = 'http://www.example.com/test';
    $host = 'test.com';

    $webService = $this->getMockBuilder('PestJSON')
      ->setConstructorArgs(array('http://' . $host))
      ->getMock();

    $webService->expects($this->once())
      ->method('get')
      ->will($this->throwException(new Pest_Exception('test')));

    $scraper = new Client(array('host' => $host));
    $scraper->setWebService($webService);

    $data = $scraper->info($url);
  }

  /**
   * @expectedException Tagged\Scraper\ClientException
   */
  public function testInfoThrowsExceptionWhenWebServiceCannotHandleUrl() {
    $url = 'http://www.example.com/test';
    $host = 'test.com';

    $webService = $this->getMockBuilder('PestJSON')
      ->setConstructorArgs(array('http://' . $host))
      ->getMock();

    $webService->expects($this->once())
      ->method('get')
      ->will($this->throwException(new Exception('test')));

    $scraper = new Client(array('host' => $host));
    $scraper->setWebService($webService);

    $data = $scraper->info($url);
  }

  /**
   * Integration test with a running scraper instance
   *
   * @group integration
   */
  public function testIntegration() {
    $url = 'http://www.youtube.com/watch?v=4HsAWuAFvpY';
    $host = 'localhost';
    $scraper = new Client(array('host' => $host));
    $data = $scraper->info($url);
    $this->assertEquals($data->type, 'youtube');
  }

  public function dataOptionChanges() {
    return array(
      array('host', 'host1', 'host2'),
      array('port', 1234, 5678)
    );
  }

  public function dataValidResponses() {
    return array(
      array(
        json_decode('{
          "type": "youtube",
          "url": "http://www.youtube.com/watch?v=4HsAWuAFvpY",
          "id": "4HsAWuAFvpY",
          "title": "TULUKA TV en CN23",
          "description": "TULUKA TV en CN23",
          "images": {
            "default": "https://i1.ytimg.com/vi/4HsAWuAFvpY/default.jpg",
            "medium": "https://i1.ytimg.com/vi/4HsAWuAFvpY/mqdefault.jpg",
            "high": "https://i1.ytimg.com/vi/4HsAWuAFvpY/hqdefault.jpg",
            "standard": "https://i1.ytimg.com/vi/4HsAWuAFvpY/sddefault.jpg"
          },
          "created_date": "2012-02-13T04:33:29.000Z"
        }')
      )
    );
  }
}
