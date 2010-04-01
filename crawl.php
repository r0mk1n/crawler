#!/usr/bin/php -q
<?php
include(dirname(__FILE__) . '/mi_crawler.php');

function crawlHelp() {
?>
crawl - a testing tool for crawling a website
Usage: crawl [OPTION] .... Uri

If you're doing any kind of serverside browser sniffing this tool helps you find
out what the user sees. Also serves to measure (individual page) response times
to help focus attention where it's needed, and stores each page for further/later
analysis

arguments:
-cache                  Use cached results if they exist? Defaults to true
-depth                  How deep to follow the links. Defaults to 2
-limit                  Maximum number of pages to request - defaults to no limit
-restricttodomain       Don't leave the domain? Defaults to true
-restricttodomainstrict Don't consider subdomains part of the domain? Defaults to false
-loglevel               How verbose/informative to be. Defaults to 2
-continue               Continue from where you left off? Defaults to false
                            If true and an index from a previous crawl is found no
                            crawling will be performed.
                            This setting is relevant if you've already crawled a site
                            and want to process the results - set to true
-useragent              The user agent string to use - defaults to
                            "MiCrawler Version X.X"
                        Can either be a full string, or one of the existing presets:
                            android, chrome, googlebot, firefox, ie6, ie7, ie8,
                            lynx, opera
-processor              The name of the processor class to run results through.
                            Defaults to the (normalized) name of the domain
                            If the class doesn't exist it only crawls
<?php
}

function parseParams ($params = array()) {
	$function = array_shift($params);
	$return = array();
	$count = count($params);
	for ($i = 0; $i < $count; $i++) {
		if (isset($params[$i])) {
			if ($params[$i]{0} === '-') {
				$key = substr($params[$i], 1);
				$return[$key] = true;
				unset($params[$i]);
				if (isset($params[++$i])) {
					if ($params[$i]{0} !== '-') {
						$return[$key] = str_replace('"', '', $params[$i]);
						unset($params[$i]);
					}
				}
			} else {
				$return[] = $params[$i];
			}
		}
	}
	return array($function, $return);
}

function crawl($uri, $params = array(), $processor = '', $processorFile = '') {
	$params = array_merge(array('continue' => false), $params);
	$results = MiCrawler::crawl($uri, $params);
	if ($results && $processorFile && is_file(dirname(__FILE__) . '/' . $processorFile)) {
		require_once(dirname(__FILE__) . '/' . $processorFile);
		$processor = ucwords($processor) . 'Processor';
		$results = call_user_func_array(array($processor, 'process'), array($results, $uri));
		echo "\n";
		foreach((array)$results as $key => $values) {
			echo $key . ' ' . count($values) . " found\n";
		}
		return;
	}
	echo count($results) . " found\n";
}

list($function, $params) = parseParams($argv);
if ($function === 'crawl.php') {
	if (empty($params[0]) && empty($params['uri'])) {
		return crawlHelp();
	}
	$processor = $processorFile = null;

	$uri = $params[0];
	extract($params);
	$parts = parse_url($uri);
	if (!$processor) {
		$processor = strtolower(str_replace(array('www.', '.com'), '', $parts['host']));
		$processorFile = $processor . '_processor.php';
		if (!file_exists(dirname(__FILE__) . '/' . $processor . '_processor.php')) {
			$processor = 'Generic';
			$processorFile = 'generic_processor.php';
		}
	}
	crawl($uri, $params, $processor, $processorFile);
}