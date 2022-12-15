<?php

namespace App\Http\Controllers\ShouldaCouldaWoulda;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SelectLeagueController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        if($request->has('league'))
        {
            dd('league');
        }

        return view('shoulda-coulda-woulda.league-select');
    }
}
