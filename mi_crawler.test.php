<?php
include str_replace('.test.php', '.php', __FILE__);

$results = MiCrawler::crawl('http://www.ad7six.com', array('continue' => false));
echo count($results) . " found\n";