<?php namespace BigElephant\InputValidator;

use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Closure;

class Factory {

	protected $validatorFactory;

	protected $request;

	protected $router;

	protected $validators = array();

	protected $filterInputs = array();

	public function __construct(Request $request, ValidationFactory $validatorFactory, Router $router)
	{
		$this->validatorFactory = $validatorFactory;
		$this->request = $request;
		$this->router = $router;
	}

	public function make($validator)
	{
		if ($validator instanceof Closure)
		{
			$validator = new ClosureValidator($validator, $this->request, $this->validatorFactory, $this->router);

			return array($validator->getInput(), $validator->getRules(), $validator->getFailedMessages());
		}

		if (is_string($validator) AND ! isset($this->validators[$validator]))
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
		$this->router->addFilter('validator.'.$name, function() use ($me, $name, $response) 
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
		$this->filterInputs[$name] = $input;
	}

	public function input($name)
	{
		if ( ! isset($this->filterInputs[$name]))
		{
			return null;
		}

		return $this->filterInputs[$name];
	}

	public function add($name, $validator, $filterFailResponse = null)
	{
		$this->validators[$name] = $validator;

		$this->addFilter($name, $filterFailResponse);
	}
}