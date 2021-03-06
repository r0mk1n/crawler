<?php
/**
 * Short description for mi_crawler.php
 *
 * Long description for mi_crawler.php
 *
 * PHP version 4 and 5
 *
 * Copyright (c) 2010, YourNameOrCompany
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2010, YourNameOrCompany
 * @link          www.yoursite.com
 * @package       crawler
 * @subpackage    crawler
 * @since         v 1.0 (05-Aug-2010)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
if (!function_exists('trace')) {
/**
 * Generate a stack trace of how you got here
 *
 * @return void
 * @access public
 */
	function trace() {
		$backtrace = debug_backtrace();
		array_shift($backtrace);
		$return = array();
		foreach($backtrace as $i => $row) {
			$row = array_merge(array('file' => '[internal]', 'line' => '??'), $row);
			$row['file'] = str_replace(ABSPATH, '', $row['file']);
			if ($row['function'] === 'call_user_func_array') {
				$return[$i - 1] = str_replace('[internal]:??', "{$row['file']}:{$row['line']}", $return[$i - 1]);
				continue;
			}
			$ref = '';
			if (!empty($row['class'])) {
				$ref .= "{$row['class']}::";
			}
			if (!empty($row['function'])) {
				$ref .= $row['function'] . ' ';
			}
			$ref .= "{$row['file']}:{$row['line']}";
			$return[$i] = $ref;
		}
		return implode("\n", $return);
	}
}

/**
 * MiCrawler class
 *
 * @uses
 * @package       crawler
 * @subpackage    crawler
 */
class MiCrawler {

/**
 * settings property
 *
 * @var array
 * @access public
 */
	public static $settings = array(
		'pagesTmpDir' => '/tmp/crawler/pages/',
		'dataTmpDir' => '/tmp/crawler/data/',
		'logPrefix' => '',
		'useragents' => array(
			'android' => 'Mozilla/5.0 (Linux; U; Android 2.1; en-us; Nexus One Build/ERD62) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17 ',
			'chrome' => 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.2 (KHTML, like Gecko) Chrome/6.0',
			'googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
			'firefox' => 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2b5) Gecko/20091204 Firefox/3.6b5',
			'ie6' => 'Mozilla/4.0 (compatible; MSIE 6.1; Windows XP)',
			'ie7' => 'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; en-US)',
			'ie8' => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; GTB6.4; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; MSSDMC2.5.2219.1)',
			'lynx' => 'Lynx (textmode)',
			'opera' => 'Opera/9.99 (Windows NT 5.1; U; pl) Presto/9.9.9'
		),

		'cache' => true,
		'depth' => 2,
		'exclude' => '(/css/|/js/)',
		'limit' => 0,
		'no-parent' => false,
		'parentDir' => false,
		'wait' => 0,
		'domain' => '',
		'restricttodomain' => true,
		'restricttodomainstrict' => false,
		'textlinks' => true,
		'loglevel' => 2,
		'continue' => false,
		'useragent' => 'MiCrawler Version 1.0',
		'nl' => "\n",
	);

/**
 * totalCount property
 *
 * @var int 0
 * @access protected
 */
	protected $_totalCount = 0;

/**
 * counters property
 *
 * @var array
 * @access protected
 */
	protected $_counters = array();

/**
 * map property
 *
 * @var array
 * @access protected
 */
	protected $_map = array();

/**
 * results property
 *
 * @var array
 * @access protected
 */
	protected $_results = array();

/**
 * settings property
 *
 * @var array
 * @access protected
 */
	protected $_settings = array();

/**
 * stack property
 *
 * @var array
 * @access protected
 */
	protected $_stack = array();

/**
 * crawl method
 *
 * @param mixed $url null
 * @param array $settings array()
 * @return void
 * @access public
 */
	static public function crawl($url = null, $settings = array()) {
		$Crawler = new MiCrawler($url, $settings);
		return $Crawler->_crawl($url, 0, $Crawler->_settings['continue']);
	}

