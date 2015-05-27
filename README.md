# slackcamp
slackcamp is a simple cron job written in PHP which finds new activity from [Basecamp](http://basecamp.com) and posts it to a specified [Slack](http://slack.com) channel.

## Requirements
- PHP 5.3.x +
- [Composer](http://getcomposer.org)
- [Slack](http://slack.com) account
- [Basecamp](http://basecamp.com) account

## Configuration on Slack

### 1. Choose a team you want to connect with Basecamp
This is the team you want to link with your Basecamp account

### 2. Configure Integrations

![Slack integration configuration](http://plopster.blob.core.windows.net/slackcamp/slack_configure_integrations.png)

### 3. Incoming Webhooks

![Slack Incoming Webhooks](http://plopster.blob.core.windows.net/slackcamp/slack_webhooks.png)

### 4. Set up webhook

![Slack webhook setup](http://plopster.blob.core.windows.net/slackcamp/slack_webhook_setup.png?123)

### 5. Integration complete

![Slack webhook active](http://plopster.blob.core.windows.net/slackcamp/slack_integration_complete.png)

## Installation
1. Clone the repository.
2. Run Composer Install `php composer.phar install`.
3. Copy `config.default.php` to `config.php` and modify the settings.
4. Set up a cron job to run:

```bash
$ crontab -e

# run slackcamp, send basecamp activity to slack
*/1 * * * * php /slackcamp/slackcamp.php
```

### Using OAuth

If OAuth is required rather than normal username/password, a Basecamp integration must be set up on [Basecamp's Developers page](http://integrate.37signals.com), and the appropriate values entered into the slackcamp configuration.  Additionally, `oauth.php` must be accessible from the configured `Redirect URI` and able to write to the location determined by `BASECAMP_OAUTH2_TOKEN_FILE`.  This file should then be secured against reading by the webserver.

Unfortunately, OAuth doesn't add much in the way of security to the script, as an OAuth token issued by Basecamp has most of the same capabilities via the API as a Username/Password combination, rather than the ability to set a limited capability token (eg. read only) as you might expect.  Still, it prevents the account itself being stolen.

## Notes
slackcamp needs to be able to write to a file named `last_run_date.txt` within its directory, or another directory if configured that way. This is so that when we don't get duplicate events from Basecamp.

slackcamp also relies on the accuracy of PHP's `date()` function. If the server time is inaccurate, you may receive duplicate (or missing) messages.

## Thanks
[netvlies / basecamp-php](https://github.com/netvlies/basecamp-php) - PHP Implementation of the all new Basecamp API
