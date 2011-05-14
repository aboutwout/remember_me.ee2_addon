<?php if ( ! defined('EXT')) exit('Invalid file request');

if (session_id() == '')
{
   session_start();
}

$plugin_info = array(
	'pi_name'			=> 'Remember Me',
	'pi_version'		=> '1.0 beta',
	'pi_author'			=> 'Wouter Vervloet',
	'pi_author_url'		=> 'http://www.baseworks.nl/',
	'pi_description'	=> 'Save entries for a user to do something with them on (another) page.',
	'pi_usage'			=> Remember_me::usage()
);

/**
* Remember Me Plugin class 
*
* @package		  remember_me.ee2_addon
* @version			1.0 beta
* @author			  Wouter Vervloet <wouter@baseworks.nl>
* @license			http://creativecommons.org/licenses/by-sa/3.0/
*/
class Remember_me {

	/**
	* Plugin return data
	*
	* @var	string
	*/
	var $return_data;

	/**
	* Remember me storage
	*
	* @var	array
	*/
  var $_storage = array();
  
	/**
	* Current site
	*
	* @var	integer
	*/
  private $_current_site = 1;

  private $_entry_id = 0;
  private $_channel = FALSE;
  private $_return = FALSE;
  private $_reverse = FALSE;
  private $_list = 'default';
  private $_show_empty = FALSE;
  private $_set = FALSE;
  private $_append = FALSE;
  private $_clear = FALSE;

  private $_dynamic_params = array();

	/**
	* Member id of the currently logged in member
	*
	* @var	integer
	*/
  private $_member_id = 0;

	/**
	* Array of parameters that are allowed to be set dynamically
	*
	* @var	array
	*/
  private $_allowed_dynamic_params = array('entry_id', 'return', 'channel', 'reverse', 'list', 'append', 'member_id', 'set', 'show_empty', 'clear');


	/**
	* PHP4 Constructor
	*
	* @see	__construct()
	*/
	function Remember_me()
	{
		$this->__construct();
	}
  // END Remember_me

	/**
	* PHP5 Constructor
	* @return void
	*/
	function __construct()
	{

		$this->EE =& get_instance();
				
    $this->_storage = isset($_SESSION[strtolower(__CLASS__)]) ? $_SESSION[strtolower(__CLASS__)] : array();

    $this->_fetch_all_params();

		$this->EE->TMPL->log_item(__CLASS__ . ': Fetched storage ('.implode(',', array_keys($this->_storage)).')');

    $this->_current_site = $this->EE->config->item('site_id');
	}
	// END __construct
	
	
	/**
	* Save entries to session storage
	*
	* @return void
	*/
	function set()
	{

    if ($entry = $this->_entry_exists($this->_entry_id))
    {
      $this->_storage[$this->_entry_id] = $entry;
      $this->_save_storage();      
    }
    else
    {
      $this->_redirect();      
    }
	  
	}
	// END set
	
	/**
	* Retrieve entries from to session storage
	*
	* @return mixed [string|boolean]
	*/
	function get()
	{
	  if ($this->_entry_id !== FALSE)
	  {
  	  // entry_id parameter has been specified
	    return $this->_get_entry($this->_storage);
	  }

	  if ($this->_channel !== FALSE)
	  {
  	  // channel parameter has been specified
	    return $this->_get_where_channel($this->_storage);
	  }

    return $this->_get_all();

	}
	// END get
	
	/**
	* Clear entries from session storage
	*
	* @return void
	*/
	function clear()
	{
	  	  
	  if( $this->_entry_id !== FALSE )
	  {
  	  // entry_id parameter has been specified
	    $this->_clear_entry( $this->_entry_id );
	  }
	  else if ($this->_channel !== FALSE)
	  {
	    // channel parameter has been specified
	    $this->_clear_where_channel($this->_channel);
	  }
	  else
	  {  
	    // clear the entire storage
      $this->_clear_all(); 
	  }
	  
	  $this->_redirect();	  

	}
	// END clear
	