/**
 * contents method
 *
 * @param mixed $url null
 * @param array $settings array()
 * @return void
 * @access public
 */
	static public function contents($url = null, $settings = array()) {
		$Crawler = new MiCrawler($url, $settings);
		return $Crawler->_retrieve($url);
	}

/**
 * addPagination method
 *
 * @param mixed $url null
 * @param array $settings array()
 * @return void
 * @access public
 */
	static public function addPagination($url = null, $settings = array()) {
		$Crawler = new MiCrawler($url, $settings);
		return $Crawler->_paginate($url);
	}

/**
 * tmpFile method
 *
 * @param mixed $url null
 * @param array $settings array()
 * @return void
 * @access public
 */
	static public function tmpFile($url = null, $settings = array()) {
		$Crawler = new MiCrawler($url, $settings);
		return $Crawler->_tmpFile($url);
	}

/**
 * crawer instances can only be created by calling one of the public static methods
 *
 * @param array $settings array()
 * @return void
 * @access protected
 */
	protected function __construct($url, $settings = array()) {
		$this->_settings = array_merge(MiCrawler::$settings, $settings);

		if (empty($this->_settings['domain'])) {
			$parts = parse_url($url);
			$this->_settings['domain'] = $parts['scheme'].'://'.$parts['host'];
			if ($this->_settings['domain'] === trim($url, '/')) {
				$url = '/';
			}
			$this->_settings['_globalDomain'] = preg_replace('@^https?:\/\/(www\.)?@', '', $this->_settings['domain']);
		}
		if ($url !== '/') {
			if (substr($url, -1) == '/') {
				$this->_settings['parentDir'] = $url;
			} else {
				$this->_settings['parentDir'] = preg_replace('@/[^/]*?$@', '', $url);
			}
		}

		$base = dirname(__FILE__) . '/';
		if ($this->_settings['dataTmpDir'][0] !== '/') {
			$this->_settings['dataTmpDir'] = $base . $this->_settings['dataTmpDir'];
		}

		if ($this->_settings['pagesTmpDir'][0] !== '/') {
			$this->_settings['pagesTmpDir'] = $base . $this->_settings['pagesTmpDir'];
		}

		if (isset($this->_settings['useragents'][strtolower($this->_settings['useragent'])])) {
			$this->_settings['useragent'] = $this->_settings['useragents'][strtolower($this->_settings['useragent'])];
		}
	}

/**
 * crawl method
 *
 * @param mixed $url null
 * @param int $depth 0
 * @param bool $continue false
 * @return void
 * @access protected
 */
	protected function _crawl($url = null, $depth = 0, $continue = false) {
		$fullUrl = $url;

		if ($this->_settings['cache'] && $depth === 0) {
			$cacheFile = dirname($this->_settings['pagesTmpDir']) . '/' . $this->_settings['_globalDomain'] . '.json';
			if (file_exists($cacheFile) && filesize($cacheFile) < 1000000) {
				$this->_map = json_decode(file_get_contents($cacheFile), true);
				if (!$continue) {
					return $this->_map;
				}
			}
		}

		if ($this->_settings['domain'] === trim($url, '/')) {
				$url = $this->_settings['domain'];
		} elseif ($url[0] === '/') {
			$fullUrl = $this->_settings['domain'] . $url;
		}

		$this->_logprefix($url);

		$directResults = $this->_index($fullUrl);

		if (!$this->_settings['depth'] || $depth < $this->_settings['depth']) {
			if (empty($this->_results[$depth + 1])) {
				$this->_results[$depth + 1] = (array)$directResults;
			} else {
				$this->_results[$depth + 1] = array_merge($this->_results[$depth + 1], (array)$directResults);
			}
			$this->_results[$depth + 1] = array_unique($this->_results[$depth + 1]);
		}

		if ($depth === 0) {
			$max = $this->_settings['depth'];
			if (!$max) {
				$max = 999;
			}
			for($i = 1; $i <= $max; $i++) {
				if (empty($this->_results[$i])) {
					break;
				}

				$this->_logprefix($i);
				foreach($this->_results[$i] as $linked) {
					$_return = $this->_crawl($linked, $i);
					if ($_return === false) {
						break (2);
					}
				}
				if (isset($this->_results[$i + 1])) {
					$this->_results[$i + 1] = array_unique($this->_results[$i + 1]);
					for($j = 1; $j <= $i; $j++) {
						$this->_results[$i + 1] = array_diff($this->_results[$i + 1], $this->_results[$j]);
					}
				}
				$this->_logPrefixPop();
			}
			if (!$this->_map) {
				if ($url === '/') {
					$url = $this->_settings['domain'];
				}
				$cacheFile = $this->_tmpFile($url);
				$this->_map[$url] = $cacheFile;
			}
			ksort($this->_map);
			file_put_contents($cacheFile, json_encode($this->_map));
			return $this->_map;
		}
		$this->_logPrefixPop();
		return $directResults;
	}

