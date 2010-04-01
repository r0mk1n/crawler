# Crawler

A standalone php script for crawling a site.

This script makes it relatively easy to 
 * find broken links
 * find slow pages
 * simulate semi-real usage (well, that's a stretch - but it's better than pinging the same page 20 times)
 * Check any browser sniffing you might be doing

## Usage

It's a pretty simple script, to see the full help call with no parameters

	$ . crawl


	$ . crawl http://ad7six.com
	/ (0) 1.2671s
			writing cache
	/ » 1 » /contact (1) 1.2357s
			writing cache
	/ » 1 » /entries/index/2006 (2) 1.2564s
			writing cache
	/ » 1 » /entries/index/2007 (3) 1.2598s
			writing cache
	/ » 1 » /entries/index/2008 (4) 1.0801s
			writing cache
	/ » 1 » /entries/index/2009/11 (5) 1.0758s
			writing cache
	...

