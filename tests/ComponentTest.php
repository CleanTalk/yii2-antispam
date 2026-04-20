<?php
namespace cleantalk\antispam\tests;

use cleantalk\antispam\Component;
use Cleantalk\CleantalkRequest;
use Cleantalk\CleantalkResponse;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Yii;
use yii\base\InvalidConfigException;

/**
 * @coversDefaultClass \cleantalk\antispam\Component
 */
class ComponentTest extends TestCase
{
	/**
	 * @var \cleantalk\antispam\Component
	 */
	protected $component;

	/**
	 * @inheritdoc
	 */
	protected function setUp(): void
	{
		$this->component = Yii::createObject(['class' => Component::class, 'apiKey' => CLEANTALK_TEST_API_KEY]);
	}

	public function testInit()
	{
		$this->expectException(InvalidConfigException::class);
		Yii::createObject(['class' => Component::class, 'apiKey' => null]);
	}

	public function testIsAllowUser()
	{
		$mock = $this->getSendRequestMock([
			'allow'   => 1,
			'comment' => ''
		]);
		[$result, $comment] = $mock->isAllowUser('allow@email.ru', 'Ivan Petrov');
		$this->assertTrue($result);

		$mock = $this->getSendRequestMock([
			'allow'   => 0,
			'comment' => 'Mock deny'
		]);
		[$result, $comment] = $mock->isAllowUser('deny@email.ru', 'Ivan Petrov');
		$this->assertFalse($result);
		$this->assertEquals('Mock deny', $comment);

		$mock = $this->getSendRequestMock([
			'inactive' => 1,
			'comment'  => 'Mock deny'
		]);
		[$result, $comment] = $mock->isAllowUser('deny@email.ru', 'Ivan Petrov');
		$this->assertFalse($result);
	}

	public function testIsAllowMessage()
	{
		$mock = $this->getSendRequestMock([
			'allow'   => 1,
			'comment' => ''
		]);

		[$result, $comment] = $mock->isAllowMessage('message1');
		$this->assertTrue($result);

		$mock = $this->getSendRequestMock([
			'allow'   => 0,
			'comment' => 'Mock deny'
		]);
		[$result, $comment] = $mock->isAllowMessage('bad message');
		$this->assertFalse($result);
		$this->assertEquals('Mock deny', $comment);
	}

	public function testGetCheckJsCode()
	{
		$this->assertMatchesRegularExpression('#\w+#', $this->component->getCheckJsCode());
	}


	public function testStartFormSubmitTime()
	{
		$this->component->startFormSubmitTime('');
		sleep(2);
		$time = $this->component->calcFormSubmitTime('');
		$this->assertGreaterThanOrEqual(1, $time);
	}


	public function testIsJavascriptEnable()
	{
		Yii::$app->request->setBodyParams(['ct_checkjs' => $this->component->getCheckJsCode()]);
		$this->assertEquals(1, $this->component->isJavascriptEnable());

		Yii::$app->request->setBodyParams([]);
		$this->assertEquals(0, $this->component->isJavascriptEnable());
	}

	public function testCreateRequest()
	{
		$class = new ReflectionClass($this->component);
		$method = $class->getMethod('createRequest');
		$method->setAccessible(true);
		$request = $method->invoke($this->component);
		$this->assertInstanceOf(CleantalkRequest::class, $request);
	}

	public function testSendRequest()
	{
		$this->expectException(InvalidArgumentException::class);
		$class = new ReflectionClass($this->component);
		$method = $class->getMethod('sendRequest');
		$method->setAccessible(true);
		$method->invoke($this->component, new CleantalkRequest(), 'ololo');
	}

	protected function getSendRequestMock($response)
	{

		$mock = $this->createPartialMock(Component::class, ['sendRequest']);
		$mock->apiKey = CLEANTALK_TEST_API_KEY;
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