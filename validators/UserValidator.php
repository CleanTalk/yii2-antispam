<?php
namespace cleantalk\antispam\validators;

use Yii;
use yii\validators\Validator;

class UserValidator extends Validator
{
    /** @var string Nickname attribute name in model */
    public $nickNameAttribute;

    /** @var string CleanTalk application component ID */
    public $apiComponentId = 'cleanTalk';

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
            $this->addError($model, $attribute, $comment);
        }
    }
}