<?php

use App\Libraries\Server\ServerRequestException;
use App\Libraries\Twitter\TwitterResponse;
use GuzzleHttp\Psr7;

class ApiTest extends TestCase
{
    public $twitterServer;

    public function setUp()
    {
        parent::setUp();
        $this->twitterServer = $this->mock(\App\Libraries\Twitter\TwitterServer::class);
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

        $this->twitterServer->shouldReceive('getUserTimeline')->once()->andReturn(new TwitterResponse($response));
        $this->twitterServer->shouldReceive('setConfig')->once();

        $this->get('/histogram/Ferrari');
        $this->seeJsonEquals([]);
    }

    public function testHistogramThreeTweetsInADay()
    {
        $id = 859017375156159928;

        $twitterTimelineResponse = [];
        $twitterTimelineResponse[] = ['id' => $id--, 'created_at' => 'Fri Mar 09 20:55:23 +0000 2018'];
        $twitterTimelineResponse[] = ['id' => $id--, 'created_at' => 'Fri Mar 09 15:05:19 +0000 2018'];
        $twitterTimelineResponse[] = ['id' => $id--, 'created_at' => 'Fri Mar 09 05:17:22 +0000 2018'];
        $twitterTimelineResponse[] = ['id' => $id--, 'created_at' => 'Wed Mar 07 23:11:11 +0000 2018'];

        $stream = Psr7\stream_for(json_encode($twitterTimelineResponse));
        $response = new Psr7\Response(200, ['Content-Type' => 'application/json'], $stream);

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
        $id = 8590353890812344;

        // Add a lot of tweets in an other day
        for ($i = 0; $i < ($batchLimit + 20); $i++) {
            $twitterTimelineResponse[] = ['id' => $id--, 'created_at' => 'Sat Mar 10 20:55:23 +0000 2018'];
        }

        // Add tweets in the day to be tested
        $twitterTimelineResponse[] = ['id' => $id--, 'created_at' => 'Fri Mar 09 19:55:23 +0000 2018'];
        $twitterTimelineResponse[] = ['id' => $id--, 'created_at' => 'Fri Mar 09 19:05:19 +0000 2018'];
        $twitterTimelineResponse[] = ['id' => $id--, 'created_at' => 'Fri Mar 09 17:17:22 +0000 2018'];

        // Add some more tweets in another day
        for ($i = 0; $i < ($batchLimit / 4); $i++) {
            $twitterTimelineResponse[] = ['id' => $id--, 'created_at' => 'Thu Mar 08 13:16:46 +0000 2018'];
        }

        // Build two responses for the two batches
        $stream1 = Psr7\stream_for(json_encode(array_slice($twitterTimelineResponse, 0, $batchLimit)));
        $response1 = new Psr7\Response(200, ['Content-Type' => 'application/json'], $stream1);

        $stream2 = Psr7\stream_for(json_encode(array_slice($twitterTimelineResponse, $batchLimit + 1)));
        $response2 = new Psr7\Response(200, ['Content-Type' => 'application/json'], $stream2);

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
        $id = 859017375156159928;

        $twitterTimelineResponse = [];
        $twitterTimelineResponse[] = ['id' => $id--, 'created_at' => 'Sat Mar 10 19:55:23 +0000 2018'];
        $twitterTimelineResponse[] = ['id' => $id--, 'created_at' => 'Fri Mar 09 19:05:19 +0000 2018'];
        $twitterTimelineResponse[] = ['id' => $id--, 'created_at' => 'Wed Mar 07 17:17:22 +0000 2018'];
        $twitterTimelineResponse[] = ['id' => $id--, 'created_at' => 'Tue Mar 06 17:17:22 +0000 2018'];

        $stream = Psr7\stream_for(json_encode($twitterTimelineResponse));
        $response = new Psr7\Response(200, ['Content-Type' => 'application/json'], $stream);

        $this->twitterServer->shouldReceive('setConfig')->once();
        $this->twitterServer->shouldReceive('getUserTimeline')->once()->andReturn(new TwitterResponse($response));

        $this->get('/histogram/Ferrari?date=2018-03-08');
        $this->seeJsonEquals([]);
    }

    public function testTwitterServerConnectionError()
    {
        $message = "Some message";
        $code = 123;

        $this->twitterServer->shouldReceive('setConfig')->once();
        $this->twitterServer->shouldReceive('getUserTimeline')->once()->andThrow(new ServerRequestException($message, $code));

        $this->get('/histogram/Ferrari');
        $this->seeJsonContains(['error' => $message, 'code' => $code]);
    }


}