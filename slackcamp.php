<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

function slack_notify($msg, $channel, $attachment)
{
    $ch = curl_init();
    $url = sprintf(
        'https://%s.slack.com/services/hooks/incoming-webhook?token=%s',
        SLACK_INSTANCE,
        SLACK_API_TOKEN
    );
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $payload = array(
        'channel' => $channel,
        'username' => SLACK_BOT_NAME,
        'text' => $msg
    );
    $bot_icon = SLACK_BOT_ICON;
    if (preg_match('/^:[a-z0-9_\-]+:$/i', $bot_icon)) {
        $payload['icon_emoji'] = $bot_icon;
    } elseif ($bot_icon) {
        $payload['icon_url'] = $bot_icon;
    }
    if ($attachment) {
        $payload['attachments'] = array($attachment);
    }
    $data = 'payload=' . json_encode($payload);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    curl_close($ch);
    echo "\n" . 'message sent to ' . $channel;
}

// set the last run date
$last_run_date = date('c');
if (file_exists('last_run_date.txt')) {
    $since = file_get_contents('last_run_date.txt');
} else {
    $since = $last_run_date;
}

// persist the last run date
$fp = fopen('last_run_date.txt', 'w');
fwrite($fp, $last_run_date);
fclose($fp);

echo "\n" . 'getting global events since ' . $since . "\n";

// initiate the basecamp service
$service = \Basecamp\BasecampClient::factory(array(
    'auth' => 'http',
    'username' => BASECAMP_USERNAME,
    'password' => BASECAMP_PASSWORD,
    'user_id' => BASECAMP_ID,
    'app_name' => 'slackcamp',
    'app_contact' => 'http://github.com/jamescarlos/slackcamp'
));

// get the events
$events = $service->getGlobalEvents(array(
    'since' => $since
));

// go through all of the events that are new since we last ran this
foreach ($events as $event) {
    $message = $event['creator']['name'] . ' ' . strip_tags($event['action']) . ' <' . $event['html_url'] . '|' . $event['target'] . '>';
    $excerpt = isset($event['excerpt']) ? htmlspecialchars_decode($event['excerpt']) : '';
    $attachment = array();
    if ($excerpt) {
        $attachment = array(
            'fallback' => $excerpt,
            'fields' => array(
                array(
                    'title' => $event['creator']['name'],
                    'value' => $excerpt,
                    'short' => false
                ),
            )
        );
    }

    // see if a specific slack channel is set for notifications
    $channel = SLACK_DEFAULT_CHANNEL;
    if (isset($slack_channels[$event['bucket']['name']])) {
        $channel = $slack_channels[$event['bucket']['name']];
    }

    slack_notify($message, $channel, $attachment);
}

echo "\n" . 'DONE!' . "\n";
