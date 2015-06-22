<?php
namespace cleantalk\antispam;

use Yii;
use yii\base\Widget as BaseWidget;
use yii\helpers\Html;

class Widget extends BaseWidget
{
    /** @var string CleanTalk application component ID */
    public $apiComponentId = 'antispam';

    /** @var string hidden field id */
    protected $checkJsHtmlId;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->checkJsHtmlId = md5(rand(0, 1000));
    }


    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->registerClientScript();
        $this->getComponent()->startFormSubmitTime($this->checkJsHtmlId);
        return Html::hiddenInput('ct_checkjs', -1, ['id' => $this->checkJsHtmlId]) .
        Html::hiddenInput('ct_formid', $this->checkJsHtmlId);
    }

    protected function  registerClientScript()
    {
        $js = 'setTimeout(function(){document.getElementById("' . $this->checkJsHtmlId . '").value="' . $this->getComponent()->getCheckJsCode() . '";}, 1000)';
        $this->getView()
            ->registerJs($js);
    }

    /**
     * @return \cleantalk\antispam\Component
     */
    protected function getComponent()
    {
        return Yii::$app->get($this->apiComponentId);
    }

}