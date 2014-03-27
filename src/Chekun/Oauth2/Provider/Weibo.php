<?php namespace Chekun\Oauth2\Provider;

use Chekun\Oauth2\Oauth2Provider;
use Chekun\Oauth2\Oauth2ProviderInterface;
use Chekun\Oauth2\Oauth2Exception;
use Chekun\Oauth2\Token\AccessToken;

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
}