	/**
	* Save the current session storage to the database
	*
	* @return void
	*/
	function save()
	{

	  $lists = $this->_fetch_from_database();
	  
    $current = isset($lists[$this->_list]) ? $lists[$this->_list] : array();
    
    if ($this->_entry_id)
    {
      $entry = $this->_entry_exists($this->_entry_id);
      $items = array($this->_entry_id => $entry);
    }
    else
    {
      $items = $this->_storage;      
    }

    $items = ( $this->_channel ) ? $this->_filter_by_channel($items) : $items;

    if ($this->_append === TRUE)
    {
        	  
      foreach ($items as $entry_id => $item)
      {
        $current[$entry_id] = $item;
      }
      
      $lists[$this->_list] = $current;
      
    } else {
      
      $lists[$this->_list] = $items;
      
    }    
    
    // If the list has no entries at all, remove it entirely
    if (count($lists[$this->_list]) === 0) unset($lists[$this->_list]);
	  	  
	  $data = array(
	   'remember_me' => serialize($lists)
	  );
	  
	  $this->_update_database($data);

    if ($this->_clear === TRUE)
    {
      $this->_clear_all();
    }
	  
	  $this->_redirect();

	}
	// END save
	
	/**
	* Retrieve a list of entries from the database. If no list specified, list is 'default'
	*
	* @return void
	*/
	function load()
	{
	  
	  $lists = $this->_fetch_from_database();
    
    // If list doesn't exists or there are no items saved in the list, abort attack.
    if ( ! is_array($lists) OR ! isset($lists[$this->_list]) OR count($lists[$this->_list]) === 0 ) return '';

    $items = $lists[$this->_list];
    
    if( $this->_entry_id )
    {
      return $this->_get_entry($items);
      
    } else if ( $this->_channel ) {
      
      $items = $this->_filter_by_channel($items);      
    }

	  if( $this->_reverse === TRUE )
    {
      $items = array_reverse($items, TRUE);
    }
        
    if( $this->_set )
    {
      
      if ($this->_append)
      {
        $items = $items + $this->_storage;
      }
      
      $this->_storage = $items;
      
      $this->_save_storage();
      $this->_redirect();
    }
        	  
    return implode('|', array_keys($items));
	  
	}
	// END save	

  /**
  * @todo
  */
	function remove()
	{
	  
	  if ($this->_list === 'all')
	  {
      $this->_clear_database();
	  }
	  else
	  {
	    
	    if ($this->_entry_id)
	    {
	      $this->_remove_entry_from_list();	    
	    }
	    else
	    {
        $this->_remove_list();	    	      
	    }
	  }
	  
	  $this->_redirect();
    
	}
	// END remove

