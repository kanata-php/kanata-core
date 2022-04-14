<?php

declare(strict_types=1);

use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;
use Kanata\Drivers\SessionTable;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HttpHelpersTest extends TestCase
{
    protected Generator $faker;

    protected function setUp(): void
    {
        $this->faker = Factory::create();
    }

    /**
     * @before
     */
    public function startTable(): void
    {
        SessionTable::getInstance();
    }

    /**
     * @after
     */
    public function tearDownTable(): void
    {
        SessionTable::destroyInstance();
    }

    private function get_request(string $id = 'some-id')
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->session = ['id' => $id];
        return $request;
    }

    public function test_can_set_session()
    {
        $id = 'some-id';

        SessionTable::getInstance()->set($id, ['data' => []]);
        $request = $this->get_request($id);
        set_session($request, ['test' => 'test-data']);
        $newSessionData = SessionTable::getInstance()->get($id);

        $this->assertTrue(isset($newSessionData['test']));
        $this->assertTrue($newSessionData['test'] === 'test-data');
    }

    public function test_can_get_session()
    {
        $id = 'some-id';

        SessionTable::getInstance()->set($id, ['data' => 'some-test-data']);
        $request = $this->get_request($id);

        $this->assertTrue(get_session($request, 'data') === 'some-test-data');
    }

    public function test_can_clear_session()
    {
        $request = $this->get_request();

        // set data
        set_session($request, ['data' => 'some-data']);
        set_session($request, ['data2' => 'some-data-2']);
        set_session($request, ['data3' => 'some-data-3']);
        $this->assertTrue(get_session($request, 'data') === 'some-data');
        $this->assertTrue(get_session($request, 'data2') === 'some-data-2');
        $this->assertTrue(get_session($request, 'data3') === 'some-data-3');

        /// clear only one key
        clear_session($request, 'data');
        $this->assertTrue(get_session($request, 'data') === null);
        $this->assertTrue(get_session($request, 'data2') === 'some-data-2');
        $this->assertTrue(get_session($request, 'data3') === 'some-data-3');

        // clear all
        clear_session($request);
        $this->assertTrue(get_session($request, 'data') === null);
        $this->assertTrue(get_session($request, 'data2') === null);
        $this->assertTrue(get_session($request, 'data3') === null);
    }

    public function test_doesnt_affect_other_request_contexts()
    {
        $request = $this->get_request();
        $request2 = $this->get_request('some-id-2');

        set_session($request, ['data' => 'some-data']);
        $this->assertTrue(get_session($request, 'data') === 'some-data');
        $this->assertTrue(get_session($request2, 'data') === null);
    }

    public function test_can_set_form_session()
    {
        $id = 'some-id';
        $request = $this->get_request($id);
        $uri = Mockery::mock(UriInterface::class);
        $uri->shouldReceive('getPath')->andReturn('/my-form');
        $request->shouldReceive('getUri')->andReturn($uri);

        $email = $this->faker->email();

        $sessionData = SessionTable::getInstance()->get($id);
        $this->assertTrue(!isset($sessionData['form']));

        set_form_session($request, ['email' => $email]);
        $newSessionData = SessionTable::getInstance()->get($id);
        $this->assertTrue(isset($newSessionData['form']));
        $this->assertTrue(isset($newSessionData['form']['/my-form']));
        $this->assertTrue(isset($newSessionData['form']['/my-form']['email']));
        $this->assertTrue($newSessionData['form']['/my-form']['email'] === $email);
    }

    public function test_can_get_form_session()
    {
        $id = 'some-id';
        $request = $this->get_request($id);
        $uri = Mockery::mock(UriInterface::class);
        $uri->shouldReceive('getPath')->andReturn('/my-form');
        $request->shouldReceive('getUri')->andReturn($uri);

        $email = $this->faker->email();

        $formData = get_form_session($request);
        $this->assertTrue(empty($formData));
        
        $data = ['email' => $email];
        set_form_session($request, $data);
        $formData2 = get_form_session($request);
        $this->assertTrue($formData2 === $data);
    }

    public function test_can_clear_form_session()
    {
        $id = 'some-id';
        $request = $this->get_request($id);
        $uri = Mockery::mock(UriInterface::class);
        $uri->shouldReceive('getPath')->andReturn('/my-form');
        $request->shouldReceive('getUri')->andReturn($uri);

        $email = $this->faker->email();
        
        $data = ['email' => $email];
        set_form_session($request, $data);
        $formData = get_form_session($request);
        $this->assertTrue($formData === $data);

        clear_form_session($request);
        $formData2 = get_form_session($request);
        $this->assertTrue($formData2 === []);
    }

    public function test_can_create_flash_message()
    {
        $id = 'some-id';
        $request = $this->get_request($id);

        set_flash_message($request, ['form' => 'Some message to form!']);
        $session_data = SessionTable::getInstance()->get($id);
        $this->assertTrue($session_data['flash-message']['form'] === 'Some message to form!');
    }

    public function test_can_consume_flash_message()
    {
        $id = 'some-id';
        $request = $this->get_request($id);

        set_flash_message($request, ['form' => 'Some message to form!']);
        $message = get_flash_message($request);
        $this->assertTrue($message['form'] === 'Some message to form!');

        // flash messages get erased after first consumption
        $message2 = get_flash_message($request);
        $this->assertTrue($message2 === null);
    }

    public function test_can_clear_flash_message()
    {
        $id = 'some-id';
        $request = $this->get_request($id);

        set_flash_message($request, ['form' => 'Some message to form!']);
        $session_data = SessionTable::getInstance()->get($id);
        $this->assertTrue($session_data['flash-message']['form'] === 'Some message to form!');
        clear_flash_message($request);

        $message = get_flash_message($request);
        $this->assertTrue($message === null);
    }
}