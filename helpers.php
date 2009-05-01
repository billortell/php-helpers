<?php
/**
 * php-helpers
 * (c) 2009 Jason Frame [jason@onehackoranother.com]
 */

//
// These constants define the locations of your site's static assets.
// Define your own prior to inclusion of this lib or accept the defaults below.
// STATIC_ROOT/ will be prepended to each asset directory when generating
// asset paths.

if (!defined('STATIC_ROOT'))        define('STATIC_ROOT', '');
if (!defined('STATIC_JS_DIR'))      define('STATIC_JS_DIR', 'javascripts');
if (!defined('STATIC_IMAGE_DIR'))   define('STATIC_IMAGE_DIR', 'images');
if (!defined('STATIC_CSS_DIR'))     define('STATIC_CSS_DIR', 'stylesheets');

//
// Helper helpers!

/**
 * Parse a selector of the form #foo.bar.baz into constituent ID and classes.
 * An array argument will be returned unchanged.
 */
function parse_simple_selector($s) {
    if (!is_array($s)) {
        preg_match('/^(#([\w-]+))?((\.[\w-]+)*)$/', $s, $matches);
        $s = array();
        if (!empty($matches[2])) $s['id'] = $matches[2];
        if (!empty($matches[3])) $s['class'] = trim(str_replace('.', ' ', $matches[3]));
    }
    return $s;
}

/**
 * Turn some representation of a URL into a string.
 * Scalar parameters are coerced to strings and returned.
 * Any other argument will be passed to url_for(), which should be implemented by
 * your application.
 */
function parse_url($u) {
    return is_scalar($u) ? (string) $u : url_for($u);
}

function is_enumerable($thing) {
    return is_array($thing) || is_object($thing);
}

//
//

function h($html, $q = ENT_QUOTES) {
    return htmlentities($html, $q);
}

/**
 * Create an image tag.
 *
 * i('foo.png', array('alt' => 'Hello'))
 * i('bar.png', '#my-image')
 * i('baz.gif', '#my-image.my-class', array('width' => 500))
 */
function i($src, $options_or_selector = array(), $options = array()) {
    $options += parse_simple_selector($options_or_selector);
    $options['src'] = url_for_image($src);
    $options += array('alt' => '');
    return empty_tag('img', $as, $options);
}

function link_to($html, $url, $options = array()) {
    $options['href'] = parse_url($url);
    return tag('a', $html, $options);
}

//
// Asset URLs

function url_for_image($image) {
    return url_for_asset($image, STATIC_IMAGE_DIR);
}

function url_for_stylesheet($stylesheet) {
    return url_for_asset($stylesheet, STATIC_CSS_DIR);
}

function url_for_javascript($js) {
    return url_for_asset($js, STATIC_JS_DIR);
}

/**
 * Returns the URL for static asset $what
 *
 * If $what is an absolute URI/path, or a relative path starting with "./",
 * it is returned unmodified. Otherwise, a static asset URL is generated based
 * on STATIC_ROOT.
 */
function url_for_asset($what, $where) {
    if (preg_match('%^(https?://|\.?/)%', $what)) {
        return $what;
    } else {
        return STATIC_ROOT . "/$where/$what";
    }
}

//
// Tag Helpers

function stylesheet_link_tag($css, $options = array()) {
    $options['href'] = url_for_stylesheet($css);
    $options['rel'] = 'stylesheet';
    $options['type'] = 'text/css';
    return tag('link', '', $options);
}

function javascript_include_tag($js, $options = array()) {
    $options['src'] = url_for_javascript($js);
    $options['type'] = 'text/javascript';
    return tag('script', '', $options);
}

function tag($tag, $content, $attribs = array()) {
    $attribs = attribute_list($attribs);
    return "<{$tag}{$attribs}>{$content}</{$tag}>";
}

function empty_tag($tag, $attribs = array()) {
    $attribs = attribute_list($attribs);
    return "<{$tag}{$attribs}/>";
}

function attribute_list($attribs) {
    $out = '';
    foreach ($attribs as $k => $v) {
	    $v = h($v);
	    $out .= " $k='$v'";
    }
    return $out;
}

//
// Input Helpers

function hidden_field_tag($name, $value, $options = array()) {
    return empty_tag('input', array(
        'type'  => 'hidden',
        'name'  => $name,
        'value' => $value
    ) + $options);
}

function hidden_field_tags($array, $prefix = '') {
    $html = '';
    foreach ($array as $k => $v) {
        $name = strlen($prefix) ? "{$prefix}[$k]" : $k;
        if (is_enumerable($v)) {
            $html .= hidden_field_tags($v, $name);
        } else {
            $html .= hidden_field_tag($name, $v);
        }
    }
    return $html;
}

function text_field_tag($name, $value = '', $options = array()) {
    return empty_tag('input', array(
        'type'  => 'text',
        'name'  => $name,
        'value' => $value
    ) + $options);
}

function password_field_tag($name, $value = '', $options = array()) {
    return empty_tag('input', array(
        'type'  => 'password',
        'name'  => $name,
        'value' => $value
    ) + $options);
}

function file_field_tag($name, $options = array()) {
    return empty_tag('input', array(
        'type'  => 'file',
        'name'  => $name
    ) + $options);
}

function check_box_tag($name, $checked = false, $options = array()) {
    $options['type'] = 'checkbox';
    $options['name'] = $name;
    $options['value'] = 1;
    if ($checked) $options['checked'] = 'checked';
    return hidden_field_tag($name, 0) . empty_tag('input', $options);
}

function radio_button_tag($name, $value, $current_value = null, $options = array()) {
    $options['type'] = 'radio';
    $options['name'] = $name;
    $options['value'] = $value;
    if ($value == $current_value || $current_value === true) $options['checked'] = 'checked';
    return empty_tag('input', $options);
}

function select_tag($name, $values, $selected = null, $options = array()) {
    $option_string = '';
    foreach ($values as $v) {
        $v = h($v);
        $s = ($selected !== null && $selected == $v) ? ' selected="selected"' : '';
        $option_string .= "<option{$sel}>{$v}</option>\n";
    }
    $options['name'] = $name;
    return tag('select', $option_string, $options);
}

function key_select_tag($name, $values, $selected = null, $options = array()) {
    $option_string = '';
    foreach ($values as $k => $v) {
        $s = ($selected !== null && $selected == $k) ? ' selected="selected"' : '';
        $k = h($k);
        $v = h($v);
        $option_string .= "<option value='$k'{$sel}>{$v}</option>\n";
    }
    $options['name'] = $name;
    return tag('select', $option_string, $options);
}

function text_area_tag($name, $value, $options = array()) {
    $options['name'] = $name;
    return tag('textarea', $value, $options + array('rows' => 6, 'cols' => 50));
}
?>