<?php
/**
 * Created by PhpStorm.
 * User: nicolasd
 * Date: 3/9/18
 * Time: 03:21
 */

namespace App\Libraries\Twitter;


use App\Libraries\Server\MissingCredentialsException;
use App\Libraries\Server\Server;
use App\Libraries\Server\ServerRequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Validator;

class TwitterServer implements Server
{
    const TWITTER_API_BASE_URL = 'https://api.twitter.com/1.1/';

    /**
     * The HTTP status code from the previous request
     */
    protected $httpStatusCode;

    /**
     * HTTP Client that handles requests to Twitter's API
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * Twitter auth credentials
     */
    private $oauth_access_token;
    private $oauth_access_token_secret;
    private $consumer_key;
    private $consumer_secret;

    /**
     * Set credentials to use with Twitter API
     *
     * @param array $config
     * @throws MissingCredentialsException
     */
    public function setConfig(array $config)
    {
        $validator = Validator::make($config, [
            'oauth_access_token' => 'required',
            'oauth_access_token_secret' => 'required',
            'consumer_key' => 'required',
            'consumer_secret' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            throw new MissingCredentialsException($errors->first());
        }

        $this->oauth_access_token = $config['oauth_access_token'];
        $this->oauth_access_token_secret = $config['oauth_access_token_secret'];
        $this->consumer_key = $config['consumer_key'];
        $this->consumer_secret = $config['consumer_secret'];

        $this->buildHttpClient();
    }

    /**
     * Builds an HTTP Client with the OAuth1 protocol needed by Twitter's API
     */
    private function buildHttpClient()
    {
        $stack = HandlerStack::create();

        $middleware = new Oauth1([
            'consumer_key' => $this->consumer_key,
            'consumer_secret' => $this->consumer_secret,
            'token' => $this->oauth_access_token,
            'token_secret' => $this->oauth_access_token_secret
        ]);

        $stack->push($middleware);

        $this->httpClient = new Client([
            'base_uri' => self::TWITTER_API_BASE_URL,
            'handler' => $stack,
            'auth' => 'oauth'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    /**
     * Returns a collection of the most recent Tweets posted by the indicated by the screen_name or user_id parameters.
     *
     * Parameters :
     * - user_id
     * - screen_name
     * - since_id
     * - count (1-200)
     * - include_rts (0|1)
     * - max_id
     * - trim_user (0|1)
     * - exclude_replies (0|1)
     * - contributor_details (0|1)
     * - include_entities (0|1)
     * - tweet_mode ('extended' returns a collection of Tweets, which are not truncated)
     *
     * @param array $params
     * @return TwitterResponse
     */
    public function getUserTimeline(array $params = [])
    {
        return $this->get('statuses/user_timeline.json', ['query' => $params]);
    }

    /**
     * HTTP GET request to a Twitter's endpoint
     *
     * @param string $endpoint Twitter's endpoint
     * @param array $params Twitter's endpoint parameters
     * @return TwitterResponse
     */
    public function get($endpoint, array $params = [])
    {
        return $this->request('GET', $endpoint, $params);
    }

    /**
     * Performs an HTTP request to a Twitter's endpoint
     *
     * @param string $method HTTP verb such as GET, POST
     * @param string $endpoint Twitter's endpoint
     * @param array $params Twitter's endpoint parameters
     * @return TwitterResponse
     * @throws \Exception
     */
    public function request($method, $endpoint, array $params = [])
    {
        try {
            $response = $this->httpClient->request($method, $endpoint, $params);
        } catch (ClientException $e) {
            $this->handleClientException($e);
        } catch (\Exception $e) {
            throw new ServerRequestException($e->getMessage(), $e->getCode(), $e);
        }
        $this->httpStatusCode = $response->getStatusCode();
        return new TwitterResponse($response);
    }

    private function handleClientException(ClientException $e)
    {
        try {
            $json = json_decode($e->getResponse()->getBody()->getContents(), true);
            $message = "Twitter: " . $json['errors'][0]['message'];
            $code = $json['errors'][0]['code'];
            throw new ServerRequestException($message, $code, $e);
        } catch (\Exception $e) {
            throw new ServerRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }
}