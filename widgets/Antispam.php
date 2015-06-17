<?php
namespace cleantalk\antispam\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;

class Antispam extends Widget
{
    /** @var string CleanTalk application component ID */
    public $apiComponentId = 'antispam';

    public $checkJsHtmlId;

    public function init()
    {
        parent::init();
        if (is_null($this->checkJsHtmlId)) {
            $this->checkJsHtmlId = md5(rand(0, 1000));
        }
    }


    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->registerClientScript();
        $this->getApi()->startFormSubmitTime($this->id);
        return Html::hiddenInput('ct_checkjs', -1, ['id' => $this->checkJsHtmlId]);
    }

    protected function  registerClientScript()
    {
        $js = 'setTimeout(function(){document.getElementById("' . $this->checkJsHtmlId . '").value="' . $this->getApi()->getCheckJsCode() . '";}, 1000)';
        $this->getView()
            ->registerJs($js);
    }

    /**
     * @return \cleantalk\antispam\Api
     */
    protected function getApi()
    {
        return Yii::$app->get($this->apiComponentId);
    }

}