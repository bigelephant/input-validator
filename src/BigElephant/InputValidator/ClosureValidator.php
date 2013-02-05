<?php namespace InputValidator;

use Closure

class ClosureValidator extends Validator {

	protected $closure;

	public function __construct(Closure $closure, Request $request, ValidationFactory $validation)
	{
		$this->closure = $closure;

		parent::__construct($request, $validation);
	}

	protected function defineInputs()
	{
		call_user_func($this->closure, array($this));
	}
}