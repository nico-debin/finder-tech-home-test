### Status
[![Build Status](https://travis-ci.org/nicodebin/finder-tech-home-test.svg?branch=master)](https://travis-ci.org/nicodebin/finder-tech-home-test)


# Take Home Tech Task
## Task Requirements
See [requirements.md](requirements.md)

## Server Requirements

 * PHP >= 7.0
 * OpenSSL PHP Extension
 * PDO PHP Extension
 * Mbstring PHP Extension
 * Composer - PHP Dependency Manager - http://getcomposer.org
 
## Installation

1. Clone this project
2. Run `composer install` to install project's dependencies.
3. Copy `.env.example` to `.env`
    1. Set the `APP_KEY` with a random string with max 32 characters. You can generate one with the following command (from the project's root): `php -r "require 'vendor/autoload.php'; echo str_random(32).PHP_EOL;"`
    2. Fill the TWITTER keys. You'll need to create an application and create your consumer key and access token in [apps.twitter.com](https://apps.twitter.com/)
    3. Set TWEET_BATCH_LIMIT to 100 (or more).
 
## Running the application

1. For a quick run, we'll start a built-in web server. Whatever the method you choose, the server should run in the following directory `/public`.
2. Placed in `/public`, run `php -S localhost:3000`
3. That's it. Use a browser, an app like Postman, or just Curl from the terminal to make requests to the endpoints. (ie: `curl localhost:3000`)

## Running tests

1. Run `vendor/bin/phpunit` from project's root. 