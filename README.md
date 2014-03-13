# slackcamp
slackcamp is a simple cron job written in PHP which finds new activity from [Basecamp](http://basecamp.com) and posts it to a specified [Slack](http://slack.com) channel.

## Requirements
- PHP 5.3.x +
- [Composer](http://getcomposer.org)

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