<?php namespace BigElephant\InputValidator;

use Illuminate\Validation\Factory;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Closure;

class Factory {

	protected $validatorFactory;

	protected $request;

	protected $router;

	protected $validators = array();

	protected static $filterInputs = array();

	public function __construct(Request $request, Factory $validatorFactory, Router $router)
	{
		$this->validatorFactory = $validatorFactory;
		$this->request = $request;
		$this->router = $router;
	}

	public function make($validator)
	{
		if ($validator instanceof Closure)
		{
			$validator = new ClosureValidator($validator, $this->request, $this->validatorFactory);

			return array($validator->getInput(), $validator->getRules(), $validator->getMessages());
		}

		if (is_string($validator))
		{
			$this->add($validator, $validator);
		}

		if (isset($this->validators[$validator]))
		{
			if ($this->validators[$validator] instanceof Closure)
			{
				return new ClosureValidator($this->validators[$validator], $this->request, $this->validatorFactory);
			}

			return new $this->validators[$validator]($this->request, $this->validatorFactory);
		}

		throw new \InvalidArgumentException('Invalid input validator.');
	}

	protected function addFilter($name, $response)
	{
		if ($response === null)
		{
			return;
		}

		$me = $this;
		$this->router->filer('validator.'.$name, function() use ($me, $name, $response) 
		{
			$validator = $me->make($name);

			if ($validator->fails())
			{
				return $response;
			}

			$me->addFilterInput($name, $validator->getInput());
		});
	}

	public function addFilterInput($name, array $input)
	{
		static::$filterInputs[$name] = $input;
	}

	public static function input($name)
	{
		if ( ! isset(static::$filterInputs[$name]))
		{
			return null;
		}

		return static::$filterInputs[$name];
	}

	public function add($name, $validator, $filterFailResponse = null)
	{
		$this->validators[$name] = $validator;

		$this->addFilter($name, $filterFailResponse);
	}
}