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