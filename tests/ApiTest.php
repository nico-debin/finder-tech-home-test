<?php

use App\Libraries\Server\ServerRequestException;
use App\Libraries\Twitter\TwitterResponse;
use Carbon\Carbon;
use GuzzleHttp\Psr7;

class ApiTest extends TestCase
{
    public $twitterServer;
    public $fakeTweetId;

    public function setUp()
    {
        parent::setUp();
        $this->twitterServer = $this->mock(\App\Libraries\Twitter\TwitterServer::class);
        $this->fakeTweetId = rand(100000000000, 900000000000);
    }

    public function mock($class)
    {
        $mock = Mockery::mock($class);
        $this->app->instance($class, $mock);
        return $mock;
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * Returns a dummy class to build a fake tweet easily
     */
    public function fakeTweet() {
        $fakeTweet = new class {
            private $tweet = [];

            public function date($strDate) {
                // write dates in Twitter's format
                $this->tweet['created_at'] = Carbon::parse($strDate)->format('D M d H:i:s O Y');
                return $this;
            }

            public function id($id) {
                $this->tweet['id'] = $id;
                return $this;
            }

            public function get() {
                return $this->tweet;
            }
        };

        // Decrease the ID each time a new tweet is created.
        return $fakeTweet->id($this->fakeTweetId--);
    }

    /**
     * Using an array of data, fakes a response as returned by TwitterServer
     */
    public function fakeTwitterResponse(array $twitterTimelineResponse) {
        $stream = Psr7\stream_for(json_encode($twitterTimelineResponse));
        $response = new Psr7\Response(200, ['Content-Type' => 'application/json'], $stream);
        return new TwitterResponse($response);
    }

    public function testRootEndpoint()
    {
        $this->get('/');

        $this->assertEquals(
            'Try /hello/{name}', $this->response->getContent()
        );
    }

    public function testHelloEndpoint()
    {
        $this->get('/hello/Batman');

        $this->assertEquals(
            'Hello Batman', $this->response->getContent()
        );
    }

    public function testHistogramEmptyResponse()
    {
        $twitterResponse = $this->fakeTwitterResponse([]); // Response with no tweets

        // Mock twitter server
        $this->twitterServer->shouldReceive('setConfig')->once();
        $this->twitterServer->shouldReceive('getUserTimeline')->once()->andReturn($twitterResponse);

        $this->get('/histogram/Ferrari')->seeJsonEquals([]);
    }

    public function testHistogramThreeTweetsInADay()
    {
        $twitterTimelineResponse = [];
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-09 20:55:23')->get();
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-09 15:05:19')->get();
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-09 05:17:22')->get();
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-07 23:11:11')->get();

        $twitterResponse = $this->fakeTwitterResponse($twitterTimelineResponse);

        // Mock twitter server
        $this->twitterServer->shouldReceive('setConfig')->once();
        $this->twitterServer->shouldReceive('getUserTimeline')->once()->andReturn($twitterResponse);

        $this->get('/histogram/Ferrari?date=2018-03-09');
        $this->seeJsonEquals([
            5 => 1,
            15 => 1,
            20 => 1,
        ]);
    }

    public function testHistogramNumbersLargeBatch()
    {
        $twitterTimelineResponse = [];
        $batchLimit = env('TWEET_BATCH_LIMIT', 100);

        // Add a lot of tweets in an other day
        for ($i = 0; $i < ($batchLimit + 20); $i++) {
            $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-10 20:55:23')->get();
        }

        // Add tweets in the day to be tested
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-09 19:55:23')->get();
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-09 19:05:19')->get();
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-09 17:17:22')->get();

        // Add some more tweets in another day
        for ($i = 0; $i < ($batchLimit / 4); $i++) {
            $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-08 13:16:46')->get();
        }

        // Build two responses for the two batches
        $twitterResponse1 = $this->fakeTwitterResponse(array_slice($twitterTimelineResponse, 0, $batchLimit));
        $twitterResponse2 = $this->fakeTwitterResponse(array_slice($twitterTimelineResponse, $batchLimit + 1));

        // Mock twitter server
        $this->twitterServer->shouldReceive('setConfig')->once();
        $this->twitterServer->shouldReceive('getUserTimeline')->twice()->andReturn($twitterResponse1, $twitterResponse2);

        $this->get('/histogram/Ferrari?date=2018-03-09');
        $this->seeJsonEquals([
            19 => 2,
            17 => 1,
        ]);
    }

    public function testHistogramWithNoTweetsInSpecificDay()
    {
        $twitterTimelineResponse = [];
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-10 19:55:23')->get();
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-09 19:05:19')->get();
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-07 17:17:22')->get();
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-06 17:17:22')->get();

        $twitterResponse = $this->fakeTwitterResponse($twitterTimelineResponse);

        // Mock twitter server
        $this->twitterServer->shouldReceive('setConfig')->once();
        $this->twitterServer->shouldReceive('getUserTimeline')->once()->andReturn($twitterResponse);

        $this->get('/histogram/Ferrari?date=2018-03-08')->seeJsonEquals([]);
    }

    public function testHistogramAllHours()
    {
        // Build  at least one tweet for each hour
        $twitterTimelineResponse = [];
        for ($i=23 ; $i>=0 ; $i--) {
            $minutes = rand(20, 59);
            for ($j=0 ; $j<rand(1,4); $j++) {
                $date = sprintf("2018-03-12 %02d:%02d:%02d", $i, $minutes--, rand(0, 59));
                $twitterTimelineResponse[] = $this->fakeTweet()->date($date)->get();
            }
        }

        $twitterResponse = $this->fakeTwitterResponse($twitterTimelineResponse);

        // Mock twitter server
        $this->twitterServer->shouldReceive('setConfig')->once();
        $this->twitterServer->shouldReceive('getUserTimeline')->once()->andReturn($twitterResponse);

        $response = $this->call('GET', '/histogram/Ferrari?date=2018-03-12');

        $histogram = json_decode($response->content(), true);

        $tweetCount = 0;
        foreach ($histogram as $hourRange => $count) {
            $tweetCount += $count;
            $this->assertTrue(in_array($hourRange, range(0, 23)));
            $this->assertGreaterThan(0, $count);
        }

        $this->assertEquals(count($twitterTimelineResponse), $tweetCount);
    }

    public function testTwitterServerConnectionError()
    {
        $message = "Some message";
        $code = 123;

        // Mock twitter server
        $this->twitterServer->shouldReceive('setConfig')->once();
        $this->twitterServer->shouldReceive('getUserTimeline')->once()->andThrow(new ServerRequestException($message, $code));

        $response = $this->call('GET', '/histogram/Ferrari');

        $this->seeJsonContains(['error' => $message, 'code' => $code]);
        $this->assertEquals(400, $response->status());
    }

}