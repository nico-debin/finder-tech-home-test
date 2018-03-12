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
        $twitterTimelineResponse = [];
        $stream = Psr7\stream_for(json_encode($twitterTimelineResponse));
        $response = new Psr7\Response(200, ['Content-Type' => 'application/json'], $stream);

        // Mock twitter server
        $this->twitterServer->shouldReceive('setConfig')->once();
        $this->twitterServer->shouldReceive('getUserTimeline')->once()->andReturn(new TwitterResponse($response));

        $this->get('/histogram/Ferrari');
        $this->seeJsonEquals([]);
    }

    public function testHistogramThreeTweetsInADay()
    {
        $twitterTimelineResponse = [];
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-09 20:55:23')->get();
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-09 15:05:19')->get();
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-09 05:17:22')->get();
        $twitterTimelineResponse[] = $this->fakeTweet()->date('2018-03-07 23:11:11')->get();

        $stream = Psr7\stream_for(json_encode($twitterTimelineResponse));
        $response = new Psr7\Response(200, ['Content-Type' => 'application/json'], $stream);

        // Mock twitter server
        $this->twitterServer->shouldReceive('setConfig')->once();
        $this->twitterServer->shouldReceive('getUserTimeline')->once()->andReturn(new TwitterResponse($response));

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
        $stream1 = Psr7\stream_for(json_encode(array_slice($twitterTimelineResponse, 0, $batchLimit)));
        $response1 = new Psr7\Response(200, ['Content-Type' => 'application/json'], $stream1);

        $stream2 = Psr7\stream_for(json_encode(array_slice($twitterTimelineResponse, $batchLimit + 1)));
        $response2 = new Psr7\Response(200, ['Content-Type' => 'application/json'], $stream2);

        // Mock twitter server
        $this->twitterServer->shouldReceive('setConfig')->once();
        $this->twitterServer->shouldReceive('getUserTimeline')->twice()->andReturn(new TwitterResponse($response1), new TwitterResponse($response2));

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

        $stream = Psr7\stream_for(json_encode($twitterTimelineResponse));
        $response = new Psr7\Response(200, ['Content-Type' => 'application/json'], $stream);

        // Mock twitter server
        $this->twitterServer->shouldReceive('setConfig')->once();
        $this->twitterServer->shouldReceive('getUserTimeline')->once()->andReturn(new TwitterResponse($response));

        $this->get('/histogram/Ferrari?date=2018-03-08');
        $this->seeJsonEquals([]);
    }

    public function testTwitterServerConnectionError()
    {
        $message = "Some message";
        $code = 123;

        // Mock twitter server
        $this->twitterServer->shouldReceive('setConfig')->once();
        $this->twitterServer->shouldReceive('getUserTimeline')->once()->andThrow(new ServerRequestException($message, $code));

        $this->get('/histogram/Ferrari');
        $this->seeJsonContains(['error' => $message, 'code' => $code]);
    }


}