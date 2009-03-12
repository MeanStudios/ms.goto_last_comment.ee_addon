<?php  if ( ! defined('EXT')) exit('No direct script access allowed');
/**
 * Goto Comment
 *
 * Will automatcially scroll the page down to the last comment after it's been posted.
 *
 * @author		Cody Lundquist
 * @license		http://creativecommons.org/licenses/by-sa/3.0/
 * @link		http://www.codysplace.com
 * @since		Version 1.1.0
 * @filesource
 *
 * This work is licensed under the Creative Commons Attribution-Share Alike 3.0 Unported.
 * To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/3.0/
 * or send a letter to Creative Commons, 171 Second Street, Suite 300,
 * San Francisco, California, 94105, USA.
 *
 */
class Goto_last_comment_ext {

	var $settings		= array();
	var $name			= 'Goto Last Comment';
    var $class_name     = 'Goto_last_comment_ext';
	var $version		= '1.1.0';
	var $description	= 'Will automatcially scroll the page down to the last comment after it\'s been posted.';
	var $settings_exist	= 'n';
	var $docs_url		= 'http://expressionengine.com/forums/viewthread/104390/';

	function Goto_last_comment_ext($settings='')
	{
		$this->settings = $settings;
    }

    function activate_extension($settings='')
	{
		global $DB;

		// Delete old hooks
		$DB->query("DELETE FROM exp_extensions
		            WHERE class = '{$this->class_name}'");

		// Add new extensions
		$ext_template = array(
			'class'    => $this->class_name,
			'settings' => '',
			'priority' => 10,
			'version'  => $this->version,
			'enabled'  => 'y'
		);

		$extensions = array(

			array('hook'=>'comment_form_hidden_fields', 'method'=>'comment_form_hidden_fields'),
			array('hook'=>'comment_entries_tagdata',  'method'=>'comment_entries_tagdata')
		);

		foreach($extensions as $extension)
		{
			$ext = array_merge($ext_template, $extension);
			$DB->query($DB->insert_string('exp_extensions', $ext));
		}
	}

	function update_extension($current='')
	{
		global $DB;

		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

		if ($current < '1.1.0')
		{
			// make settings site-specific
			$settings = $this->get_all_settings();
			$all_settings = array();
			$sites = $DB->query('SELECT site_id FROM exp_sites');
			foreach($sites->result as $site)
			{
				$all_settings[$site['site_id']] = $settings;
			}

			// Add new hooks
			$this->activate_extension($all_settings);
			return;
		}

		// update the version
		$DB->query("UPDATE exp_extensions
		            SET version = '".$DB->escape_str($this->version)."'
		            WHERE class = '{$this->class_name}'");
	}

	// --------------------------------------------------------------------

	function disable_extension()
	{
		global $DB;

		$DB->query("UPDATE exp_extensions
		            SET enabled='n'
		            WHERE class='{$this->class_name}'");
	}

	/**
	 * Adds script tags to the head of CP pages
	 *
	 * We add the jQuery libraries to the top of the head tag to ensure that they
	 * are before any other javascript that could use the libraries.
	 *
	 * @param string $html Final html of the control panel before display
	 * @return string Modified HTML
	 */
	function comment_form_hidden_fields($hidden_fields)
	{
		global $EXT, $IN;

		if($EXT->last_call !== FALSE)
		{
			$hidden_fields = $EXT->last_call;
		}

		$hidden_fields['RET'] = $hidden_fields['RET'] . "#last_comment";
		return $hidden_fields;
	}

	// --------------------------------------------------------------------

	/**
	 * Modifies the comment entries to include an anchor on the last comment.
	 *
	 * @param string $tagdata The tag data
	 * @param array $row The data for the current comment
	 * @return $tagdata string Modified tagdata
	 */
	function comment_entries_tagdata($tagdata, $row)
	{
		global $EXT, $IN, $DB;

		$entry_id = (! isset($IN->SEGS[sizeof($IN->SEGS)]) ? '0' : $IN->SEGS[sizeof($IN->SEGS)]);

		$query = $DB->query("SELECT a.comment_id, b.entry_id
							 FROM exp_comments a, exp_weblog_titles b
							 WHERE b.url_title = '".$entry_id."'
							 AND a.entry_id = b.entry_id
							 ORDER BY a.comment_id DESC
							 LIMIT 1");

		if($EXT->last_call !== FALSE)
		{
			$tagdata = $EXT->last_call;
		}
		if ($row["comment_id"] == $query->row["comment_id"])
		{
			$tagdata = str_replace(LD.'last_comment'.RD, '<a id="last_comment"></a>', $tagdata);
		} else {
			$tagdata = str_replace(LD.'last_comment'.RD, '', $tagdata);
		}

		return $tagdata;
	}

	// --------------------------------------------------------------------

}
// END CLASS Goto_comment_ext

/* End of file ext.goto_comment_ext.php */
/* Location: ./system/extensions/ext.goto_comment_ext.php */