<?php

define('BASECAMP_USERNAME', 'basecamp_username');
define('BASECAMP_PASSWORD', 'basecamp_password');

/*
//// or alternatively:
define('BASECAMP_OAUTH2_CLIENT_ID', ''); // Fill this in with values from integrate.37signals.com
define('BASECAMP_OAUTH2_CLIENT_SECRET', '');
define('BASECAMP_OAUTH2_REDIRECT_URI', '');

define('BASECAMP_OAUTH2_TOKEN_FILE', __DIR__.'/basecamp_auth.json'); // Note, this needs to be writeable by the script running BASECAMP_OAUTH2_REDIRECT_URI, ie. the webserver
*/


define('BASECAMP_ID', '999999'); // http://basecamp.com/<BASECAMP_ID>
define('SLACK_INSTANCE', 'slack_instance_name'); // http://<SLACK_INSTANCE>.slack.com
define('SLACK_WEBHOOK_URL', 'slack_webhook_url'); // https://hooks.slack.com/services/<webhook_url>
define('SLACK_BOT_NAME', 'basecamp');
define('SLACK_BOT_ICON', ''); // full URL to bot icon
define('SLACK_DEFAULT_CHANNEL', '#basecamp'); // the default channel that messages will be posted to
define('SLACK_INCLUDE_ATTACHMENT', false); // Do we want a full attachment formatted Slack message?

define('LAST_RUN_FILENAME', __DIR__.'/last_run_date.txt');


// Mapping Basecamp Projects to Slack Channels:
//
// Define $slack_channels as a function/callable, or as an array.

// Or, if `custom.php` exists, use that instead.  This allows for complex
// custom mappings.

if( ! @include_once(__DIR__.'/custom.php')) {

    $slack_channels = function ($basecamp_project_name, $event, $service) {
        // Given an input Basecamp project name, this function should
        // return a Slack channel name to post to, or an empty string
        // if the event is to be ignored.
        //
        // The raw $event object is passed; you can use this to filter out
        // messages, eg. by checking $event['action'] and returning ''
        // unless the action starts with "commented on", "added",
        // "posted", "created", etc.
        //
        // Also, $service -- a handle to the BasecampClient in use -- is
        // passed to allow this function to do more investigation.  A good
        // use of this would be to include the Slack channel hashtag in
        // the Basecamp project's `description` field... remember to cache
        // the API queries though!

        // If the Basecamp project name itself contains a hashtag, then
        // assume it's the Slack channel
        if (preg_match('/(#\w+)/', $basecamp_project_name, $parts))
            return $parts[1];

        // Otherwise, use some other code to determine the Slack channel,
        // if any.

        switch ($basecamp_project_name) {
        case 'Basecamp Project Name':
            return '#slack_channel_name';

        case 'Basecamp Project I Do Not Care About':
            return ''; // An empty string means "discard the event"

        default:
            // If not previously matched, then default.  Alternatively, change
            // this to return an empty string to ignore the event.
            return SLACK_DEFAULT_CHANNEL;
        }
    };

    /*
    // Alternative approach: just hardcode the project names in an
    // associative array.  If the array does not contain the key for a
    // project, it'll default to SLACK_DEFAULT_CHANNEL.  If the array does
    // contain a key but the value is empty, it'll ignore the event.
    $slack_channels = array(
      'Basecamp Project Name' => '#slack_channel_name'
    );
    */
}
