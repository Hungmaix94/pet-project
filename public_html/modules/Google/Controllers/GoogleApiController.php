<?php

namespace Modules\Google\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PulkitJalan\Google\Facades\Google;

class GoogleApiController extends Controller
{
    public function handle(Request $request)
    {

        $googleClient = Google::getClient();
        dd($googleClient);
    }

}