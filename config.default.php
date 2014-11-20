<?php

define('BASECAMP_USERNAME', 'basecamp_username');
define('BASECAMP_PASSWORD', 'basecamp_password');
define('BASECAMP_ID', '999999'); // http://basecamp.com/<BASECAMP_ID>
define('SLACK_INSTANCE', 'slack_instance_name'); // http://<SLACK_INSTANCE>.slack.com
define('SLACK_WEBHOOK_URL', 'slack_webhook_url'); // https://hooks.slack.com/services/<webhook_url>
define('SLACK_BOT_NAME', 'basecamp');
define('SLACK_BOT_ICON', ''); // full URL to bot icon
define('SLACK_DEFAULT_CHANNEL', '#basecamp'); // the default channel that messages will be posted to

$slack_channels = array(
    'Basecamp Project Name' => '#slack_channel_name'
);
