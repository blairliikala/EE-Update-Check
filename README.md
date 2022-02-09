# EE-Update-Check

Checks the EE Store for Updates for a module

## Usage

1. Register the Class as a service in `addon.setup.php`
2. In the module, add a place to show the update like the example below:

```php

$html = '';

if ($this->settings['check_for_updates'] == 'y')
{
    $hasUpdate = ee('youraddon:Updates')->updateAvailable(STORE_NAME, MODULE_VERSION);
   
   if ($hasUpdate)
    {
        $ee_store_response = ee('youraddon:Updates')->getUpdateResponse(STORE_NAME);

        if ($store_response)
        {
            $remote_version  = $ee_store_response->version; // The version on the add-on store.  Should be the same or higher.
            $link            = $ee_store_response->url; // Link to the EE Addon Store's Module page.
            $last_updated    = $ee_store_response->last_update; // The late update date set in the EE Store's backend.

            $html .= ee('CP/Alert')->makeInline('update-message')
                ->asImportant()
                ->withTitle(lang('update_available'))
                ->addToBody(lang('this_version').": ".MODULE_VERSION."<br />".lang('store').": ".$remote_version.", last updated ".$last_updated.". <br /><a href='".$link."' alt='EE Store Link' target='_new'>Visit the EE Store</a>")
                ->render();
        }
    }

    echo $html; // Put this in your output.
}
```

Notes:

- This would be blocking to the page load, and be better handled over an async javascript call.
- The default is 1 day between checks.