  /**
  * @todo
  */
	function lists()
	{
	  
	  $results = array();
	  
    $lists = $this->_fetch_from_database();
	      
    if(count($lists) === 0) return $this->EE->TMPL->no_results();
        
    foreach( $lists as $list_name => $items )
    {
      if( $this->_channel )
      {
        $items = $this->_filter_by_channel($items);
      }
      
      $items = $this->_reverse_entries($items);
      
      // If a list has no items and show_empty is not true, do not include in output
      if( count($items) === 0 AND $this->_show_empty === FALSE ) continue;
      
      $results[] = array(
        'list_name' => $list_name,
        'list_items' => implode('|', array_keys($items))
      );
    }
    
    return $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $results);

	}
	// END lists
	
	function debug()
	{
	  return print_r($this->_storage, TRUE);
	}
	
	
	//============================================
	// INTERNAL FUNCTIONS
	//============================================


  /**
  * @todo
  */	
	function _get_entry( $haystack=array() )
	{	  	  
	  // If entry_id is not set, abandon ship
    if( $this->_entry_id === FALSE OR count($haystack) === 0 ) return 0;
	      
    return isset($haystack[$this->_entry_id]) ? 1 : 0;
    
	}
	// END _get_entry
	
	
  /**
  * @todo
  */	
	function _get_all()
	{	      
	  $results = array_keys($this->_storage);
    $results = $this->_reverse_entries($results);

    return implode('|', $results);
    
	}
	// END _get_all


  /**
  * @todo
  */
  function _get_where_channel()
  {
    
    // If channel_id is not set, abandon ship
    if( $this->_channel === FALSE ) return FALSE;
    
    $results = $this->_filter_by_channel($this->_storage);
	  $results = array_keys($results);
    $results = $this->_reverse_entries($results);
    
    return implode('|', $results);
     
  }
  // END _get_where_channel
  
  
  /**
  * @todo
  */
  function _fetch_from_database()
  {
    
    // Does the remember_me column exist? If not, create it!
	  if( ! $this->_column_exists() ) return array();

    if ( ! isset($this->EE->session->cache[__CLASS__][$this->_current_site][$this->_member_id]['storage']))
    {
      $query = $this->EE->db
                          ->select('remember_me')
                          ->from('members')
                          ->where('members.member_id', $this->_member_id)
                          ->where('member_groups.site_id', $this->_current_site)
                          ->join('member_groups', 'members.group_id = member_groups.group_id')
                          ->get();

      $this->EE->session->cache[__CLASS__][$this->_current_site][$this->_member_id]['storage'] = $query->row('remember_me');      
    }
    
    $data = unserialize($this->EE->session->cache[__CLASS__][$this->_current_site][$this->_member_id]['storage']);
    
    return is_array($data) ? $data : array();
    
  }
  
  function _remove_entry_from_list()
  {
    $lists = $this->_fetch_from_database();

    // If list doesn't exists or there are no items saved in the list, abort attack.
    if ( ! isset($lists[$this->_list]) ) return FALSE;
    
    if (isset($lists[$this->_list][$this->_entry_id]))
    {
      unset($lists[$this->_list][$this->_entry_id]);
    }
    
    if (count($lists[$this->_list]) === 0)
    {
      unset($lists[$this->_list]);
    }
    
    $data = array(
	   'remember_me' => serialize($lists)
	  );
	  
	  $this->_update_database($data);
    
    
  }
  
  /**
  * @todo
  */
  function _remove_list()
  {

    $lists = $this->_fetch_from_database();
          
    // If list doesn't exists or there are no items saved in the list, abort attack.
    if( ! isset($lists[$this->_list]) ) return FALSE;

    $lists[$this->_list] = null;
    unset($lists[$this->_list]);
    
    $data = array(
	   'remember_me' => serialize($lists)
	  );
	  
	  $this->_update_database($data);
    
  }
  
  
  /**
  * @todo
  */
  function _clear_database()
  {
    // Does the remember_me column exist? If not, create it!
	  if( ! $this->_column_exists() ) return FALSE;

    $data = array(
	   'remember_me' => serialize(array())
	  );
	  
	  $this->_update_database($data);
	  
  }
  
  
  /**
  * @todo
  */
	function _clear_entry($entry_id=FALSE)
	{
	  	  
	  // If entry_id is not set, abandon ship
    if( $entry_id == FALSE ) return FALSE;
	      
    if( isset($this->_storage[$entry_id]) )
    {
      unset($this->_storage[$entry_id]);
      
      $this->_save_storage();
    }
    
	}
	// END _clear_entry
	
  /**
  * @todo
  */
	function _update_database($data=array())
	{
	  // Insert items into the database
	  $this->EE->db->where('member_id', $this->_member_id)->update('members', $data);
    
	}
	// END _clear_entry
	
	
	
	/**
  * @todo
  */
	function _clear_all()
	{
	  
    $this->_storage = array();

    $this->_save_storage();
    
	  
	}
	// END _clear_all


  /**
  * @todo
  */
  function _clear_where_channel($channel_id=FALSE)
  {
  
    // If channel_id is not set, abandon ship
    if( $channel_id == FALSE ) return;
    
    $keep = array();
    
    foreach ( $this->_storage as $key => $entry )
    {
      if( $entry['channel_id'] != $channel_id )
      {
        $keep[$key] = $entry;
      }
    }
    
    $this->_storage = $keep;
        
    $this->_save_storage();
    
    
  }
	// END _clear_where_channel
  
  
  /**
  * @todo
  */
  function _save_storage()
  {    
    $this->EE->TMPL->log_item(__CLASS__ . ': Saving storage ('.implode(',', array_keys($this->_storage)).')');
    
    if( isset($this->EE->session->userdata[strtolower(__CLASS__)]) ) unset($this->EE->session->userdata[strtolower(__CLASS__)]);      

    // Save storage to session
    $_SESSION[strtolower(__CLASS__)] = $this->_storage;
    
      
  }
  
  
  /**
  * @todo
  */
  function _redirect()
  {
    // If return URL has been set and it is not an Ajax call, redirect
    if( $this->_return AND ! isset($_SERVER['X_HTTP_REQUESTED_WITH']) )
    {
      $this->EE->functions->redirect( $this->EE->functions->create_url($this->_return) );
    }
    
    return;
  }
  
  
	/**
	* Check if an entry exists for the given parameter
	*
	* @param	string	$in entry_id or url_title of a channel entry
	* @return	mixed [integer|boolean]
	*/
  function _entry_exists($in = FALSE, $return_id=FALSE) {

    if ($in === FALSE)
    {
      $in = $this->EE->uri->query_string;
    }
    
    $entry = array();
    
    if ( ! isset($this->EE->session->cache[__CLASS__][$this->_current_site]['entries']) OR ! isset($this->EE->session->cache[__CLASS__][$this->_current_site]['entries'][$in]))
    {
      $this->EE->db->select("entry_id, CAST(channel_id AS UNSIGNED) AS channel_id");
      $this->EE->db->where("(entry_id = '$in' OR url_title = '$in') AND site_id='".$this->_current_site."'");
      $this->EE->session->cache[__CLASS__][$this->_current_site]['entries'][$in] = $this->EE->db->get('channel_titles');
    }
    
    $results = $this->EE->session->cache[__CLASS__][$this->_current_site]['entries'][$in];
    
    return $return_id ? (int) $results->row('entry_id') : $results->row_array();
        
    return FALSE;
  }
	// END _entry_exists

	/**
	* Check if the specified channel exists and return the channel_id
	*
	* @param	string	$channel channel_id or channel short name
	* @return	mixed [integer|boolean]
	*/
  function _channel_exists($channel=FALSE) {
		
    if( $channel == FALSE ) return FALSE;

 	  $this->EE->db->select('channel_id')
               	   ->from('channels')
               	   ->where("(channel_id = '$channel' OR channel_name = '$channel')")
               	   ->where('site_id', $this->_current_site);
    $results = $this->EE->db->get();
    
    return ($results->num_rows() > 0) ? (int) $results->row('channel_id') : FALSE;    
  }
	// END _channel_exists

	
	/**
	* Check if the remember_me column exists in the exp_members table. If not create it...
	*
	* @return	boolean
	*/
  function _column_exists()
  {
    
	  // You have to be logged in to be able to save entries
	  if( ! $this->_member_id ) return FALSE;    
    
    if( $this->EE->db->field_exists('remember_me', 'members') ) return TRUE;

    $this->EE->load->dbforge();
    
    $dbfield = array(
      'remember_me' => array(
        'type' => 'TEXT',
        'null' => TRUE
      )
    );
          
    $this->EE->dbforge->add_column('members', $dbfield); 
    
    
    
    return TRUE;
    
  }
  
  /**
  * Fetch entries from a set with a certain channel id
  * @param    array   $items  array of items to search through
  * @return   array
  */
  function _filter_by_channel($haystack=array())
  {
		
    if ( ! $this->_channel OR count($haystack) === 0) return $haystack;
    
    $results = array();
        
    foreach ($haystack as $key => $entry)
    {      
      if ((int) $entry['channel_id'] === $this->_channel)
      {
        $results[$key] = $entry;
      }
    }
    
    return $results;
    
  }
  
  
  /**
  * @todo
  */
  function _reverse_entries($items)
  {
        
    if (is_array($items) AND $this->_reverse === TRUE)
    {
      $items = array_reverse($items);
    }
    
    return $items;
  }
  
	function _fetch_all_params()
	{
	  	
    $this->_dynamic_params = array_intersect($this->_allowed_dynamic_params, explode('|', (string) $this->EE->TMPL->fetch_param('dynamic_params')));
    
    $this->_entry_id = $this->_fetch_param('entry_id') ? $this->_entry_exists($this->_fetch_param('entry_id'), TRUE) : FALSE;
    $this->_channel = $this->_fetch_param('channel') ? $this->_channel_exists($this->_fetch_param('channel')) : FALSE;
    
    $this->_return = $this->_fetch_param('return');
    $this->_reverse = $this->_fetch_param_bool('reverse');

    $this->_list = $this->_fetch_param('list') ? $this->_fetch_param('list') : 'default';
    $this->_show_empty = $this->_fetch_param_bool('show_empty');
    $this->_set = $this->_fetch_param_bool('set');
    $this->_append = $this->_fetch_param_bool('append');
    $this->_clear = $this->_fetch_param_bool('clear');
        
    if ($this->_fetch_param('member_id') AND is_numeric($this->_fetch_param('member_id')))
    {
      $this->_member_id = (int) $this->EE->TMPL->fetch_param('member_id');
    }
    else
    {
      $this->_member_id = isset($this->EE->session->userdata['member_id']) ? $this->EE->session->userdata['member_id'] : 0;
    }
  }  
  
  function _fetch_param($param='', $default=FALSE)
  {
    if ($param === '') return $default;
    
    $val = FALSE;

    if (in_array($param, $this->_dynamic_params))
    {
      $val =  $this->EE->input->get_post($param);
    }
    
    if ($val === FALSE)
    {
      $val = $this->EE->TMPL->fetch_param($param);
    }
    
    return $val ? $val : $default;
      
  }
  
  function _fetch_param_bool($param='', $default=FALSE)
  {

    $val = $this->_fetch_param($param, $default);

    if ($val === $default) return $val;
    
    switch($val)
    {
      default: $val = $default; break;
      case 'y': case 'yes': case 'true': case '1': $val = TRUE; break;
      case 'n': case 'no': case 'false': case '0': $val = FALSE; break;
    }

    return $val;
      
  }
  
	

	/**
	* Plugin Usage
	*
	* @return	string
	*/    
	function usage()
	{
		ob_start(); 
		?>
		
      // Save entry to storage
      {exp:remember_me:set entry_id='61'}

      // Save entry to storage by auto-discovering the entry_id by looking at the current URL
      {exp:remember_me:set}

      // Get all entries from storage
      {exp:remember_me:get}

      // Get entries belonging to a certain channel from storage
      {exp:remember_me:get channel='producten'}<br />
      
      // Retrieve saved entries in a reversed order
      {exp:remember_me:get reverse='yes'}<br />

      // Check if a certain entry is in storage
      {if {exp:remember_me:get entry_id='61'}}
        Entry in storage
      {if:else}
        Entry not in storage
      {/if}
      
      // It can also be used in conjunction with the {exp:channel:entries} loop
      {exp:channel:entries entry_id="{exp:remember_me:get channel='producten'}" parse='inward' dynamic='off'}
        {title}<br />
      {/exp:channel:entries}
      
      // Clear entire storage
      {exp:remember_me:clear}

      // Remove single entry from storage
      {exp:remember_me:clear entry_id='61'}

      // Remove entries belonging to a certain channel from storage
      {exp:remember_me:clear channel='products'}
      
      // Save the entry_ids currently in session storage to the database (in this case, listname is 'default')
      {exp:remember_me:save}
      
      // Save the entry_ids currently in session storage to the database with listname 'favorites'
      {exp:remember_me:save list='favorites'}
      
      // Get a set of saved entries with listname 'favorites' from the the database and restore them to the session storage
      {exp:remember_me:load list='favorites' set='yes'}
      
      // Remove list 'favorites' from the database
      {exp:remember_me:remove list='favorites'}
      
      // Remove all lists from the database and thus clearing it entirely.
      {exp:remember_me:remove list='all'}
      
      // Get all lists from the database
      {exp:remember_me:lists member_id='34'}
        {list_name} : {list_items}<br />
      {/exp:remember_me:lists}
      
      // Returns
      default : 23|43|12|67<br />
      favorites : 45|4|92|457<br />
      
			
		<?php
		$buffer = ob_get_contents();
  
		ob_end_clean(); 

		return $buffer;
	}
  // END usage

}
// END CLASS
/* End of file pi.remember_me.php */