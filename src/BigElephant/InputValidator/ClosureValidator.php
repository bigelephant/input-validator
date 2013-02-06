<?php namespace BigElephant\InputValidator;

use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Closure;

class ClosureValidator extends Validator {

	protected $closure;

	public function __construct(Closure $closure, Request $request, ValidationFactory $validation, Router $router)
	{
		$this->closure = $closure;

		parent::__construct($request, $validation, $router);
	}

	protected function defineInput()
	{
		call_user_func_array($this->closure, array($this));
	}
}