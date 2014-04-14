<?php namespace Chekun\Oauth2\Provider;

use Chekun\Oauth2\Oauth2Provider;
use Chekun\Oauth2\Oauth2ProviderInterface;
use Chekun\Oauth2\Oauth2Exception;
use Chekun\Oauth2\Token\AccessToken;
use Chekun\Oauth2\Token\Token;

class Weibo extends Oauth2Provider implements Oauth2ProviderInterface {

    const API_URL = 'https://api.weibo.com/2/';

    public $name = 'weibo';

    public $human = '新浪微博';

    public $uidKey = 'uid';

    public $method = 'POST';

    public function urlAuthorize()
    {
        return 'https://api.weibo.com/oauth2/authorize';
    }

    public function urlAccessToken()
    {
        return 'https://api.weibo.com/oauth2/access_token';
    }

    public function access($code, $options = array())
    {
        if (isset($_GET["error"])) {
            $errcode = $_GET["error_code"];
            $errdesc = $_GET["error_description"];
            throw new Oauth2Exception(["code" => $errcode, "message" => $errdesc]);
        }
        return parent::access($code, $options);
    }

    // https://github.com/xiaosier/libweibo/blob/master/saetv2.ex.class.php#L215
    public function signedRequest($sign, $app_secret = null, $options = array())
    {
        if (!$app_secret) {
            if (isset($this->options["signed_request_secret"])) {
                $app_secret = $this->options["signed_request_secret"];
            } else {
                $app_secret = $this->clientSecret;
            }
        }
        list($encoded_sig, $payload) = explode('.', $sign, 2);
        $sig = static::b64decode($encoded_sig) ;
        $data = json_decode(static::b64decode($payload), true);
        if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
            throw new Oauth2Exception(
                "Unexpected algorithm: expected HMAC-SHA256 but get `{$data["algorithm"]}`."
            );
        }
        $expected_sig = hash_hmac('sha256', $payload, $app_secret, true);
        if ($sig !== $expected_sig) {
            throw new Oauth2Exception("Unexpected Signature.");
        }
        $data["access_token_key"] = "oauth_token";
        $data["uid_key"] = "user_id";
        return Token::factory("access", $data);
    }

    static public function b64decode($str)
    {
        return base64_decode(strtr($str.str_repeat('=', (4 - strlen($str) % 4)), '-_', '+/'));
    }

    public function getUserInfo(AccessToken $token)
    {
        $url = static::API_URL . 'users/show.json?'.http_build_query(array(
                'access_token' => $token->accessToken,
                'uid' => $token->uid,
            ));
        $user = json_decode($this->client->get($url)->getContent());
        if (array_key_exists("error", $user)) {
            throw new OAuth2Exception((array) $user);
        }
        return array(
            'via' => 'weibo',
            'uid' => $user->id,
            'screen_name' => $user->screen_name,
            'name' => $user->name,
            'location' => $user->location,
            'description' => $user->description,
            'image' => $user->profile_image_url,
            'access_token' => $token->accessToken,
            'expire_at' => $token->expires,
            'refresh_token' => $token->refreshToken
        );
    }

    public function statuses_update(AccessToken $token, $status)
    {
        $url = static::API_URL . "statuses/update.json?" . http_build_query(array(
            'access_token' => $token->accessToken,
            'status' => $status,
        ));
        $resp = $this->client->post($url);
        return json_decode($resp->getContent(), true);
    }

    public function statuses_upload_url_text(AccessToken $token, $status, $pic)
    {
        $url = static::API_URL . "statuses/upload_url_text.json?" . http_build_query(array(
            'access_token' => $token->accessToken,
            'status' => $status,
            "url" => $pic,
        ));
        $resp = $this->client->post($url);
        return json_decode($resp->getContent(), true);
    }
}