<?php

namespace App\Http\Controllers;

use App\Libraries\Twitter\TwitterAPI;
use Illuminate\Http\Request;

class APIController extends Controller
{
    public function giveHint()
    {
        //return 'Try /hello/:name';
        return 'Try '.route('sayHello');
    }

    public function sayHello($name)
    {
        return "Hello $name";
    }

    public function showHistogram(Request $request, $username)
    {
        $date = $request->input('date');
        $api = new TwitterAPI();
        $histogram = $api->getUserHistogram($username, $date);
        return response()->json($histogram)->setEncodingOptions(JSON_FORCE_OBJECT);
    }
}