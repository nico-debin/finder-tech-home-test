# Take Home Tech Task

Using an appropriate micro-framework as a foundation (e.g. [Silex](https://silex.symfony.com/), or [Slim](https://www.slimframework.com/) etc.), connect to
Twitter's API and return a JSON-encoded array containing **“hour -> tweet counts”** for a
given user, to determine what hour of the day they are most active. The application should
consist of at least 3 endpoints:

 * **"/"** - will respond with **"Try /hello/:name"** as text
 * **"/hello/BarackObama**" - will respond with "**Hello BarackObama**" as text
 * **"/histogram/Ferrari"** - will respond with a JSON structure displaying the number of tweets per hour of the day

The app will be reviewed based on the appropriate use of [OO](https://en.wikipedia.org/wiki/Object-oriented_programming#Features) and [SOLID](https://en.wikipedia.org/wiki/SOLID_(object-oriented_design)) principles.

## Notes

You should:
 * Submit your assignment as a Git repository hosted on either GitHub or BitBucket.
 * Take the full window of time; there are no bonuses for early submission.
 * Include a README explaining how to install dependencies and run your application.
 * Include automatic tests and instructions for running them.
 * Explain any compromises/shortcuts you made due to time considerations.

## Sample Solution Scaffold

```
date_default_timezone_set('UTC');
require_once __DIR__ . '/vendor/autoload.php';

$app = new Silex\Application();

$app->get('/', function() use($app) {
    return 'Try /hello/:name';
});

$app->get('/hello/{name}', function($name) use($app) {
    return 'Hello ' . $app->escape($name);
});

$app->get('/histogram/{username}', function($username) use($app) {
    // code here
});

$app->run();
```

# Solution
## Server Requirements

 * PHP >= 7.0
 * OpenSSL PHP Extension
 * PDO PHP Extension
 * Mbstring PHP Extension
 * Composer - PHP Dependency Manager - http://getcomposer.org
 
## Installation

1. Clone this project
2. Run `composer install`
3. Copy `.env.example` to `.env`
    1. Change the `APP_KEY` to something random string with max 32 characters. You can generate one with the following command (from the project's root): `php -r "require 'vendor/autoload.php'; echo str_random(32).PHP_EOL;"`
    2. Fill the TWITTER keys. You'll need to create an application and create your consumer key and access token in [apps.twitter.com](https://apps.twitter.com/)
 
## Running the application

1. For a quick run, we'll start a built-in web server. Whatever the method you choose, the server should run in the following directory `/public`.
2. Placed in `/public`, run `php -S localhost:3000`
3. That's it. Use a browser, an app like Postman, or just Curl from the terminal to make requests to the endpoints. (ie: `curl localhost:3000`)

## Running tests

1. Run `vendor/bin/phpunit` from project's root. 