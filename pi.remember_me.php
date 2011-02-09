<?php if (!defined('EXT')) exit('Invalid file request');

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
  var $_current_site = 1;

	/**
	* Member id of the currently logged in member
	*
	* @var	integer
	*/
  var $_member_id = 0;

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

    $this->_storage = (isset($_SESSION['remember_me'])) ? $_SESSION['remember_me'] : array();
    
    $this->_entry_id = $this->EE->TMPL->fetch_param('entry_id');
    $this->_channel = $this->EE->TMPL->fetch_param('channel');
    $this->_return = $this->EE->TMPL->fetch_param('return');
    $this->_reverse = ($this->EE->TMPL->fetch_param('reverse') == 'yes') ? TRUE : FALSE;

    $this->_list = $this->EE->TMPL->fetch_param('list') ? $this->EE->TMPL->fetch_param('list') : 'default';
    $this->_clear = ($this->EE->TMPL->fetch_param('clear') == 'yes') ? TRUE : FALSE;
    $this->_append = ($this->EE->TMPL->fetch_param('append') == 'yes') ? TRUE : FALSE;
    
    $this->_current_site = $this->EE->config->item('site_id');
    $this->_member_id = (isset($this->EE->session->userdata['member_id'])) ? $this->EE->session->userdata['member_id'] : 0;
	}
	// END __construct
	
	
	function set()
	{
	  	  
    if ( $entry = $this->_entry_exists($this->_entry_id) )
    {
      
      $this->_storage[$entry->entry_id] = $entry;      
      
      $this->_save_storage();
      
    }
    
    $this->_redirect();
	  
	}
	// END set
	
	
	function get()
	{

	  // entry_id parameter has been specified
	  if( $entry_id = $this->_entry_exists($this->_entry_id, TRUE) )
	  {
	    return $this->_get_entry( $entry_id );
	  }

	  // channel parameter has been specified
	  if( $channel = $this->_channel_exists($this->_channel) )
	  {
	    return $this->_get_where_channel( $channel );	    
	  }

    return $this->_get_all();	  

	}
	// END get
	
	
	function clear()
	{
	  
	  // entry_id parameter has been specified
	  if( $entry_id = $this->_entry_exists($this->_entry_id, TRUE) )
	  {
	    $this->_clear_entry( $entry_id );
	  }
	  // channel parameter has been specified
	  else if( $channel = $this->_channel_exists($this->_channel) )
	  {
	    $this->_clear_where_channel($channel);	    
	  }
	  else
	  {
      $this->_clear_all();	    
	  }	  

	}
	// END clear
	
	/**
	* @todo
	*/
	function save()
	{
    // Does the remember_me column exist? If not, create it!
	  if( ! $this->_column_exists() ) return FALSE;
	  
	  $query = $this->EE->db->select('remember_me')->where('member_id', $this->_member_id)->get('members');
	  
	  $lists = array();
	  
	  if( $query->num_rows() > 0 )
	  {
      $current_lists = unserialize($query->row('remember_me'));
	    $lists = array_merge($lists, $current_lists);
	  }
	  	  	  
	  if( $this->_list )
	  {
	    $lists[$this->_list] = $this->_storage;
	  } else {
	    $lists['default'] = $this->_storage;
	  }
	  	  
	  $data = array(
	   'remember_me' => serialize($lists)
	  );
	  
	  // Insert items into the database
	  $this->EE->db->where('member_id', $this->_member_id)->update('members', $data);

	  return TRUE;
	  
//	  $this->_redirect();
	}
	// END save
	
	/**
	* @todo
	*/
	function load($internal=FALSE)
	{
    // Does the remember_me column exist? If not, create it!
	  if( ! $this->_column_exists() ) return FALSE;
	  
	  $query = $this->EE->db->select('remember_me')->where('member_id', $this->_member_id)->get('members');
	  
	  if( $query->num_rows() === 0 ) return FALSE;

    $lists = unserialize($query->row('remember_me'));
      
    // If list doesn't exists or there are no items saved in the list, abort attack.
    if( ! isset($lists[$this->_list]) || count($lists[$this->_list]) === 0 ) return FALSE;

    $results = array_keys($lists[$this->_list]);
	  
	  if( $this->_reverse === TRUE )
    {
      $results = array_reverse($results);
    }

    return implode('|', $results);	  
	  
	}
	// END save	

  /**
  * @todo
  */
	function remove()
	{
    
	}
	// END remove

  /**
  * @todo
  */
	function lists()
	{
	  
	}
	// END lists
	
	
	function _get_entry($entry_id=FALSE)
	{
	  	  
	  // If channel_id is not set, abandon ship
    if( $entry_id == FALSE ) return 0;
	      
    return isset($this->_storage[$entry_id]) ? 1 : 0;
    
	}
	// END _get_entry
	
	
	function _get_all()
	{
	      
	  $results = array_keys($this->_storage);
	  
	  if( $this->_reverse === TRUE )
    {
      $results = array_reverse($results);
    }
    
    return implode('|', $results);
    
	}
	// END _get_all


  function _get_where_channel($channel_id=FALSE)
  {
    
    // If channel_id is not set, abandon ship
    if( $channel_id == FALSE ) return FALSE;

    $results = array();
    foreach ( $this->_storage as $key => $entry )
    {
      if( $entry->channel_id == $channel_id )
      {
        $results[] = $key;        
      }
    }
    
    if( $this->_reverse === TRUE )
    {
      $results = array_reverse($results);
    }
    
    return implode('|', $results);
     
  }
  // END _get_where_channel
  
  
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
	
	
	function _clear_all()
	{
	  
    $this->_storage = array();

    $this->_save_storage();
    
	  
	}
	// END _clear_all


  function _clear_where_channel($channel_id=FALSE)
  {
  
    // If channel_id is not set, abandon ship
    if( $channel_id == FALSE ) return;
    
    $keep = array();
    
    foreach ( $this->_storage as $key => $entry )
    {
      if( $entry->channel_id != $channel_id )
      {
        $keep[$key] = $entry;
      }
    }
    
    $this->_storage = $keep;
        
    $this->_save_storage();
    
    
  }
	// END _clear_where_channel
  
  
  function _save_storage()
  {    
    
    if( isset($_SESSION['remember_me']) ) unset($_SESSION['remember_me']);      

    // Save storage to cookie    
    $_SESSION['remember_me'] = $this->_storage;
        
    $this->_redirect();    
      
  }
  
  function _redirect()
  {
    
    // If return URL has been set and it is not an Ajax call, redirect
    if( $this->_return && !isset($_SERVER['X_HTTP_REQUESTED_WITH']) )
    {
      $this->EE->functions->redirect( $this->EE->functions->create_url($this->_return) );
    }
    
  }
  
  
	/**
	* Check if an entry exists for the given parameter
	*
	* @param	string	$in entry_id or url_title of a channel entry
	* @return	mixed [integer|boolean]
	*/
  function _entry_exists($in = FALSE, $return_id=FALSE) {

    if( $in === FALSE )
    {
      $in = $this->EE->uri->query_string;
    }

    $results = $this->EE->db->select("entry_id, CAST(channel_id AS UNSIGNED) AS channel_id")
                   ->where("(entry_id = '$in' OR url_title = '$in') AND site_id='".$this->_current_site."'")
                   ->get('channel_titles');
    
    if( $results->num_rows() > 0 )
    {
      return ($return_id) ? (int) $results->row('entry_id') : $results->row();
    }
    
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

      // Clear entire storage
      {exp:remember_me:clear}

      // Remove single entry from storage
      {exp:remember_me:clear entry_id='61'}

      // Remove entries belonging to a certain channel from storage
      {exp:remember_me:clear channel='products'}
      
      // It can also be used in conjunction with the {exp:channel:entries} loop
      {exp:channel:entries entry_id="{exp:remember_me:get channel='producten' parse='inward'}" parse='inward' dynamic='off'}
        {title}<br />
      {/exp:channel:entries}

			
		<?php
		$buffer = ob_get_contents();
  
		ob_end_clean(); 

		return $buffer;
	}
  // END usage

}
// END CLASS

function debug($val)
{
  echo "<pre>".print_r($val, true)."</pre>";exit;
}

/* End of file pi.remember_me.php */