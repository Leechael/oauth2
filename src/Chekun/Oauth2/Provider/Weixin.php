<?php namespace Chekun\Oauth2\Provider;

use Chekun\Oauth2\Oauth2Provider;
use Chekun\Oauth2\Oauth2ProviderInterface;
use Chekun\Oauth2\Oauth2Exception;
use Chekun\Oauth2\Token\AccessToken;

class Weixin extends Oauth2Provider implements Oauth2ProviderInterface {

    const API_URL = 'https://api.weixin.qq.com/';

    public $name = 'weixin';

    public $human = '微信';

    public $uidKey = 'openid';

    public $clientIdKey = 'appid';

    public $clientSecretKey = 'secret';

    protected $scope = 'snsapi_userinfo';

    public $method = 'POST';

    public function urlAuthorize()
    {
        return 'https://open.weixin.qq.com/connect/oauth2/authorize';
    }

    public function urlAccessToken()
    {
        return 'https://api.weixin.qq.com/sns/oauth2/access_token';
    }

    public function getUserInfo(AccessToken $token)
    {
        $url = static::API_URL . 'sns/userinfo?'.http_build_query(array(
                'access_token' => $token->accessToken,
                'openid' => $token->uid,
                'lang' => 'zh_CN'
            ));
        $user = json_decode($this->client->get($url)->getContent());
        if (array_key_exists("errcode", $user)) {
            throw new OAuth2Exception((array) $user);
        }
        return array(
            'via' => 'weixin',
            'uid' => $user->openid,
            'screen_name' => $user->nickname,
            'name' => $user->nickname,
            'sex' => $user->sex,
            'location' => $user->province,
            'description' => '',
            'image' => $user->headimgurl,
            'access_token' => $token->accessToken,
            'expire_at' => $token->expires,
            'refresh_token' => $token->refreshToken
        );
    }
}