<?php


namespace Asn;

class Scraper {

  /**
   * URL containing countries
   * @var string
   */
  const COUNTRY_LIST_URL = 'http://bgp.he.net/report/world';

  /**
   * URL prefix for specific countries
   */
  const COUNTRY_PREFIX = 'http://bgp.he.net/country/';

  /**
   * Our scraper instance
   * @var \Goutte\Client
   */
  private $_scraper = null;

  /**
   * Arguments passed to script
   * @var array
   */
  private $_args = array();

  /**
   * Output file name
   * @var string
   */
  private $_output = null;

  /**
   * Initialize our scraping instance
   */
  public function __construct($args) {
    $this->_args = $args;
    $this->_scraper = new \Goutte\Client();
    $guzzle = new \GuzzleHttp\Client();

    $this->_scraper->setClient($guzzle);
  }

  /**
   * Run the scrape job. This is the main entry point
   */
  public function run() {
    try {
      $this->_parseArguments();
    } catch (\Exception $e) {
      print $e->getMessage();
      exit(1);
    }

    $output = array();

    print "Generating ASN List...(this may take a while)\n";
    foreach ($this->_getCountries() as $country) {
      foreach ($this->_getAsns($country) as $asn) {
        $id = preg_replace('/[^0-9]/', '', $asn['Asn']);
        $output[$id] = $asn;
      }
    }
    print "Complete. Writing output file...\n";
    $this->_writeFile($output);
    print 'Complete. Please see contents in '. $this->_output . "\n";
    exit(0);

  }

  /**
   * Get a list of countries
   *
   * @return array
   */
  private function _getCountries() {
    $countries = array();
    $crawler = $this->_scraper->request('GET', self::COUNTRY_LIST_URL);
    return $crawler->filter('table#table_countries > tbody > tr')->each(function($row) {
      return trim($row->filter('td')->eq(1)->text());
    });
  }

  /**
   * Get a list of ASNs for a given country
   *
   * @param string $code Country code
   * @return array
   */
  private function _getAsns($code) {
    $crawler = $this->_scraper->request('GET', self::COUNTRY_PREFIX . $code);
    return $crawler->filter('table#asns > tbody > tr')->each(function($row) use ($code) {
      $asn = $row->filter('td')->first()->text();
      $name = $row->filter('td')->eq(1)->text();
      $adj_v4 = str_replace(',', '', $row->filter('td')->eq(2)->text());
      $route_v4 = str_replace(',', '', $row->filter('td')->eq(3)->text());
      $adj_v6 = str_replace(',', '', $row->filter('td')->eq(4)->text());
      $route_v6 = str_replace(',', '', $row->filter('td')->eq(5)->text());
      return array(
        'Asn' => $asn,
        'Country' => $code,
        'Name' => $name,
        'Adjacencies v4' => (int)$adj_v4,
        'Routes v4' => (int)$route_v4,
        'Adjacencies v6' => (int)$adj_v6,
        'Routes v6' => (int)$route_v6,
      );
    });
  }

  /**
   * Parse and validate our command line arguments
   *
   * @return void
   * @throws Exception
   */
  private function _parseArguments() {
    // We only take a single parameter.
    if (count($this->_args) !== 2) {
      $message = "Error: invalid arguments\n";
      $message .= $this->_getUsage();
      throw new \Exception($message);
    }

    // Make sure we can write to the path
    $this->_output = $this->_args[1];
    if (! is_writable(dirname($this->_output))) {
      $message = "Error: destination file is not writable\n";
      throw new \Exception($message);
    }
  }

  /**
   * Write our output file
   *
   * @param array $output
   */
  private function _writeFile($output) {
    $fp = fopen($this->_output, 'w');
    fwrite($fp, json_encode($output, JSON_PRETTY_PRINT));
    fclose($fp);
  }

  /**
   * return this applications usage information
   *
   * @return string
   */
  private function _getUsage() {
    return 'usage: ' . $this->_args[0] . " <output_filename>\n";
  }
}
