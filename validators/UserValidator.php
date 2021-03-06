<?php
namespace cleantalk\antispam\validators;

use Yii;
use yii\validators\Validator;

/**
 * Validates user registration (sign up for) email.
 * Additional param is nickName.
 */
class UserValidator extends Validator
{
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
        $nick = '';
        if ($this->nickNameAttribute) {
            $nick = $model->{$this->nickNameAttribute};
        }
        list($valid, $comment) = $api->isAllowUser($model->$attribute, $nick);
        if (!$valid) {
            if ($this->message !== null) {
                $comment = $this->message;
            }
            $this->addError($model, $attribute, $comment);
        }
    }
}