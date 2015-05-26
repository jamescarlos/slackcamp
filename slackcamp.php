<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';


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
    if (defined('BASECAMP_OAUTH2_TOKEN_FILE')
        and !empty(constant('BASECAMP_OAUTH2_TOKEN_FILE'))
        and file_exists(constant('BASECAMP_OAUTH2_TOKEN_FILE'))) {

        $auth_json = file_get_contents(BASECAMP_OAUTH2_TOKEN_FILE);
        $auth = json_decode($auth_json, true);

        // Check if the OAuth token will expire soon
        if(3600 > $auth['expires_at'] - time()) {

            // The OAuth token will expire within the next hour, so
            // renew it using the "refresh token" originally offered

            echo "\n" . "Updating Basecamp OAuth access token" . "\n";

            $params = array(
                'type' => 'refresh',
                'client_id' => BASECAMP_OAUTH2_CLIENT_ID,
                'redirect_uri' => BASECAMP_OAUTH2_REDIRECT_URI,
                'client_secret' => BASECAMP_OAUTH2_CLIENT_SECRET,
                'refresh_token' => $auth['refresh_token']
            );

            $ch = curl_init(BASECAMP_OAUTH2_ACCESS_URI);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, rawurldecode(http_build_query($params)));
            $response = curl_exec( $ch );
            curl_close($ch);

            // The API should return JSON.
            if (0 !== strpos($response, '{"'))
                throw new Exception($response);

            $res = json_decode($response, true);

            // It should include a new access_token
            if(!isset($res['access_token']))
                throw new Exception($response);

            // This new access token needs to be pasted into the existing auth data
            $auth['expires_at'] = time() + $res['expires_in'];
            $auth['access_token'] = $res['access_token'];

            $auth_json = json_encode($auth);

            // Save the renewed data out
            if(!file_put_contents(BASECAMP_OAUTH2_TOKEN_FILE, $auth_json))
                throw new Exception("Could not write file ".BASECAMP_OAUTH2_TOKEN_FILE.": $auth_json");
        }

        // Authenticate using OAuth
        $service = \Basecamp\BasecampClient::factory(array(
            'auth' => 'oauth',
            'token' => $auth['access_token'],
            'user_id' => BASECAMP_ID,
            'app_name' => 'slackcamp',
            'app_contact' => 'http://github.com/starberry/slackcamp'
        ));
    }

    else if (defined('BASECAMP_USERNAME') and !empty(constant('BASECAMP_USERNAME'))) {

        // Authenticate using standard username/password, but over HTTPS
        $service = \Basecamp\BasecampClient::factory(array(
            'auth' => 'http',
            'username' => BASECAMP_USERNAME,
            'password' => BASECAMP_PASSWORD,
            'user_id' => BASECAMP_ID,
            'app_name' => 'slackcamp',
            'app_contact' => 'http://github.com/jamescarlos/slackcamp'
        ));
    }
    else
        throw new Exception("Basecamp configuration required");


    // get the events
    $events = $service->getGlobalEvents(array(
        'since' => $since
    ));

    // reverse the array to send the older events first
    $events = array_reverse($events);

    // go through all of the events that are new since we last ran this
    foreach ($events as $event) {

        // Build the Slack message, using formatting detailed here:
        //     https://api.slack.com/docs/formatting

        $message = $event['creator']['name'] . ' ' . strip_tags($event['action']) . ' <' . $event['html_url'] . '|' . $event['target'] . '>';


        // If attachment is something we want, build it.
        $attachment = array();
        if (!defined('SLACK_INCLUDE_ATTACHMENT') or constant('SLACK_INCLUDE_ATTACHMENT')) {

            // Build a fallback excerpt for attachments
            $excerpt = isset($event['excerpt']) ? htmlspecialchars_decode($event['excerpt'], ENT_QUOTES) : '';

            if (!$excerpt) $excerpt = $event['action'];

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



        // Get the correct Slack channel to post to

        // see if a specific slack channel is set for notifications
        $channel = SLACK_DEFAULT_CHANNEL;

        // If $slack_channels is a callable, eg. a function, then call it
        // to deduce the channel name.
        if (is_callable($slack_channels)) {
            $channel = call_user_func($slack_channels, $event['bucket']['name'], $event);
        }

        // Or, if it's an associative array containing an entry for the
        // Basecamp project name, use it.
        else if (is_array($slack_channels) and isset($slack_channels[$event['bucket']['name']])) {
            $channel = $slack_channels[$event['bucket']['name']];
        }



        // If a channel was selected, send the message.  Otherwise, ignore.
        if ($channel) {
            // Send the slack message
            $ret = slack_notify($message, $channel, $attachment);

            if('ok' !== $ret)
                throw new Exception("Bad response from Slack: $ret");

            echo "\n" . 'message sent to ' . $channel;
        }
        else {
            // Don't send the message.. skip
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

exit;


function slack_notify($msg, $channel, $attachment)
    // Send a message to Slack
{
    $curl = curl_init();
    $url = SLACK_WEBHOOK_URL;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_HEADER, false);
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
    //    $data = 'payload=' . json_encode($payload);
    $data = json_encode($payload);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    $ret = curl_exec($curl);
    curl_close($curl);

    return $ret;
}
