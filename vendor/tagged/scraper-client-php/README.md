Scraper client for PHP
======================

Easily retrieve info from websites by utilizing the Tagged Scraper web service.

Installation:
-------------

Update `composer.json` with the following:

    {
      "repositories": {
        "type": "vcs",
        "url":  "git://github.tagged.com/tagged/scraper-client-php.git"
      },
      "require": {
        "tagged/scraper-client-php": "*"
      }
    }

Then run `composer update` (or `composer install` for first install).

Next, `require()` the composer autoloader if not already loaded:

    <?php
    require_once 'vender/autoload.php'

All done!

Usage:
------

    use Tagged\Scraper;

    $config = array(
      'host'      => 'localhost',
      'port'      => 3000,
      'timeout'   => 10000
    );

    $client = new Client($config);
    $url = 'http://www.youtube.com/watch?v=4HsAWuAFvpY';
    
    try {
      $data = $client->info($url);
    } catch (\Tagged\ScraperException) {
      // Unable to parse, timeout, or some other error
    }
    
    echo $data->title;

Development
-----------

Install dependencies:

    composer install

Run tests:

    vendor/bin/phpunit