/**
 * index method
 *
 * @param mixed $url null
 * @param bool $paginate false
 * @return void
 * @access protected
 */
	protected function _index($url = null, $paginate = false) {
		$contents = $this->_retrieve($url);
		if ($contents === false) {
			return false;
		}
		$parts = parse_url($url);
		$links = $this->_extractLinks($contents, $parts['scheme'] . '://' . $parts['host']);
		if ($paginate) {
			$links = array_merge($links, $this->_paginate($url, $contents, $links));
		}
		return $links;
	}

/**
 * extract method
 *
 * @param mixed $text
 * @param string $pattern '//'
 * @return void
 * @access protected
 */
	protected function _extract($text, $pattern = '//') {
		if ($this->_settings['cache']) {
			$cacheFile = $this->_settings['dataTmpDir'] . md5($pattern . $text);
			if (file_exists($cacheFile)) {
				return json_decode(file_get_contents($cacheFile), true);
			}
		}
		preg_match_all($pattern, $text, $return);

		if ($return[0]) {
			$return = array_unique(array_pop($return));
			sort($return);
		} else {
			$return = array();
		}

		if ($this->_settings['cache'] && !empty($cacheFile)) {
			$dir = dirname($cacheFile);
			if (!is_dir($dir)) {
				`mkdir -p $dir`;
			}
			file_put_contents($cacheFile, json_encode($return));
		}
		return $return;
	}

/**
 * exclude method
 *
 * @param mixed $url
 * @return void
 * @access protected
 */
	protected function _exclude($url) {
		static $pattern;
		if (!$pattern) {
			$pattern = $this->_settings['exclude'];
		}

		if ($this->_settings['parentDir']) {
			if (strpos($url, $this->_settings['parentDir']) === false) {
				return false;
			}
		}
		return !preg_match('@' . $this->_settings['exclude'] . '@', $url);
	}

/**
 * filter method
 *
 * @param mixed $array
 * @param string $pattern ''
 * @return void
 * @access protected
 */
	protected function _filter($array, $pattern = '') {
		$_pattern;
		if ($pattern) {
			$_pattern = $pattern;
		}
		if (is_string($array)) {
			if (preg_match($pattern, $array)) {
				return preg_replace($pattern, '', $array);
			};
			return false;
		}
		return array_filter($array, array(&$this, '_filter'));
	}

