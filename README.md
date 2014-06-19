Yii GraylogRoute
============

Log route for Yii

* [Download latest release](https://github.com/57uff3r/yii-graylog/releases)
* Put GraylogRoute.php into your Yii extensions directory.


Put some settings into your log component config
```php
'log' => array(
  'class' => 'CLogRouter',
  'routes' => array(
    [
      'class'   => 'application.extensions.GrayLogRoute',
      'levels'  => 'info,error,warning',
      'exclude' => 'exception.CHttpException.*',
      'server'  => 'https://127.0.0.1:12201/gelf'
    ],
  ),
)
```

You can exclude some log messages (like 404 errors) with 'exclude' parameter.

