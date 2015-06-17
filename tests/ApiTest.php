<?php
namespace cleantalk\antispam\tests;

use cleantalk\antispam\Api;
use CleantalkRequest;
use CleantalkResponse;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use ReflectionClass;
use Yii;

/**
 * @coversDefaultClass \cleantalk\antispam\Api
 */
class ApiTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \cleantalk\antispam\Api
     */
    protected $component;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->component = Yii::createObject(['class' => Api::className(), 'apiKey' => CLEANTALK_TEST_API_KEY]);
    }

    /**
     * @expectedException \yii\base\InvalidConfigException
     */
    public function testInit()
    {
        Yii::createObject(['class' => Api::className(), 'apiKey' => null]);
    }

    public function testIsAllowUser()
    {
        $mock = $this->getSendRequestMock(
            array(
                'allow' => 1,
                'comment' => ''
            )
        );
        list($result, $comment) = $mock->isAllowUser('allow@email.ru', 'Ivan Petrov');
        $this->assertTrue($result);

        $mock = $this->getSendRequestMock(
            array(
                'allow' => 0,
                'comment' => 'Mock deny'
            )
        );
        list($result, $comment) = $mock->isAllowUser('deny@email.ru', 'Ivan Petrov');
        $this->assertFalse($result);
        $this->assertEquals('Mock deny', $comment);

        $mock = $this->getSendRequestMock(
            array(
                'inactive' => 1,
                'comment' => 'Mock deny'
            )
        );
        list($result, $comment) = $mock->isAllowUser('deny@email.ru', 'Ivan Petrov');
        $this->assertFalse($result);
    }

    public function testIsAllowMessage()
    {
        $mock = $this->getSendRequestMock(
            array(
                'allow' => 1,
                'comment' => ''
            )
        );

        list($result, $comment) = $mock->isAllowMessage('message1');
        $this->assertTrue($result);

        $mock = $this->getSendRequestMock(
            array(
                'allow' => 0,
                'comment' => 'Mock deny'
            )
        );
        list($result, $comment) = $mock->isAllowMessage('bad message');
        $this->assertFalse($result);
        $this->assertEquals('Mock deny', $comment);
    }

    public function testGetCheckJsCode()
    {
        $this->assertRegExp('#\w+#', $this->component->getCheckJsCode());
    }


    public function testStartFormSubmitTime()
    {
        // $this->component->startFormSubmitTime();
    }


    public function testIsJavascriptEnable()
    {
        $_POST['ct_checkjs'] = $this->component->getCheckJsCode();

        $this->assertEquals(1, $this->component->isJavascriptEnable());

        unset($_POST['ct_checkjs']);
        $this->assertEquals(0, $this->component->isJavascriptEnable());
    }

    public function testCreateRequest()
    {
        $class = new ReflectionClass($this->component);
        $method = $class->getMethod('createRequest');
        $method->setAccessible(true);
        $request = $method->invoke($this->component);
        $this->assertInstanceOf('CleantalkRequest', $request);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSendRequest()
    {
        $class = new ReflectionClass($this->component);
        $method = $class->getMethod('sendRequest');
        $method->setAccessible(true);
        $method->invoke($this->component, new CleantalkRequest(), 'ololo');
    }

    protected function getSendRequestMock($response)
    {
        $mock = $this->getMock(Api::className(), ['sendRequest'], [['apiKey' => CLEANTALK_TEST_API_KEY]]);
        $mock->expects($this->once())
            ->method('sendRequest')
            ->will(
                $this->returnValue(
                    new CleantalkResponse(
                        $response
                    )
                )
            );
        return $mock;
    }
}