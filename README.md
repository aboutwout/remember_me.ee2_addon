Remember Me
==================

Save entries for a user during a session. This could be used for a 'add to cart' function or for a 'product compare' function (save entry_id's for later use). Entries are only stored during a session.

As of version 1.0 you can also save the session storage to the database on a per member basis.


## Tags

---

### {exp:remember_me:set}

Save entry id’s to session storage. Session storage is cleared when the session expires. If no entry_id is specified, Remember Me will try to discover the currently viewed entry based on the last URL segment.

#### Parameters

**entry\_id** (optional)
The entry\_id or url\_title of the entry you want to save to storage.

**return** (optional)
The URL to redirect to after saving an entry to storage redirect. Follows the template\_group/template\_name convention.

#### Examples

    // Save entry to storage
    {exp:remember_me:set}

    // Save entry to storage
    {exp:remember_me:set entry_id='69' return='products/index'}



---

### {exp:remember_me:get}

#### Parameters
    
**entry_id** (optional)
Check wether an entry is in storage. Value can either be a an url\_title or entry\_id.

**channel** (optional)
Filter the returned dataset by channel\_id or channel\_name

**reverse** (optional)
Reverse the order of the entries
   
   
#### Examples

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
    
---

### {exp:remember_me:clear}

#### Parameters

**entry_id** (optional)
Check wether an entry is in storage. Value can either be a an url\_title or entry\_id.

**channel** (optional)
Filter the returned dataset by channel\_id or channel\_name

**return** (optional)
The URL to redirect to. Follows the template\_group/template\_name convention.


#### Examples

    // Clear entire storage
    {exp:remember_me:clear return='group/template'}

    // Remove single entry from storage
    {exp:remember_me:clear entry_id='61'}

    // Remove entries belonging to a certain channel from storage
    {exp:remember_me:clear channel='products'}

---

### {exp:remember_me:save}

#### Parameters

**entry_id** (optional)
Check wether an entry is in storage. Value can either be a an url\_title or entry\_id.

**channel** (optional)
Either the channel short\_name or the channel\_id. Only save entries to the database which belong to the specified channel.

**return** (optional)
URL to redirect to after saving entries to the database. Follows the template\_group/template\_name convention.

**list** (optional)
Name of the list to save to. If no list is specified, list name ‘default’ is used.

**append** (optional)
Append entries to the database or not. Default value is 'no' which means it will override the entries currently saved to the list in the database. [ yes | no* ]

**clear** (optional)
Clear session storage after saving it to the database. [ yes | no* ]

**member_id** (optional)
member\_id of the member to assigne the entries to. Defaults to currently logged in member.


#### Examples

    // Save the entire storage to the list 'default'
    {exp:remember_me:save return='products/index'}
    
    // Append a single entry to the list 'shoppingcart' and redirect
    {exp:remember_me:save entry_id='23' list='shoppingcart' append='yes' return='products/detail/product_name'}
    
    // Save all entries in storage from channel 'songs' to the list 'My jazz favorites'
    {exp:remember_me:save list='My jazz favorites' channel='songs' return='music/index'}
    
    // Append entries from the channel 'songs' to the list 'My jazz favorites'
    {exp:remember_me:save list='My jazz favorites' channel='songs' append='yes' return='music/index'}

---

### {exp:remember_me:load}

#### Parameters

**entry_id** (optional)
Check wether an entry is stored in the database. Value can either be a an url\_title or entry\_id.

**channel** (optional)
Filter the items loaded from the database and return only those entries that belong to a particular channel. Either the channel\_id or channel\_name of a channel.

**return** (optional)
URL to redirect to after loading entries from the database. Follows the template\_group/template\_name convention.

**reverse** (optional)
Reverse the order of the returned items. [ yes | no ]

**list** (optional)
The list name to load. Default value is 'default'

**set** (optional)
Put the returned entries in session storage? Overrides the items currently in storage, unless specified otherwise (see 'append' parameter). [ yes | no* ]

**append** (optional)
When using the 'set' parameter this parameter allows you to append the loaded items to session storage instead of overriding it.   [ yes | no* ]

**member_id** (optional)
member\_id of the member to load the entries from. Defaults to currently logged in member.


#### Examples


    // Output items from list 'default'.
    {exp:remember_me:load}
      > Output : '34|63|135|4'
    
    // Append items from the list 'shoppingcart' to the current session storage, but only those that are in the 'products' channel.
    {exp:remember_me:load list='shoppingcart' set='yes' append='yes' channel='products' return='products/index'}
    
    // Outputs items from the list 'My birthday wishlist' belonging to member 7.
    {exp:remember_me:load list='My birthday wishlist' return='products/index' member_id='7'}
      > Output : '34|232|64|92'

    

---

### {exp:remember_me:remove}

#### Parameters

**channel** (optional)
Remove only those entries that belong to a particular channel. Either the channel\_id or channel\_name of a channel.

**return** (optional)
URL to redirect to after removing entries from the database. Follows the template\_group/template\_name convention.

**list** (optional)
The list name to remove or remove entries from. Default value is 'default'

**member_id** (optional)
member\_id of the member to remove the entries from. Defaults to currently logged in member.


#### Examples

    // Remove the entire 'saved_shoppingcart' list from the database
    {exp:remember_me:remove list='saved_shoppingcart' return='products/index'}
    
    // Remove entry 232 from the list 'My birthday wishlist'
    {exp:remember_me:remove list='My birthday wishlist' entry_id='232' return='account/whishlists'}
    

---

### {exp:remember_me:lists}

This tag will output all lists saved by a particular member. The tag {list_items} is used to output the entry\_ids in that list.

#### Parameters

**channel** (optional)
Filter the returned items in the list by channel. Either the channel\_id or channel\_name of a channel.

**reverse** (optional)
Reverse the order of the returned items within a list. [ yes | no* ]

**show\_empty** (optional)
When returning lists and filtering by channel, a list may return no items. By default they aren't shown, but this parameter will allow you to show it anyway. [ yes | no* ]

**member\_id** (optional)
member\_id of the member to show the list for. Defaults to currently logged in member.


#### Examples

    // Show all lists belonging to member 45. Handy if you want to allow members to make public wishlists
    <ul class="db-storage">
      {exp:remember_me:lists parse='inward' member_id='45'}
        {if no_results}<li class="no_lists">No lists saved</li>{/if}
      <li>
        <h5>{list_name}</h5>
        <ul>
          {exp:channel:entries dynamic='no' entry_id='{list_items}'}
            <li>{entry_id}) {title} <a href="{path=plugins/remember_me/remove_entry/{list_name}/{entry_id}}">Remove</a></li>
          {/exp:channel:entries}
        </ul>
      </li>
      {/exp:remember_me:lists}
    </ul>  
    

---




    
## Changelog

**0.9.3** : Added 'reverse' parameter

