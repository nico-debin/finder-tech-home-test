<?php

namespace App\Http\Controllers;

use App\Libraries\Twitter\TwitterAPI;

class APIController extends Controller
{
    public function giveHint()
    {
        return 'Try /hello/:name';
    }

    public function sayHello($name)
    {
        return "Hello $name";
    }

    public function showHistogram($username)
    {
        $api = new TwitterAPI();
        $histogram = $api->getUserHistogram($username);
        return response()->json($histogram);
    }
}