<?php namespace InputValidator;

use BigElephant\LaravelRules\Rule;

class Input extends Rule {

	protected $factory;

	protected $hidden = false;

	protected $updatable = true;

	protected $failMessage;

	public function __construct(Factory $factory)
	{
		$this->factory = $factory;
	}

	public function confirmed($rule)
	{
		$this->factory->add($rule.'_confirmation');

		return parent::confirmed($rule);
	}

	public function hasRule()
	{
		return ! empty($this->rules);
	}

	public function hidden()
	{
		$this->hidden = true;
	}

	public function noUpdate()
	{
		$this->updatable = false;
	}

	public function noEdit()
	{
		$this->noUpdate();
	}

	public function isHidden()
	{
		return $this->hidden;
	}

	public function canUpdate()
	{
		return $this->updatable;
	}

	public function fail($message)
	{
		$this->failMessage = $message;
	}

	public function getFailMessage()
	{
		return $this->failMessage;
	}
}