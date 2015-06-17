<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
define('CLEANTALK_TEST_API_KEY', 'CleanTalk some api key');

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

Yii::setAlias('@cleantalk/antispam', dirname(__DIR__));

new \yii\web\Application(['id' => 'test-yii2-antispam', 'basePath' => __DIR__]);