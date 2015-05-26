<?php

define('BASECAMP_USERNAME', 'basecamp_username');
define('BASECAMP_PASSWORD', 'basecamp_password');
define('BASECAMP_ID', '999999'); // http://basecamp.com/<BASECAMP_ID>
define('SLACK_INSTANCE', 'slack_instance_name'); // http://<SLACK_INSTANCE>.slack.com
define('SLACK_WEBHOOK_URL', 'slack_webhook_url'); // https://hooks.slack.com/services/<webhook_url>
define('SLACK_BOT_NAME', 'basecamp');
define('SLACK_BOT_ICON', ''); // full URL to bot icon
define('SLACK_DEFAULT_CHANNEL', '#basecamp'); // the default channel that messages will be posted to
define('LAST_RUN_FILENAME', __DIR__.'/last_run_date.txt');

$slack_channels = function ($basecamp_project_name, $event) {
    // Given an input Basecamp project name, this function should
    // return a Slack channel name to post to, or an empty string
    // if the event is to be ignored.

    switch ($basecamp_project_name) {
    case 'Basecamp Project Name':
        return '#slack_channel_name';

    case 'Basecamp Project I Do Not Care About':
        return '';

    default:
        // If not previously matched, then default.  Alternatively, change
        // this to return an empty string to ignore the event.
        return SLACK_DEFAULT_CHANNEL;
    }
};

if (false) {
    // Alternative approach: just hardcode the project names in an
    // associative array.  If the array does not contain the key for a
    // project, it'll default to SLACK_DEFAULT_CHANNEL.  If the array does
    // contain a key but the value is empty, it'll ignore the event.
    $slack_channels = array(
        'Basecamp Project Name' => '#slack_channel_name'
    );
}
