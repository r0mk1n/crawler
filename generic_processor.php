<?php
/**
 * GenericProcessor class
 *
 * @uses
 * @package       crawler
 * @subpackage    crawler
 */
class GenericProcessor {

	static public $settings = array(
		'nl' => "\n"
	);
/**
 * process method
 *
 * Input is a key value array of
 * 	url => tmp file
 *
 * @param mixed $input array()
 * @param array $params array()
 * @return void
 * @access public
 */
	static function process($input, $params = array()) {
		$return = self::_preProcess($input);
		// ... Stub
		self::log('Import finished');
		return $return;
	}

/**
 * preProcess method
 *
 * Store a tidy-ed version of the page for further analysis. Using tidy makes the output easier
 * to read as well as then allowing it to be treated as xml
 *
 * @param mixed $rows
 * @param array $params array()
 * @return void
 * @access protected
 */
	static protected function _preProcess($rows, $params = array()) {
		$return = array();
		foreach($rows as $row => &$file) {
			self::log($row);
			if (!file_exists($file)) {
				self::log('Cache file not found');
				continue;
			}
			$_ = '';
			$_file = $file;
			$contents = file_get_contents($file);

			if (file_exists($file . '.preprocessed')) {
				self::log("File $file.preprocessed exists");
			} else {
				$contents = preg_replace("@<script[^>]*>.*?</script>@s", '', $contents);
				$contents = preg_replace("@\s*<!--.*?-->\s*@s", '', $contents);
				file_put_contents($file . '.preprocessed', $contents);
				$contents = `tidy -asxhtml -utf8 -modify --break-before-br y --clean y --drop-empty-paras y --drop-font-tags y -i --quiet y --tab-size 4 --wrap 1000 - < $file.preprocessed 2>/dev/null`;

				self::log("Writing $file.preprocessed");
				file_put_contents($file . '.preprocessed', $contents);
			}
			$return[$row] = 'processed';
		}
		return $return;
	}

/**
 * log method
 *
 * @param mixed $message null
 * @param bool $newLine true
 * @return void
 * @access protected
 */
	protected function log($message = null, $newLine = true) {
		if ($newLine) {
			echo self::$settings['nl'];
		}
		echo $message;
		flush();
	}
}