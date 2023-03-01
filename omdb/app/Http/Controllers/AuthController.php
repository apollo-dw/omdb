<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Username;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function callback(Request $request) {
		$state = json_decode(urldecode($request->state), true);
		// $csrf_token = $state["csrf_token"];
		$redirect_url = $state["redirect_url"];

		$code = $_GET["code"];
		$fields = json_encode(array(
            "client_id" => intval(config('auth.osu_client_id')),
			"client_secret" => config('auth.osu_client_secret'),
			"code" => $code,
			"grant_type" => "authorization_code",
			"redirect_uri" => URL::to("/auth/callback"),
		));

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://osu.ppy.sh/oauth/token',
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_POST => true,
		  CURLOPT_POSTFIELDS => $fields,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		));

		$response = curl_exec($curl);
		curl_close($curl);

		$json = json_decode($response, true);
		info("SHIET", ['response' => $response]);

		$access_token = $json["access_token"];
		$refresh_token = $json["refresh_token"];
		$expiresIn = (int) $json["expires_in"];

		// TODO: Replace with GUZZLE
		// https://laravel.com/docs/10.x/http-client
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://osu.ppy.sh/api/v2/me/osu',
		  CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'Authorization: Bearer ' . $access_token],
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		));

		$response = curl_exec($curl);
		curl_close($curl);

		$json = json_decode($response, true);
		$user_id = intval($json["id"]);
		$username = $json["username"];

		$user = User::updateOrCreate(
			['id' => $user_id],
			[
				'access_token' => $access_token,
				'refresh_token' => $refresh_token,
			],
		);

		Username::updateOrCreate(
			['user_id' => $user_id],
			['username' => $username],
		);

		Auth::login($user);

		/*
		$stmt = $conn->prepare("SELECT * FROM `users` WHERE `UserID` = ?");
		$stmt->bind_param("s", $userId);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result && $result->num_rows == 0) {
			$stmt = $conn->prepare("INSERT INTO `users` (UserID, Username, AccessToken, RefreshToken) VALUES (?, ?, ?, ?);");
			$stmt->bind_param("ssss", $userId, $username, $accessToken, $refreshToken);
			$stmt->execute();
			$stmt->close();
		} else {
			$stmt = $conn->prepare("UPDATE `users` SET `AccessToken` = ?, `RefreshToken` = ? WHERE `UserID` = ?");
			$stmt->bind_param("sss", $accessToken, $refreshToken, $userId);
			$stmt->execute();
			$stmt->close();
		}
		 */

		return redirect($redirect_url);
    }
}
