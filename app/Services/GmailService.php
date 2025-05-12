<?php
namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

class GmailService
{
    public function send($to, $subject, $htmlBody)
    {
        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GMAIL_REDIRECT_URI'));
        $client->setAccessType('offline');
        $client->setScopes(['https://www.googleapis.com/auth/gmail.send']);

        $client->setAccessToken([
            'access_token' => env('GMAIL_ACCESS_TOKEN'),
            'refresh_token' => env('GMAIL_REFRESH_TOKEN'),
            'expires_in' => 3600,
            'created' => time() - 3600,
        ]);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken(env('GMAIL_REFRESH_TOKEN'));
        }

        $gmail = new Gmail($client);

        $rawMessage = "From: <" . env('GMAIL_FROM', 'oleholu@gmail.com') . ">\r\n";
        $rawMessage .= "To: <$to>\r\n";
        $rawMessage .= "Subject: $subject\r\n";
        $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
        $rawMessage .= $htmlBody;

        $encodedMessage = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

        $message = new Message();
        $message->setRaw($encodedMessage);

        return $gmail->users_messages->send('me', $message);
    }
}
