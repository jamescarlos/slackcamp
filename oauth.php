<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';


try {

    if(file_exists(BASECAMP_OAUTH2_TOKEN_FILE)) {
        $auth_json = file_get_contents(BASECAMP_OAUTH2_TOKEN_FILE);
        $auth = json_decode($auth_json);

        if(3600 > $auth->expires_at - time()) {
            $params = array(
                'type' => 'refresh',
                'client_id' => BASECAMP_OAUTH2_CLIENT_ID,
                'redirect_uri' => BASECAMP_OAUTH2_REDIRECT_URI,
                'client_secret' => BASECAMP_OAUTH2_CLIENT_SECRET,
                'refresh_token' => $auth->refresh_token
            );

            $ch = curl_init(BASECAMP_OAUTH2_ACCESS_URI);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, rawurldecode(http_build_query($params)));
            $response = curl_exec( $ch );
            curl_close($ch);

            if (0 !== strpos($response, '{"'))
                throw new Exception($response);

            print_r($response);
            exit;
        }
    }

    if(isset($_GET['code']) and !empty($_GET['code'])) {

        $params = array(
            'type' => 'web_server',
            'client_id' => BASECAMP_OAUTH2_CLIENT_ID,
            'redirect_uri' => BASECAMP_OAUTH2_REDIRECT_URI,
            'client_secret' => BASECAMP_OAUTH2_CLIENT_SECRET,
            'code' => $_GET['code']
        );

        $ch = curl_init(BASECAMP_OAUTH2_ACCESS_URI);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, rawurldecode(http_build_query($params)));

        $response = curl_exec( $ch );
        curl_close($ch);

        if (0 !== strpos($response, '{"'))
            throw new Exception('Bad response: '.$response);

        $res = json_decode($response, true);

        if(!isset($res['refresh_token']))
            throw new Exception($response);

        $res['expires_at'] = time() + $res['expires_in'];

        $auth = json_encode($res);

        if(!file_put_contents(BASECAMP_OAUTH2_TOKEN_FILE, $auth))
            throw new Exception("Could not write file ".BASECAMP_OAUTH2_TOKEN_FILE.": $auth");

        echo 'Done.';
    }

    else {
        $params = array(
            'type' => 'web_server',
            'client_id' => BASECAMP_OAUTH2_CLIENT_ID,
            'redirect_uri' => BASECAMP_OAUTH2_REDIRECT_URI
        );

        $url = BASECAMP_OAUTH2_REQUEST_URI.'?'.http_build_query($params);

        Header("Location: $url");
        print '<a href="'.$url.'>'.$url.'</a>';
    }
}
catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<pre>";
    echo htmlentities($e->getMessage());
}