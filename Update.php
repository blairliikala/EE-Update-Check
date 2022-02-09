<?php

/** Check for updates from EE Add-on store. */

namespace Your\Addon;

class Updates {

    // https://expressionengine.com/add-ons/addon-feed
    /*  Example response from EE store feed.
    {
        "title": "ExpressionEngine Store",
        "home_page_url": "https://expressionengine.com/add-ons",
        "feed_url": "https://expressionengine.com/add-ons/addon-feed",
        "items": [
            {
                "id":"weebhooks",
                "title":"Weebhooks",
                "developer":"Blair Liikala",
                "url":"https:\/\/expressionengine.com\/add-ons\/weebhooks",
                "summary":"&quot;&quot;&lt;p&gt;Create workflows when actions happen in EE.&lt;\/p&gt;\n",
                "image":"https:\/\/expressionengine.com\/asset\/img\/add-on-icons\/blob.png",
                "price":"15",
                "version":"1.0",
                "last_update":"5 days ago",
                "compatibility":"6",
                "categories" : ["Integrations"]
            }
        ]
    }
    */

    public function __construct()
    {
        // get your settings!
        // $this->settings = getSettings();

        $this->settings['enable_cache'] = 'y';
    }


    public function updateAvailable(string $module_name = NULL, string $local_module_version = NULL) : Bool
    {

        // Need module name to check store!
        if (!$module_name)
        {
            return FALSE;
        }

        $data = $this->getFeed($module_name);

        if (!$data)
        {
            return FALSE;
        }

        $data  = json_decode($data);
        if (!$data)
        {
            return FALSE;
        }

        $items = (isset($data->items)) ? $data->items : NULL;
        if (!$items)
        {
            return FALSE;
        }

        // Find the matching addon object.  This is incase all the addons are returned.
        foreach($items as $key=>$item)
        {
            if ($item->id === $module_name)
            {
                $item = $items[$key];
            }
        }
        if (!isset($item) OR !$item)
        {
            return FALSE;
        }

        $store_version = (isset($item->version)) ? $item->version : NULL;

        if (!$store_version)
        {
            return FALSE;
        }


        /*

            There is debate over how to get the version for the module.
            There is a weird state that happens when the module files are updated, but the update script has not run yet.  So the two options are:
            (1) Pulling from the database is slightly slower, but more likely to be accurate.  It is also what EE thinks is the current version.
            (2) Getting from a global is faster, but assumes it was set in the config.  It is also the current version of the module's files.

        */
        //$local_version = ee('Model')->get('Module')->filter('module_name', $module_name)->first()->YOUR_MODULE_VERSION; // Get through the database.
        //$local_version = YOUR_MODULE_VERSION; // Less queries, better for testing.

        if ($local_module_version)
        {
            // Less queries, better when developing/testing.
            return ( version_compare( trim($store_version), trim($local_module_version), '>') ) ? TRUE : FALSE;
        }
        else
        {
            $local_version = ee('Model')->get('Module')->filter('module_name', $module_name)->first()->module_version; // Get through the database.
            return ( version_compare( trim($store_version), trim($local_version), '>') ) ? TRUE : FALSE;
        }

    }

    public function getUpdateResponse(string $module_name = NULL)
    {
        // Need module name to check store!
        if (!$module_name)
        {
            return FALSE;
        }

        $response = json_decode($this->getFeed($module_name));

        if (!$response)
        {
            return FALSE;
        }

        $response = (isset($response->items)) ? $response->items[0] : FALSE;

        return $response;
    }


    public function getFeed(string $module_name = '')
    {

        $url = 'https://expressionengine.com/add-ons/addon-feed/'.$module_name;

        $cached = ee()->cache->get('/'.$module_name.'/eestore_ping');

        if ($cached)
        {
            return $cached;
        }
        else
        {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $data = curl_exec($curl);

            curl_close($curl);

            if ($this->settings['enable_cache'] === 'y')
            {
                ee()->cache->save('/'.$module_name.'/eestore_ping', $data, 86400);
            }

            return $data;

        }


    }

}