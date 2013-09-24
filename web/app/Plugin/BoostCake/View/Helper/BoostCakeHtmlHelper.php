<?php
App::uses('HtmlHelper', 'View/Helper');
App::uses('Inflector', 'Utility');

class BoostCakeHtmlHelper extends HtmlHelper {

/**
 * Overwrite HtmlHelper::useTag()
 * If $tag is `<input type="radio">` then replace `<label>` position
 * Returns a formatted existent block of $tags
 *
 * @param string $tag Tag name
 * @return string Formatted block
 */
	public function useTag($tag) {
		$args = func_get_args();

		if ($tag === 'radio') {
			$class = (isset($args[3]['class'])) ? $args[3]['class'] : 'radio';
			unset($args[3]['class']);
		}

		$html = call_user_func_array(array('parent', 'useTag'), $args);

		if ($tag === 'radio') {
			$regex = '/(<label)(.*?>)/';
			if (preg_match($regex, $html, $match)) {
				$html = $match[1] . ' class="' . $class . '"' . $match[2] . preg_replace($regex, ' ', $html);
			}
		}

		return $html;
	}

/**
 * Creates a formatted IMG element.
 *
 * This method will set an empty alt attribute if one is not supplied.
 *
 * ### Usage:
 *
 * Create a regular image:
 *
 * `echo $this->Html->image('cake_icon.png', array('alt' => 'CakePHP'));`
 *
 * Create an image link:
 *
 * `echo $this->Html->image('cake_icon.png', array('alt' => 'CakePHP', 'url' => 'http://cakephp.org'));`
 *
 * ### Options:
 *
 * - `url` If provided an image link will be generated and the link will point at
 *   `$options['url']`.
 * - `fullBase` If true the src attribute will get a full address for the image file.
 * - `plugin` False value will prevent parsing path as a plugin
 * - `data-src` For holder.js options. If `$path` is not empty, then unset `$options['data-src']`.
 *
 * @param string $path Path to the image file, relative to the app/webroot/img/ directory.
 * @param array $options Array of HTML attributes. See above for special options.
 * @return string completed img tag
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::image
 */
	public function image($path, $options = array()) {
		if (empty($path)) {
			$path = '/';
		} else {
			if (isset($options['data-src'])) {
				unset($options['data-src']);
			}
		}
		return parent::image($path, $options);
	}

}