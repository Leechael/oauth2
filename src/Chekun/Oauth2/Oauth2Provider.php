<?php namespace Chekun\Oauth2;

use Chekun\Oauth2\Token\Token;
use Chekun\Oauth2\OAuth2Session;
use Buzz\Browser;
use Buzz\Client\Curl;

abstract class OAuth2Provider {

    public $name = '';

    public $human = '';

    public $stateKey = 'state';

    public $errorKey = 'error';

    public $clientIdKey = 'client_id';

    public $clientSecretKey = 'client_secret';

    public $redirectUriKey = 'redirect_uri';

    public $accessTokenKey = 'access_token';

    public $uidKey = 'uid';

    public $callback = null;

    protected $params = array();

    protected $method = 'GET';

    protected $scope = '';

    protected $scopeSeperator = ',';

    protected $client = null;

    public function __construct(array $options = array())
    {
        if ( ! $this->name) {
            $this->name = strtolower(substr(get_class($this), strlen('OAuth2_Provider_')));
        }

        if (empty($options['id'])) {
            throw new \Exception('Required option not provided: id');
        }

        $this->clientId = $options['id'];

        isset($options['callback']) and $this->callback = $options['callback'];
        isset($options['secret']) and $this->clientSecret = $options['secret'];
        isset($options['scope']) and $this->scope = $options['scope'];

        $this->redirectUri = $options['redirect_uri'];

        $this->client = new Browser(new Curl());
    }

    public function __get($key)
    {
        return $this->$key;
    }

    public function authorize($options = array())
    {
        $state = md5(uniqid(rand(), true));
        OAuth2Session::write("oauth2_state", $state);
        $params = array(
            $this->clientIdKey 		=> $this->clientId,
            $this->redirectUriKey 	=> isset($options[$this->redirectUriKey]) ? $options[$this->redirectUriKey] : $this->redirectUri,
            $this->stateKey 		=> $state,
            'scope'				=> is_array($this->scope) ? implode($this->scopeSeperator, $this->scope) : $this->scope,
            'response_type' 	=> 'code',
            'approval_prompt'   => 'force' // - google force-recheck
        );

        $params = array_merge($params, $this->params);

        $url = $this->urlAuthorize().'?'.http_build_query($params);

        return $url;
    }

    public function access($code, $options = array())
    {
        if (isset($_GET[$this->stateKey]) AND $_GET[$this->stateKey] != OAuth2Session::read('oauth2_state'))
        {
            throw new Oauth2Exception(array('code' => '403', 'message' => 'The state does not match. Maybe you are a victim of CSRF.'));
        }
        $params = array(
            $this->clientIdKey 	=> $this->clientId,
            $this->clientSecretKey => $this->clientSecret,
            'grant_type' 	=> isset($options['grant_type']) ? $options['grant_type'] : 'authorization_code',
        );

        $params = array_merge($params, $this->params);

        switch ($params['grant_type'])
        {
            case 'authorization_code':
                $params['code'] = $code;
                $params[$this->redirectUriKey] = isset($options[$this->redirectUriKey]) ? $options[$this->redirectUriKey] : $this->redirectUri;
                break;

            case 'refresh_token':
                $params['refresh_token'] = $code;
                break;
        }

        $response = null;
        $url = $this->urlAccessToken();

        switch ($this->method)
        {
            case 'GET':
                $url .= '?'.http_build_query($params);
                $response = $this->client->get($url)->getContent();
                $return = $this->parseResponse($response);
                break;
            case 'POST':
                $response = $this->client->submit($url, $params)->getContent();
                $return = $this->parseResponse($response);
                break;
            default:
                throw new \OutOfBoundsException("Method '{$this->method}' must be either GET or POST");
        }
		
        if ( ! empty($return[$this->errorKey]) OR ! isset($return['access_token'])) {
            throw new OAuth2Exception($return);
        }

        $return['uid_key'] = $this->uidKey;
        $return['access_token_key'] = $this->accessTokenKey;

        switch ($params['grant_type'])
        {
            case 'authorization_code':
                return Token::factory('access', $return);
                break;

            case 'refresh_token':
                return Token::factory('refresh', $return);
                break;
        }

    }

    protected function parseResponse($response = '')
    {
        if (strpos($response, "callback") !== false) {
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response  = substr($response, $lpos + 1, $rpos - $lpos - 1);
            $return = json_decode($response, true);
        } elseif (strpos($response, "&") !== false) {
            parse_str($response, $return);
        } else {
            $return = json_decode($response, true);
        }
        return $return;
    }

    public function request($url, $method = 'get', $content = null)
    {
        $method = strtolower($method);
        if ($content) {
            if ($method == 'submit') {
                $response = $this->client->$method($url, $content)->getContent();
            } else {
                $response = $this->client->$method($url, array(), $content)->getContent();
            }
        } else {
            $response = $this->client->$method($url)->getContent();
        }
        return $this->parseResponse($response);
    }


}