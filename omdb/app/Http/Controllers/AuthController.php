<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $redirect_url = URL::to("/");
        if ($request->has('redirect_url'))
            $redirect_url = $request->redirect_url;

        $state = array(
            "redirect_url" => $redirect_url,
        );
        $state_encoded = urlencode(json_encode($state));

        $oauthFields = array(
            "client_id" => config('auth.osu_client_id'),
            "redirect_uri" => URL::to("/auth/callback"),
            "response_type" => "code",
            "scope" => "identify public",
            "state" => $state_encoded,
        );

        $url = 'https://osu.ppy.sh/oauth/authorize?' . http_build_query($oauthFields);

        return redirect()->away($url);
    }
}
