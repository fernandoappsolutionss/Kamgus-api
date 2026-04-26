<?php


namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Google\Client;
use Illuminate\Support\Facades\Cache;

class K_FirebaseChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $project = "kamgus";
        $url = 'https://fcm.googleapis.com/v1/projects/'.$project.'/messages:send';
        $data = $notification->toFirebase($notifiable);
        
        //consulta la variable 'k_firebase_access_token' en Cache::remember y si no existe, solicita una nueva usando GuzzleHttp
        //Cache::forget('k_firebase_access_token');
        $accessToken = Cache::remember('k_firebase_access_token', 3600, function () use ($url, $data) {
            // Here you would send the notification to Firebase
            // For example, using Guzzle to make an HTTP request to Firebase API
            $token = $this->getAccessToken();
            return $token['token_type']." ".$token['access_token'];
            
            //return $response;
            
        });
        //dd($token);
        $client = new \GuzzleHttp\Client();
        $response = [];
        //dd($data['message']["token"]);
        foreach ($data['message']["data"] as $key => $value) {
            $data['message']["data"][$key] = strval($value);
        }
        if(is_array($data['message']["token"])){
            $tokens = $data['message']["token"];
            foreach($tokens as $token){
                $data['message']["token"] = $token;
                try {
                    //code...
                    //$response[] = $client->post($url, [
                    $response[] = $client->postAsync($url, [
                        'headers' => [
                            'Authorization' => $accessToken,
                            'Content-Type' => 'application/json',
                        ],
                        'json' => $data,
                    ]);
                } catch (\Throwable $th) {
                    //echo $th->getCode();
                    break;
                }
            }
        }
        return $response;

    }

    public function getAccessToken() {
        $client = new Client();
        // Loads service account from a non-public location.
        // Path is configured in config/larafirebase.php → 'firebase_credentials' (defaults to storage/app/firebase/service-account.json).
        $client->setAuthConfig(config('larafirebase.firebase_credentials'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging'); // Add necessary scopes

        try {
            $client->fetchAccessTokenWithAssertion();
            $accessToken = $client->getAccessToken();
            return ($accessToken);
        } catch (\Exception $e) {
            return ($e->getMessage());
        }
    }
}