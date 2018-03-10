<?php namespace App\Libraries\Twitter;

use Carbon\Carbon;

class TwitterAPI
{

    /**
     * @var TwitterServer
     */
    private $server;

    public function __construct(TwitterServer $server)
    {
        $this->server = $server;
        $this->server->setConfig($this->getConfig());
    }

    /**
     * Builds a configuration array with Twitter's credentials as needed by TwitterServer,
     * reading from environment variables
     *
     * @return array
     */
    protected function getConfig()
    {
        return [
            'oauth_access_token' => env('TWITTER_ACCESS_TOKEN'),
            'oauth_access_token_secret' => env('TWITTER_ACCESS_TOKEN_SECRET'),
            'consumer_key' => env('TWITTER_CONSUMER_KEY'),
            'consumer_secret' => env('TWITTER_CONSUMER_SECRET'),
        ];
    }

    /**
     * Looks for the latest tweets for a user in Twitter
     *
     * @param string $screenName The username in Twitter
     * @return array
     */
    public function getLatestTweets($screenName)
    {
        return $this->server->getUserTimeline(['screen_name' => $screenName])->object();
    }

    /**
     * Returns a histogram based on a hole day. Array keys represent 1 hour interval, values are
     * the amount of tweets within the interval.
     *
     * @param string $screenName
     * @param string $date in format "YYYY-MM-DD"
     * @return array histogram
     */
    public function getUserHistogram($screenName, $date = null)
    {
        // Parse date. Default is today's date.
        try {
            $targetDate = new Carbon($date);
        } catch (\Exception $e) {
            $targetDate = Carbon::today();
        }

        // parameters for getUserTimeline()
        $params = [
            'screen_name' => $screenName,
            'trim_user' => true,
            'exclude_replies' => true,
            'count' => env('TWEET_BATCH_LIMIT', 100), // Get batches of 100 tweets
            'max_id' => null // max_id starts in null since it's the first one
        ];

        // array to store amounts of tweets per hour. Keys are each hour.
        $histogram = [];

        $fetchMoreTweets = true;
        while ($fetchMoreTweets) {
            $userTimeline = $this->server->getUserTimeline($params)->object();

            // Tweets comes sorted by date in descending order
            foreach ($userTimeline as $tweet) {
                $tweetDate = new Carbon($tweet->created_at);
                if ($tweetDate->isSameDay($targetDate)) { // Same day of same month of same year
                    $hour = $tweetDate->hour;
                    if (isset($histogram[$hour])) {
                        $histogram[$hour]++;
                    } else {
                        $histogram[$hour] = 1;
                    }
                } else if ($tweetDate > $targetDate) {
                    continue; // Keep going until we find a tweet within the same day
                } else {
                    $fetchMoreTweets = false;
                    break; // break foreach
                }
            }

            if (isset($tweet)) {
                $params['max_id'] = $tweet->id - 1;
            } else {
                break; // break while
            }
        }

        return $histogram;
    }

}