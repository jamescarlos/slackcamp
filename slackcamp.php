<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

function slack_notify($msg, $channel, $attachment)
{
    $curl = curl_init();
    $url = SLACK_WEBHOOK_URL;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
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
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_exec($curl);
    curl_close($curl);
    echo "\n" . 'message sent to ' . $channel;
}

try {
    // last run file name
    $save_last_run_date = false;

    // set the default last run date
    if (file_exists(LAST_RUN_FILENAME)) {
        $last_run_date = file_get_contents(LAST_RUN_FILENAME);
    } else {
        $last_run_date = date('c');
        $save_last_run_date = true;
    }
    $since = $last_run_date;

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

    // reverse the array to send the older events first
    $events = array_reverse($events);

    // go through all of the events that are new since we last ran this
    foreach ($events as $event) {
        $message = $event['creator']['name'] . ' ' . strip_tags($event['action']) . ' <' . $event['html_url'] . '|' . $event['target'] . '>';
        $excerpt = isset($event['excerpt']) ? htmlspecialchars_decode($event['excerpt'], ENT_QUOTES) : '';
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

        // send the slack message
        if ($channel) {
            slack_notify($message, $channel, $attachment);
        }

        // update the last run date based on the latest basecamp event retrieved
        $last_run_date = $event['created_at'];
        $save_last_run_date = true;
    }

    // persist the last run date
    if ($save_last_run_date) {
        $last_run_fp = fopen(LAST_RUN_FILENAME, 'w');
        fwrite($last_run_fp, $last_run_date);
        fclose($last_run_fp);
        echo "\n" . 'setting last run date to ' . $last_run_date;
    }
} catch (Exception $except) {
    echo "\n" . $except->getMessage();
}
echo "\n" . 'DONE!' . "\n";
