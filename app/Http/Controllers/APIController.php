<?php

namespace App\Http\Controllers;

use App\Libraries\Twitter\TwitterAPI;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use function GuzzleHttp\Psr7\uri_for;

class APIController extends Controller
{
    /**
     * Gives a hint to call to the Say Hello endpoint
     *
     * @return string
     */
    public function giveHint()
    {
        $uri = uri_for(route('sayHello'))->getPath();
        return 'Try ' . urldecode($uri);
    }

    /**
     * Greets you with your name
     *
     * @param $name
     * @return string
     */
    public function sayHello($name)
    {
        return "Hello $name";
    }

    /**
     * Returns a histogram of a Twitter's user in a single date. If no date is specified, today's date will be used
     *
     * @param Request $request
     * @param string $username
     * @return JsonResponse
     */
    public function showHistogram(Request $request, $username)
    {
        $date = $request->get('date');
        $histogram = app(TwitterAPI::class)->getUserHistogram($username, $date);
        return response()->json($histogram)->setEncodingOptions(JSON_FORCE_OBJECT);
    }
}