<?php namespace BigElephant\InputValidator;

use BigElephant\LaravelRules\Rule;

class Input extends Rule {

	/**
	 * Instance of the validator that created this instance.
	 *
	 * @var Validator
	 */
	protected $validator;

	/**
	 * If this is flushed to the session.
	 *
	 * @var boolean
	 */
	protected $hidden = false;

	/**
	 * If this input can be updated or only set when creating.
	 *
	 * @var boolean
	 */
	protected $updatable = true;

	/**
	 * Error message to be used instead of default.
	 *
	 * @var string
	 */
	protected $failMessage;

	/**
	 * Create the input instance.
	 *
	 * @param Validator $validator
	 */
	public function __construct(Validator $validator)
	{
		$this->validator = $validator;
	}

	/**
	 * When this rule is set we setup the *_confirmation pair as well.
	 *
	 * @return Input
	 */
	public function ruleConfirmed()
	{
		$confirmed = $this->validator->add($this->getValue().'_confirmation')->hidden();

		return parent::ruleConfirmed();
	}

	/**
	 * Get the value of this input from the validator.
	 * FIXME: this is done poorly, should send the value through on construction or something.
	 *
	 * @return string
	 */
	public function getValue()
	{
		return $this->validator->get($this);
	}

	/**
	 * If this input has any rules set.
	 *
	 * @return boolean
	 */
	public function hasRules()
	{
		return ! empty($this->rules);
	}

	/**
	 * This input is hidden from flushing to the session.
	 *
	 * @return Input
	 */
	public function hidden()
	{
		$this->hidden = true;

		return $this;
	}

	/**
	 * This input is on create only. 
	 *
	 * @return Input
	 */
	public function noUpdate()
	{
		$this->updatable = false;

		return $this;
	}

	/**
	 * An alternative to noUpdate().
	 *
	 * @return Input
	 */
	public function noEdit()
	{
		return $this->noUpdate();
	}

	/**
	 * If this input is hidden from session flushing.
	 *
	 * @return boolean
	 */
	public function isHidden()
	{
		return $this->hidden;
	}

	/**
	 * If this input can be updated.
	 *
	 * @return boolean
	 */
	public function canUpdate()
	{
		return $this->updatable;
	}

	/**
	 * Set the fail message for this input.
	 *
	 * @param string $message
	 */
	public function fails($message)
	{
		$this->failMessage = $message;
	}

	/**
	 * If this input has it's own fail message.
	 *
	 * @return boolean
	 */
	public function hasFailMessage()
	{
		return ! is_null($this->failMessage);
	}

	/**
	 * Get the fail message.
	 *
	 * @return string
	 */
	public function getFailMessage()
	{
		return $this->failMessage;
	}
}