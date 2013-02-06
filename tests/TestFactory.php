<?php

use Mockery as m;
use BigElephant\InputValidator\Factory;
use BigElephant\InputValidator\Input;
use BigElephant\InputValidator\Validator;
use Illuminate\Routing\Router;

class TestFactory extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->request = m::mock('Illuminate\Http\Request');
		$this->request->shouldReceive('getMethod');
		$this->request->shouldReceive('only')->with(array('email', 'password'))->andReturn(array(
			'email' => 'test.email@gmail.com',
			'password' => 'secret'
		));

		$this->factory = new Factory(
			$this->request,
			m::mock('Illuminate\Validation\Factory'),
			m::mock('Illuminate\Routing\Router')
		);
	}

	public function tearDown()
	{
		m::close();
	}

	public function testMakeClosure()
	{
		list ($input, $rules, $messages) = $this->factory->make(function($input)
		{
			$input->add('email')->required()->email()->fails('email fail');
			$input->add('password')->required()->min(5)->hidden();
		});

		$this->assertSame($input, array(
			'email' => 'test.email@gmail.com',
			'password' => 'secret'
		));

		$this->assertSame((string) $rules['email'], 'required|email');
		$this->assertSame((string) $rules['password'], 'required|min:5');

		$this->assertSame($messages, array('email' => 'email fail'));
	}

	public function testMakeRawString()
	{
		$validator = $this->factory->make('ValidatorStub');

		$this->assertTrue($validator instanceof ValidatorStub);
	}

	public function testAddAndMake()
	{
		$this->factory->add('login', 'ValidatorStub');
		$validator = $this->factory->make('login');

		$this->assertTrue($validator instanceof ValidatorStub);
	}

	public function testAddFilter()
	{
		$router = m::mock('Illuminate\Routing\Router');
		$router->shouldReceive('addFilter')->with('validator.login', m::type('Closure'));

		$factory = new Factory(
			$this->request,
			m::mock('Illuminate\Validation\Factory'),
			$router
		);

		$factory->add('login', 'ValidatorStub', 'some response');
	}

	public function testFilterGetInput()
	{
		$mockFactory = m::mock('Illuminate\Validation\Factory');
		$mockFactory->shouldReceive('make')->andReturn(new MockLaravelValidator);

		$factory = new Factory(
			$this->request,
			$mockFactory,
			$router = new Router
		);

		$factory->add('login', 'ValidatorStub', 'some response');

		call_user_func($router->getFilter('validator.login'));

		$this->assertSame($factory->input('login'), array(
			'email' => 'test.email@gmail.com',
			'password' => 'secret'
		));
	}
}

class MockLaravelValidator {

	public function fails()
	{
		return false;
	}
}

class ValidatorStub extends Validator {

	protected function defineInput()
	{
		$this->add('email')->required()->email()->fails('email fail');
		$this->add('password')->required()->min(5)->hidden();
	}
}