<?php namespace BigElephant\InputValidator;

use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Closure;

class ClosureValidator extends Validator {

	/**
	 * Variable to hold the closure that was used.
	 * 
	 * @var Closure
	 */
	protected $closure;

	/**
	 * Store the closure and setup the parent.
	 *
	 * @param closure 				  		$closure
	 * @param Illuminate\Http\Request 		$request
	 * @param Illuminate\Validation\Factory $validation
	 * @param Illuminate\Routing\Router 	$router
	 */
	public function __construct(Closure $closure, Request $request, ValidationFactory $validation, Router $router)
	{
		$this->closure = $closure;

		parent::__construct($request, $validation, $router);
	}

	/**
	 * Our closure will define the input.
	 */
	protected function defineInput()
	{
		call_user_func_array($this->closure, array($this));
	}
}