/**
 * extractLinks method
 *
 * @param mixed $text
 * @return void
 * @access protected
 */
	protected function _extractLinks($text, $domain) {
		$this->_currentDomain = $domain;
		if ($this->_settings['restricttodomain']) {
			if ($this->_settings['restricttodomainstrict']) {
				$subPattern = '(?:' . preg_quote($this->_settings['domain'], '/') . '|)(\/';
			} else {
				$subPattern = '(?:https?\:\/\/[0-9a-zA-Z_\.]*' . preg_quote($this->_settings['_globalDomain']) . '|)(\/';
			}
			$links = $this->_extract($text, '/<a[^>]*href\s*=\s*(["\'])?' . $subPattern . '[^<># ]+)[^> ]*\1/i', false);
		} else {
			$links = $this->_extract($text, '/<a[^>]*href\s*=\s*(["\'])?([http.*?|\/][^># ]+)[^<> ]*\1/i', false);
		}
		if ($this->_settings['textlinks']) {
			if ($this->_settings['restricttodomain']) {
				if ($this->_settings['restricttodomainstrict']) {
					$subPattern = '(' . preg_quote($this->_settings['domain'], '/') . '\/';
				} else {
					$subPattern = '(https?\:\/\/[0-9a-zA-Z_\.]*' . preg_quote($this->_settings['_globalDomain']) . '\/';
				}
				$pattern = '/' . $subPattern . '[^<># "\']*)/i';
			} else {
				$pattern = '/(https?:\/\/[^<># "\']*)/i';
			}

			$llinks = $this->_extract($text, $pattern, false);
			if ($llinks) {
				$links = array_unique(array_merge($links, $llinks));
			}
		}
		$links = array_unique(array_map(array('MiCrawler', '_uniqueUrl'), $links));
		$links = array_filter($links, array('MiCrawler', '_exclude'));
		return $links;
	}

/**
 * paginate method
 *
 * @param mixed $url
 * @param string $contents ''
 * @param array $links array()
 * @return void
 * @access protected
 */
	protected function _paginate($url, $contents = '', $links = array()) {
		$this->_log("checking for page links for $url", 2);

		if (!$contents) {
			$contents = $this->_retrieve($url);
		}
		if (!strpos($contents, 'class="pagination">') || strpos($contents, 'class="pagination"></div>')) {
			$this->_log("no page links found", 3);
			return array();
		}

		$base = preg_replace('@https?://[^/]*@', '', $url);
		if (!$links) {
			$parts = parse_url($url);
			$links = $this->_extractLinks($contents, $parts['scheme'] . '://' . $parts['host']);
		}

		$pages = array();
		foreach($links as $i => $link) {
			if (strpos($link, $base) === 0) {
				$page = trim(str_replace($base, '', $link), '/');
				if ($page) {
					$pages[$link] = (int)$page;
				}
			}
		}

		if (count($pages) > 1) {
			$max = max($pages);
		} elseif (!$pages) {
			return array();
		} else	{
			$max = 2;
		}
		$pages = array_flip($pages);
		$this->_log("$max page links found", 3);

		$pattern = str_replace("/$max/", '/%page%/', $pages[$max]);
		for($i = 2; $i <= $max; $i++) {
			$pageUrl = str_replace('/%page%/', "/$i/", $pattern);
			$urls[] = $pageUrl;
		}
		return $urls;
	}

/**
 * retrieve method
 *
 * @param mixed $url
 * @return void
 * @access protected
 */
	protected function _retrieve($url) {

		static $counter = 0;

		static $realCounter = 0;

		static $lastRequest = null;

		$this->_log(' (' . $counter++ . ')', 0, true, false);
		$cacheFile = $this->_tmpFile($url);
		if ($this->_settings['cache']) {
			$this->_map[$url] = $cacheFile;
			if (file_exists($cacheFile)) {
				$this->_logTime(0, 0);
				$this->_log("\t$cacheFile", 3);
				return file_get_contents($cacheFile);
			}
		}


		if ($this->_settings['limit'] && $realCounter > $this->_settings['limit']) {
			$this->_log("Retrieval limit reached");
			return false;
		}

		if ($this->_settings['wait']) {
			$now = microtime(true);
			if ($lastRequest) {
				$diff = $now - $lastRequest + $this->_settings['wait'];
				if ($diff > 0) {
					$diff = round($diff);
					$this->_log("Sleeping for $diff seconds", 2, false, false);
					sleep($diff);
				}
			}
		}

		$realCounter++;

		$start = microtime(true);
		$ch = curl_init();

		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_AUTOREFERER => true,
			CURLOPT_CONNECTTIMEOUT => 40,
			CURLOPT_FAILONERROR => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 45,
			CURLOPT_USERAGENT => $this->_settings['useragent']
		));

		$contents = curl_exec($ch);
		$this->_logTime($start, microtime(true));
		$info = curl_getinfo($ch);
		$lastRequest = microtime(true);

		if ($info['http_code'] != 200 && $info['http_code'] > 302) {
			$this->_log("page not found, code " . $info['http_code'], 1);
			$contents = '';
		}

		$dir = dirname($cacheFile);
		if (!is_dir($dir)) {
			`mkdir -p $dir`;
		}
		$this->_log("writing cache", 2);
		$this->_log("\t$cacheFile", 3);
		file_put_contents($cacheFile, $contents);
		return $contents;
	}

