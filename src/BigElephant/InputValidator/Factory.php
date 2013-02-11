<?php namespace BigElephant\InputValidator;

use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Closure;

class Factory {

	/**
	 * Instance of the laravel validation factory.
	 *
	 * @var Illuminate\Validation\Factory
	 */
	protected $validatorFactory;

	/**
	 * The HTTP request instance.
	 *
	 * @var Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * The router instance.
	 *
	 * @var Illuminate\Validation\Factory
	 */
	protected $router;

	/**
	 * List of validators that have been added.
	 *
	 * @var array
	 */
	protected $validators = array();

	/**
	 * Input that has been saved after a filter has been run.
	 *
	 * @var array
	 */
	protected $filterInputs = array();

	/**
	 * Create the factory.
	 *
	 * @param Illuminate\Http\Request 		$request
	 * @param Illuminate\Validation\Factory $validationFactory
	 * @param Illuminate\Routing\Router 	$router
	 */
	public function __construct(Request $request, ValidationFactory $validatorFactory, Router $router)
	{
		$this->validatorFactory = $validatorFactory;
		$this->request = $request;
		$this->router = $router;
	}

	/**
	 * Make an instance of a validator either by name, string of the class or a closure
	 *
	 * @param  mixed $validator
	 * @return mixed
	 */
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

	/**
	 * Name a validator for later use, also creates a filter if applicable.
	 *
	 * @param string $name
	 * @param string $validator
	 * @param mixed  $response 
	 */
	public function add($name, $validator, $filterFailResponse = null)
	{
		$this->validators[$name] = $validator;

		$this->addFilter($name, $filterFailResponse);
	}

	/**
	 * Add a filter for an alternative way to validate your application.
	 *
	 * @param string $name
	 * @param mixed  $response
	 */
	protected function addFilter($name, $response = null)
	{
		$validator = $this->make($name);

		if ( ! $response)
		{
			$response = $validator->filterFailResponse();
		}

		if ($response === null)
		{
			return;
		}

		$me = $this;
		$this->router->addFilter('validator.'.$name, function() use ($validator, $me, $name, $response) 
		{
			if ($validator->fails())
			{
				if ($response instanceof Closure)
				{
					return call_user_func($response);
				}

				return $response;
			}

			$me->addFilterInput($name, $validator->getInput());
		});
	}

	/**
	 * Add input from a filter so you can easily use it later.
	 *
	 * @param string $name
	 * @param array  $input
	 */
	public function addFilterInput($name, array $input)
	{
		$this->filterInputs[$name] = $input;
	}

	/**
	 * Get input that was earlier stored from a called filter.
	 *
	 * @param  string $name
	 * @return array
	 */
	public function input($name)
	{
		if ( ! isset($this->filterInputs[$name]))
		{
			return null;
		}

		return $this->filterInputs[$name];
	}
}