<?php
namespace cleantalk\antispam;

use Cleantalk;
use CleantalkRequest;
use InvalidArgumentException;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Json;

/**
 * CleanTalk API application component.
 * Required set apiKey property.
 *
 * @version 2.0.0
 * @author CleanTalk (welcome@cleantalk.ru)
 * @copyright (C) 2015 Сleantalk team (http://cleantalk.org)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */
class Api extends Component
{
    const AGENT_VERSION = 'yii2-2.0.0';
    const FORM_SUBMIT_START_TIME = 'cleantalk_form_submit_start_time';

    /** @var string API key */
    public $apiKey;

    /** @var string API URL */
    public $apiUrl = 'http://moderate.cleantalk.ru';

    /** @var string API response lang en|ru */
    public $responseLang;

    /** @var bool enable logging */
    public $enableLog = true;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (is_null($this->apiKey)) {
            throw new InvalidConfigException('CleanTalkApi configuration must have "apiKey" value');
        }
        if (is_null($this->responseLang)) {
            $this->responseLang = isset(Yii::$app->language) ? substr(Yii::$app->language, 0, 2) : 'en';
        }
    }

    /**
     * Check if user registration allow
     * @param string $email user email
     * @param string $nickName user nickName
     * @return array [bool, string] true, if user registration allow, false with text comment
     */
    public function isAllowUser($email = '', $nickName = '')
    {
        $ctRequest = $this->createRequest();
        $ctRequest->sender_email = $email;
        $ctRequest->sender_nickname = $nickName;

        $ctResult = $this->sendRequest($ctRequest, 'isAllowUser');

        if ($ctResult->inactive == 1) {
            $this->log(sprintf('Need admin approval for "isAllowUser": %s', $ctResult->comment));
        }

        return [$ctResult->allow == 1, $ctResult->comment];
    }

    /**
     * Check if user text message allow
     * @param string $email user email
     * @param string $nickName user nickName
     * @param string $message message
     * @return array [bool, string] true, if user message allow, false with text comment
     */
    public function isAllowMessage($message, $email = '', $nickName = '')
    {
        $ctRequest = $this->createRequest();
        $ctRequest->message = $message;
        $ctRequest->sender_email = $email;
        $ctRequest->sender_nickname = $nickName;

        $ctResult = $this->sendRequest($ctRequest, 'isAllowMessage');

        if ($ctResult->inactive == 1) {
            $this->log(sprintf('Need admin approval for "isAllowMessage": %s', $ctResult->comment));
        }

        return [$ctResult->allow == 1, $ctResult->comment];
    }


    /**
     * Generate form Javascript check code
     * @return string
     */
    public function getCheckJsCode()
    {
        return md5($this->apiKey . __FILE__);
    }

    /**
     * Set begin time of submitting form
     */
    public function startFormSubmitTime($id)
    {
        Yii::$app->session->set(self::FORM_SUBMIT_START_TIME . $id, time());
    }

    /**
     * Get form submit time in seconds.
     * @return int|null
     */
    public function getFormSubmitTime($id)
    {
        $startTime = Yii::$app->session->get(self::FORM_SUBMIT_START_TIME . $id);
        return $startTime > 0 ? time() - $startTime : null;
    }

    /**
     * Is javascript enabled
     * @return int
     */
    public function isJavascriptEnable()
    {
        $formJsCode = isset($_POST['ct_checkjs']) ? $_POST['ct_checkjs'] : null;
        return $formJsCode == $this->getCheckJsCode() ? 1 : 0;
    }

    /**
     * Create request for CleanTalk API.
     * @return \CleantalkRequest
     */
    protected function createRequest()
    {
        $ctRequest = new CleantalkRequest();
        $ctRequest->auth_key = $this->apiKey;
        $ctRequest->response_lang = $this->responseLang;
        $ctRequest->agent = self::AGENT_VERSION;
        $ctRequest->sender_ip = Yii::$app->request->getUserIP();
        $ctRequest->submit_time = $this->getFormSubmitTime('');
        $ctRequest->js_on = $this->isJavascriptEnable();

        $ctRequest->sender_info = Json::encode(
            [
                'REFFERRER' => Yii::$app->request->getReferrer(),
                'USER_AGENT' => Yii::$app->request->getUserAgent(),
                'cms_lang' => $this->responseLang,
            ]
        );
        return $ctRequest;
    }

    /**
     * @param \CleantalkRequest $request
     * @param string $method
     * @return \CleantalkResponse CleanTalk API call result
     * @throws InvalidArgumentException
     */
    protected function sendRequest($request, $method)
    {
        $ct = new Cleantalk();
        $ct->server_url = $this->apiUrl;
        if ($method != 'isAllowMessage' && $method != 'isAllowUser') {
            throw new InvalidArgumentException('Method unknown');
        }

        return $ct->$method($request);
    }

    /**
     * @param string $message
     */
    protected function log($message)
    {
        if ($this->enableLog) {
            Yii::info($message, 'ext.cleantalk');
        }
    }

}