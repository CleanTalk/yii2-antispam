# Spam Bot Protection for Yii2 Forms — reCAPTCHA Alternative.

Anti-spam extension for the Yii2 framework powered by the CleanTalk API.  
Invisible protection from spam bots on contact, comment, and registration forms — no reCAPTCHA, no puzzles, no delays.

## Features
- Works invisibly in the background (no user interaction required)
- Blocks spam bots using CleanTalk’s multi-layered checks
- reCAPTCHA-free UX — no puzzles, images, or questions
- Easy integration into Yii2 forms and models
- Supports contact forms, comment forms, and registration forms


[![Build Status](https://travis-ci.org/CleanTalk/yii2-antispam.svg)](https://travis-ci.org/cleantalk/yii2-antispam)



## Requirements

* Yii 2.0 or above
* CleanTalk account https://cleantalk.org/register?product=anti-spam

##Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist cleantalk/yii2-antispam
```

or add

```json
"cleantalk/yii2-antispam": "~1.0.0"
```

to the require section of your composer.json.

##Usage

1) Get access key on https://cleantalk.org/register?platform=yii2

2) Open your application configuration in protected/config/web.php and modify components section:

```
// application components
'components'=>[
    ...
        'antispam' => [
            'class' => 'cleantalk\antispam\Component',
            'apiKey' => 'Your API KEY',
        ],
    ...
],
```

3) Add validator in your model, for example ContactForm:

```
namespace app\models;

use cleantalk\antispam\validators\MessageValidator;
use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class ContactForm extends Model
{
    public $name;
    public $email;
    public $body;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // name, email, subject and body are required
            [['name', 'email', 'subject', 'body'], 'required'],
            // email has to be a valid email address
            ['email', 'email'],
            ['body', MessageValidator::className(), 'emailAttribute'=>'email', /*'nickNameAttribute'=>'name'*/]
        ];
    }
}
```

4) In form view add widget for hidden Javascript checks:

```
<?php $form = ActiveForm::begin(); ?>
    ...
    <?= \cleantalk\antispam\Widget::widget()?>
    ...
    <?= Html::submitButton('Submit')?>
    ...
<?php ActiveForm::end(); ?>

```

## User registration validator

See cleantalk\antispam\validators\UserValidator

Example rules:
```
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'email'], 'required'],
            ['email', 'email'],
            ['email', UserValidator::className(), 'nickNameAttribute'=>'name']
        ];
    }
```    

##License
GNU General Public License

##Resources

 * https://cleantalk.org/
 * https://github.com/CleanTalk/yii2-antispam
