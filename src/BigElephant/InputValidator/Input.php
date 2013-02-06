<?php namespace BigElephant\InputValidator;

use BigElephant\LaravelRules\Rule;

class Input extends Rule {

	protected $validator;

	protected $hidden = false;

	protected $updatable = true;

	protected $failMessage;

	public function __construct(Validator $validator)
	{
		$this->validator = $validator;
	}

	public function ruleConfirmed()
	{
		$confirmed = $this->validator->add($this->getValue().'_confirmation')->hidden();

		return parent::ruleConfirmed();
	}

	public function getValue()
	{
		return $this->validator->get($this);
	}

	public function hasRules()
	{
		return ! empty($this->rules);
	}

	public function hidden()
	{
		$this->hidden = true;

		return $this;
	}

	public function noUpdate()
	{
		$this->updatable = false;

		return $this;
	}

	public function noEdit()
	{
		return $this->noUpdate();
	}

	public function isHidden()
	{
		return $this->hidden;
	}

	public function canUpdate()
	{
		return $this->updatable;
	}

	public function fails($message)
	{
		$this->failMessage = $message;
	}

	public function hasFailMessage()
	{
		return ! is_null($this->failMessage);
	}

	public function getFailMessage()
	{
		return $this->failMessage;
	}
}