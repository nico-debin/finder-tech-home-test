<?php

namespace App\Http\Controllers;

use App\Libraries\Twitter\TwitterAPI;
use function GuzzleHttp\Psr7\uri_for;
use Illuminate\Http\Request;

class APIController extends Controller
{
    public function giveHint()
    {
        $uri = uri_for(route('sayHello'))->getPath();
        return 'Try '.urldecode($uri);
    }

    public function sayHello($name)
    {
        return "Hello $name";
    }

    public function showHistogram(Request $request, $username)
    {
        $date = $request->get('date');
        $api = app(TwitterAPI::class);
        $histogram = $api->getUserHistogram($username, $date);
        return response()->json($histogram)->setEncodingOptions(JSON_FORCE_OBJECT);
    }
}