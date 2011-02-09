Remember Me
==================

Save entries for a user during a session. This could be used for a 'add to cart' function or for a 'product compare' function (save entry_id's for later use). Entries are only stored during a session.

### Parameters

**entry_id**
When using the entry_id parameter, only a single entry in storage will be affected.

**channel**
If the channel parameter is specified, only entries in a certain channel are affected. Only the :get and :clear methods support this parameter.

**return**
Specify a return URL for this operation. After the plugin has been run, the page is redirected to that URL. Only the :set and :clear methods support this parameter. If, however, the template has been called through an Ajax request, the page will not be redirected.

**reverse**
If set to 'yes' this parameter will reverse the order of the returned entries. This is a handy feature if you want to return the latest saved entries.

Remember Me is an ExpressionEngine 2.x add-on. You can find the 1.x version [here](https://github.com/AboutWout/remember_me.ee_addon).


### Usage

The :set method can be used anywhere and do not affect page output, but can also be used in a template of it's own to be able to call it through an Ajax request. The same goes for the :get and :clear methods.

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
    {exp:remember_me:clear return='group/template'}

    // Remove single entry from storage
    {exp:remember_me:clear entry_id='61'}

    // Remove entries belonging to a certain channel from storage
    {exp:remember_me:clear channel='products'}

    // It can also be used in conjunction with the {exp:channel:entries} loop
    {exp:channel:entries entry_id="{exp:remember_me:get channel='producten' parse='inward'}" parse='inward' dynamic='off'}
      {title}<br />
    {/exp:channel:entries}
    
## Changelog

**0.9.3** : Added 'reverse' parameter

