# Oauth2 For Laravel 4

将下面一行加入app.providers

```
'Chekun\Oauth2\Oauth2ServiceProvider'
```

将下面一行加入app.aliases

```
'Oauth2'         => 'Chekun\Oauth2\Oauth2Facade'
```

使用方法

```
$provider = Oauth2::make('weibo');
```

跳转到服务商的授权页面：

```
Redirect::to($provider->authorize());
```

在授权操作后的回调页面中：

```
$token = $provider->access(Request::query("code"));
$info = $provider->getUserInfo($token);
```

一个示例——配合 [jenssegers/Laravel-Agent](https://github.com/jenssegers/Laravel-Agent) 做登录，在微信中打开则请求微信授权，否则请求新浪微博授权：


```
Route::get("login", function(){
    if (Auth::guest()) {
        if (Agent::isMobile() && Agent::match("MicroMessenger")) {
            $provider = Oauth2::make("wechat");
        } else {
            $provider = Oauth2::make("weibo");
            if (Agent::isMobile()) {
                $provider->setParams("display", "mobile");
            }
        }
        return Redirect::to($provider->authorize());
    }
    return Redirect::to("/");
});
```

本程序也可以用在其他框架和普通 PHP 下.
