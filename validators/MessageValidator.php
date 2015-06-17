<?php
namespace cleantalk\antispam\validators;

use Yii;
use yii\validators\Validator;

class MessageValidator extends Validator
{
    /** @var string Email attribute name in model */
    public $emailAttribute;

    /** @var string Nickname attribute name in model */
    public $nickNameAttribute;

    /** @var string CleanTalk application component ID */
    public $apiComponentId = 'antispam';

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        /**
         * @var \cleantalk\antispam\Component $api
         */
        $api = Yii::$app->get($this->apiComponentId);
        $email = $nick = '';
        if ($this->emailAttribute) {
            $email = $model->{$this->emailAttribute};
        }
        if ($this->nickNameAttribute) {
            $nick = $model->{$this->nickNameAttribute};
        }
        list($valid, $comment) = $api->isAllowMessage($model->$attribute, $email, $nick);
        if (!$valid) {
            $this->addError($model, $attribute, $comment);
        }
    }
}