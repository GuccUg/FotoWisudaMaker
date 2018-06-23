<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2009, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Parser Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Parser
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/parser.html
 */
class MY_Parser extends CI_Parser {
	var $l_delim = '{';
	var $r_delim = '}';
	var $object;

	//Added by ~yk~
	var $_vars = array();
	var $_vars_element = array();
	var $_ajax_filter = array();
	var $_templates = array();

	function __construct() {
		//AJAX Related
		define('AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
		$CI =& get_instance();
	}

	function set_var($varname, $string = false, $data = false)
	{
		if (is_string($varname))
		{
			$this->_vars[$varname] = is_array($data) ?
				$this->parse($string, $data, TRUE)
				: $string;
		}
		elseif (is_array($varname))
		{
			foreach ($varname as $k=>$v)
			{
				$this->_vars[$k] = is_array($v) ?
					$this->parse($v[0], $v[1], TRUE)
					: $v;
			}
		}
		else
		{
			return FALSE;
		}
	}

	function append_var($varname, $string = false, $data = array())
	{
		if (is_string($varname))
		{
			if (is_array($data)) {
				$this->_vars[$varname] =
					(isset($this->_vars[$varname])?$this->_vars[$varname]:'')
					.$this->parse($string, $data, TRUE);
			} else {
				$this->_vars[$varname] =
					(isset($this->_vars[$varname])?$this->_vars[$varname]:'')
					.$string;
			}

		}
		elseif (is_array($varname))
		{
			foreach ($varname as $k=>$v)
			{
				$this->_vars[$k] = (isset($this->_vars[$k])?$this->_vars[$k]:'').$v;
			}
		}
		else
		{
			return FALSE;
		}
	}

	function prepend_var($varname, $string = false, $data = array())
	{
		if (is_string($varname))
		{
			if (is_array($data)) {
				$this->_vars[$varname] = $this->parse($string, $data, TRUE).(isset($this->_vars[$varname])?$this->_vars[$varname]:'');
			} else {
				$this->_vars[$varname] = $string.(isset($this->_vars[$varname])?$this->_vars[$varname]:'');
			}

		}
		elseif (is_array($varname))
		{
			foreach ($varname as $k=>$v)
			{
				$this->_vars[$k] = $v.(isset($this->_vars[$k])?$this->_vars[$k]:'');
			}
		}
		else
		{
			return FALSE;
		}
	}

	//For HTML output
	function render($template)
	{
		isset($this->_templates[$template]) AND $template = $this->_templates[$template];
		return $this->parse($template, $this->_vars);
	}

	//For AJAX output (in JSON)
	function ajax($custom_vars = array())
	{
		$response = array('response'=>array(), 'alert'=>array());
		foreach($this->_ajaxvars as $k=>$v) {
			$response['response'][$k]['html'] = $this->_vars[$v];
		}
		foreach($this->_ajaxcontents as $k=>$v) {
			$response['response'][$k]['html'] = $v;
		}
		if (is_array($custom)) {
			foreach ($custom as $k=>$v) if ($k != 'response') $response[$k] = $v;
		} else {
			$response['custom'] = $custom;
		}
		$CI->output->set_output('yk!'.json_encode($response));
	}

	//automatically choose between HTML or AJAX output
	function renderajax($template, $ajax_custom_vars)
	{
		if (AJAX)
		{
			$this->ajax($ajax_custom_vars);
		}
		else
		{
			$this->render($template);
		}
	}

	//Directly write a text as an output without parsing any variables
	function output($text) {
		$CI->output->set_output($text);
	}

	// ~yk~ ----*/

	/**
	 *  Parse a template
	 *
	 * Parses pseudo-variables contained in the specified template,
	 * replacing them with the data in the second param
	 *
	 * @access	public
	 * @param	string
	 * @param	array
	 * @param	bool
	 * @return	string
	 */
	function parse($template, $data = FALSE, $return = FALSE)
	{
		$CI =& get_instance();
		$template = $CI->load->view($template, $data, TRUE);

		if ($template == '')
		{
			return FALSE;
		}

		if (is_array($data))
		{
			foreach ($data as $key => $val)
			{
				if (is_array($val))
				{
					$template = $this->_parse_pair($key, $val, $template);
				}
				elseif (!is_object($val))
				{
					$template = $this->_parse_single($key, (string)$val, $template);
				}
			}
		}

		if ($return == FALSE)
		{
			$CI->output->append_output($template);
		}

		return $template;
	}

	// --------------------------------------------------------------------

	/**
	 *  Set the left/right variable delimiters
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	function set_delimiters($l = '{', $r = '}')
	{
		$this->l_delim = $l;
		$this->r_delim = $r;
	}

	// --------------------------------------------------------------------

	/**
	 *  Parse a single key/value
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function _parse_single($key, $val, $string)
	{
		return str_replace($this->l_delim.$key.$this->r_delim, $val, $string);
	}

	// --------------------------------------------------------------------

	/**
	 *  Parse a tag pair
	 *
	 * Parses tag pairs:  {some_tag} string... {/some_tag}
	 *
	 * @access	private
	 * @param	string
	 * @param	array
	 * @param	string
	 * @return	string
	 */
	function _parse_pair($variable, $data, $string)
	{
		if (FALSE === ($match = $this->_match_pair($string, $variable)))
		{
			return $string;
		}

		$str = '';
		foreach ($data as $row)
		{
			$temp = $match['1'];
			foreach ($row as $key => $val)
			{
				if ( ! is_array($val))
				{
					$temp = $this->_parse_single($key, $val, $temp);
				}
				else
				{
					$temp = $this->_parse_pair($key, $val, $temp);
				}
			}

			$str .= $temp;
		}

		return str_replace($match['0'], $str, $string);
	}

	// --------------------------------------------------------------------

	/**
	 *  Matches a variable pair
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	mixed
	 */
	function _match_pair($string, $variable)
	{
		if ( ! preg_match("|".$this->l_delim . $variable . $this->r_delim."(.+?)".$this->l_delim . '/' . $variable . $this->r_delim."|s", $string, $match))
		{
			return FALSE;
		}

		return $match;
	}

}
// END Parser Class

/* End of file Parser.php */
/* Location: ./system/libraries/Parser.php */