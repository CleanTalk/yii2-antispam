<?php
namespace cleantalk\antispam;

use Cleantalk\Cleantalk;
use Cleantalk\CleantalkRequest;
use Cleantalk\CleantalkResponse;
use InvalidArgumentException;
use Yii;
use yii\base\Component as BaseComponent;
use yii\base\InvalidConfigException;
use yii\helpers\Json;

/**
 * CleanTalk API application component.
 * Required set apiKey property.
 *
 * @version 1.0.0
 * @author CleanTalk (welcome@cleantalk.org)
 * @copyright (C) 2015 Сleantalk team (https://cleantalk.org)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */
class Component extends BaseComponent
{
    const AGENT_VERSION = 'yii2-1.0.0';
    const KEY_SESSION_FORM_SUBMIT = 'ct_form_submit';

    /** @var string API key */
    public $apiKey;

    /** @var string API URL */
    public $apiUrl = 'https://moderate.cleantalk.org';

    /**
     * @deprecated Use setting in https://cleantalk.org/my/service?action=edit
     * @var string API response lang en|ru
     */
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
     * @param string $id form id
     */
    public function startFormSubmitTime($id)
    {
        Yii::$app->session->set(self::KEY_SESSION_FORM_SUBMIT . $id, time());
    }

    /**
     * Get form submit time in seconds
     * @param string $id form id
     * @param boolean $clear clear value in session
     * @return int|null
     */
    public function calcFormSubmitTime($id = null, $clear = true)
    {
        if ($id === null) {
            $id = Yii::$app->request->post('ct_formid');
        }
        $startTime = Yii::$app->session->get(self::KEY_SESSION_FORM_SUBMIT . $id);
        if ($clear) {
            Yii::$app->session->remove(self::KEY_SESSION_FORM_SUBMIT . $id);
        }
        return $startTime > 0 ? time() - $startTime : null;
    }

    /**
     * Is javascript enabled
     * @return int
     */
    public function isJavascriptEnable()
    {
        return Yii::$app->request->post('ct_checkjs') == $this->getCheckJsCode() ? 1 : 0;
    }

    /**
     * Create request for CleanTalk API.
     * @return CleantalkRequest
     */
    protected function createRequest()
    {
        $ctRequest = new CleantalkRequest();
        $ctRequest->auth_key = $this->apiKey;
        $ctRequest->agent = self::AGENT_VERSION;
        $ctRequest->sender_ip = Yii::$app->request->getUserIP();
        $ctRequest->submit_time = $this->calcFormSubmitTime();
        $ctRequest->js_on = $this->isJavascriptEnable();

        $ctRequest->sender_info = Json::encode(
            [
                'REFFERRER' => Yii::$app->request->getReferrer(),
                'USER_AGENT' => Yii::$app->request->getUserAgent(),
            ]
        );
        return $ctRequest;
    }

    /**
     * @param CleantalkRequest $request
     * @param string $method
     * @return CleantalkResponse CleanTalk API call result
     * @throws InvalidArgumentException
     */
    protected function sendRequest($request, $method)
    {
        $ct = new Cleantalk();
        $ct->server_url = $this->apiUrl;
        if ($method != 'isAllowMessage' && $method != 'isAllowUser') {
            throw new InvalidArgumentException('Method unknown');
        }
        Yii::trace('Sending request to cleantalk:' . var_export($request, true), __METHOD__);

        $response = $ct->$method($request);

        if ($this->enableLog) {
            Yii::info(sprintf('Cleantalk response is allow=%d, inactive=%d, comment=%s', $response->allow, $response->inactive, $response->comment), __METHOD__);
        }
        return $response;
    }
}