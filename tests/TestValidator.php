<?php

use Mockery as m;
use BigElephant\InputValidator\Validator;

class TestValidator extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->request = m::mock('Illuminate\Http\Request');
		$this->request->shouldReceive('only')->with(array())->andReturn(array());

		$this->validation = m::mock('Illuminate\Validation\Factory');
	}

	public function tearDown()
	{
		m::close();
	}

	public function testUpdatingSetFromMethod()
	{
		$this->request->shouldReceive('getMethod')->once()->andReturn('put');
		$this->request->shouldReceive('getMethod')->once()->andReturn('get');

		$validator = new TestValidatorStub($this->request, $this->validation);
		$this->assertTrue($validator->isUpdating());

		$validator = new TestValidatorStub($this->request, $this->validation);
		$this->assertFalse($validator->isUpdating());
	}

	public function testCheckPasses()
	{
		$this->request->shouldReceive('getMethod')->once()->andReturn('get');

		$mickyMocky = m::mock('Illuminate\Validation\Validator');
		$mickyMocky->shouldReceive('fails')->andReturn(false);
		$this->validation->shouldReceive('make')->once()->andReturn($mickyMocky);

		$validator = new TestValidatorStub($this->request, $this->validation);
		$this->assertTrue($validator->check());
	}

	public function testCheckFails()
	{
		$this->request->shouldReceive('getMethod')->once()->andReturn('get');
		$mickyMocky = m::mock('Illuminate\Validation\Validator');
		$mickyMocky->shouldReceive('getMessageBag')->andReturn(array());
		$mickyMocky->shouldReceive('fails')->andReturn(true);
		$this->validation->shouldReceive('make')->once()->andReturn($mickyMocky);

		$mockSession = m::mock('meh');
		$mockSession->shouldReceive('flash');
		$this->request->shouldReceive('getSessionStore')->andReturn($mockSession);
		$this->request->shouldReceive('flashOnly');

		$validator = new TestValidatorStub($this->request, $this->validation);
		$this->assertFalse($validator->check());
	}

	protected function getInput($type = false)
	{
		$input = array(
			'username' 		=> 'test-username', 
			'email' 		=> 'email@email.com', 
			'password' 		=> 'secret',
			'password_confirmation' => 'secret',
			'first_name'	=> 'Robert', 
			'last_name'		=> 'Clancy',
			'country'		=> 'aus', 
			'city' 			=> 'tent', 
			'postal_code' 	=> 235245,
			'terms' 		=> 1
		);

		switch ($type)
		{
			case 'hidden': unset($input['password'], $input['password_confirmation']); break;
			case 'updating': unset($input['username']); break;
		}

		return $input;
	}

	public function testGetAllInput()
	{
		$this->request->shouldReceive('getMethod')->once()->andReturn('get');
		$this->request->shouldReceive('only')->with(array_keys($this->getInput()))->andReturn($this->getInput());

		$validator = new TestPopulatedValidatorStub($this->request, $this->validation);
		$this->assertSame($validator->getInput(), $this->getInput());
	}

	public function testGetUpdateOnlyInput()
	{
		$this->request->shouldReceive('getMethod')->once()->andReturn('put');
		$this->request->shouldReceive('only')->with(array_keys($this->getInput('updating')))->andReturn($this->getInput('updating'));

		$validator = new TestPopulatedValidatorStub($this->request, $this->validation);
		$this->assertSame($validator->getInput(), $this->getInput('updating'));
	}

	public function testGetWithoutHiddenInput()
	{
		$this->request->shouldReceive('getMethod')->once()->andReturn('get');
		$this->request->shouldReceive('only')->with(array_keys($this->getInput('hidden')))->andReturn($this->getInput('hidden'));

		$validator = new TestPopulatedValidatorStub($this->request, $this->validation);
		$this->assertSame($validator->getInput(false), $this->getInput('hidden'));
	}

	public function testGetRulesAndMessages()
	{
		$this->request->shouldReceive('getMethod')->once()->andReturn('get');
		$this->request->shouldReceive('only')->with(array_keys($this->getInput()))->andReturn($this->getInput());

		$validator = new TestPopulatedValidatorStub($this->request, $this->validation);
		$rules = $validator->getRules();
		$this->assertSame((string) $rules['username'], 		'required|alphaDash');
		$this->assertSame((string) $rules['email'], 		'required|email');
		$this->assertSame((string) $rules['password'], 		'required|min:5|confirmed');
		$this->assertSame((string) $rules['first_name'], 	'required');
		$this->assertSame((string) $rules['last_name'], 	'required');
		$this->assertSame((string) $rules['country'], 		'in:aus,something');
		$this->assertSame((string) $rules['postal_code'], 	'numeric');
		$this->assertSame((string) $rules['terms'], 		'accepted');

		$this->assertSame($validator->getFailedMessages(), array(
			'email' => 'Please enter valid email bro.',
			'password' => 'No password? You crazy!'
		));
	}
}

class TestPopulatedValidatorStub extends Validator {

	protected function defineInput()
	{
		$this->add('username')->required()->alphaDash()->noEdit();
		$this->add('email')->required()->email()->fails('Please enter valid email bro.');
		$this->add('password')->required()->min(5)->confirmed()->hidden()->fails('No password? You crazy!');

		$this->add('first_name')->required();
		$this->add('last_name')->required();

		$this->add('country')->in('aus', 'something');
		$this->add('city');
		$this->add('postal_code')->numeric();

		$this->add('terms')->accepted();
	}
}

class TestValidatorStub extends Validator {

	protected function defineInput()
	{
	}
}