/**
 * tmpFile method
 *
 * @param mixed $url
 * @return void
 * @access protected
 */
	protected function _tmpFile($url) {
		$parts = parse_url($url);
		$hash = md5($url);
		$return = $this->_settings['pagesTmpDir'] . $parts['host'] . '/' . $hash[0] . '/' . $hash[1] . '/' . $hash;

		/* temporary */
		$old = $this->_settings['pagesTmpDir'] . $parts['host'] . '/' . $hash;
		if (file_exists($old)) {
			$dir = dirname($return);
			`mkdir -p $dir`;
			`mv $old $return`;
		}
		/* temporary end */
		return $return;
	}

/**
 * Normalize urls to prevent requesting the same url twice.
 * 	Strips trailing slashes
 * 	replaces &amp; with &
 *
 * The latter is because it's shorter to store/work with.
 *
 * @TODO - get params remove them, inlcude them, ignore them?
 * @see MiCrawler::_extractLinks
 * @param mixed $url
 * @return void
 * @access protected
 */
	protected function _uniqueUrl($url) {
		$url = str_replace('&amp;', '&', $url);
		if ($url !== '/') {
			$url = rtrim($url, '/');
		}
		if (empty($url) || $url[0] === '/') {
			$url = $this->_currentDomain . $url;
		}
		return $url;
	}

/**
 * logPrefix method
 *
 * @param mixed $prefix null
 * @param bool $reset false
 * @return void
 * @access protected
 */
	protected function _logPrefix($prefix = null, $reset = false) {
		if ($reset) {
			return $this->_settings['logPrefix'] = array($prefix);
		}
		$this->_settings['logPrefix'][] = $prefix;
	}

/**
 * logPrefixPop method
 *
 * @return void
 * @access protected
 */
	protected function _logPrefixPop() {
		array_pop($this->_settings['logPrefix']);
	}

/**
 * log method
 *
 * @param mixed $message null
 * @param int $messageLevel 1
 * @param bool $prefix false
 * @param bool $nl true
 * @return void
 * @access protected
 */
	protected function _log($message = null, $messageLevel = 1, $prefix = false, $nl = true) {
		if ($messageLevel > $this->_settings['loglevel']) {
			return;
		}
		$nl = $nl?$this->_settings['nl']:'';
		if ($prefix) {
			if (!empty($this->_settings['logPrefix'])) {
				echo implode($this->_settings['logPrefix'], ' » ');
			}
			echo $message . $nl;
			return;
		}
		if (!empty($this->_settings['logPrefix'])) {
			echo str_repeat("\t", count($this->_settings['logPrefix']) - 1);
		}
		echo $message . $nl;
	}

/**
 * logTime method
 *
 * @param mixed $start
 * @param mixed $end
 * @return void
 * @access protected
 */
	protected function _logTime($start, $end) {
		if ($this->_settings['loglevel'] > 0) {
			if ($start === 0) {
				echo ' found in cache';
			} else {
				echo ' ' . abs(round($end - $start, 4)) . 's';
			}
		}
		if ($this->_settings['loglevel'] > -1) {
			echo $this->_settings['nl'];
		}
	}
}