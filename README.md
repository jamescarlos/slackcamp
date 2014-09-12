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

![Slack webhook setup](http://plopster.blob.core.windows.net/slackcamp/slack_webhook_setup.png)

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

## Notes
slackcamp needs to be able to write to a file named `last_run_date.txt` within it's directory. This is so that when we don't get duplicate events from Basecamp.

## Thanks
[netvlies / basecamp-php](https://github.com/netvlies/basecamp-php) - PHP Implementation of the all new Basecamp API
