<?php namespace BigElephant\InputValidator;

use Illuminate\Http\Request;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Support\Contracts\MessageProviderInterface;

abstract class Validator implements MessageProviderInterface {

	/**
	 * The HTTP request instance.
	 *
	 * @var Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * Instance of the validator that created this instance.
	 *
	 * @var Illuminate\Validation\Factory
	 */
	protected $validation;

	/**
	 * List of the inputs defined by this validator.
	 *
	 * @var array
	 */
	protected $inputs = array();

	/**
	 * If we are updating for this instance.
	 *
	 * @var boolean
	 */
	protected $updating;

	/**
	 * Instance of the laravel validator last used.
	 *
	 * @return Illuminate\Validation\Validator
	 */
	protected $lastValidator;

	/**
	 * Create the validator instance. 
	 *
	 * @param Illuminate\Http\Request 		$request
	 * @param Illuminate\Validation\Factory $validation
	 */
	public function __construct(Request $request, ValidationFactory $validation)
	{
		$this->request = $request;
		$this->validation = $validation;

		$this->setUpdating(in_array(strtolower($request->getMethod()), array('put', 'patch')));

		$this->defineInput();
	}

	/**
	 * This is run before check(), if it returns false then it validation will fail.
	 *
	 * @return mixed
	 */
	protected function preCheck()
	{
	}

	/**
	 * Response a filter will return when the check fails.
	 *
	 * @return mixed
	 */
	public function filterFailResponse()
	{
	}

	/**
	 * Define all the input for this validator.
	 */
	abstract protected function defineInput();

	/**
	 * Add a new input and return it to chain the rules.
	 *
	 * @param  string $input
	 * @return Input
	 */
	public function add($input)
	{
		$this->inputs[$input] = new Input($this);

		return $this->inputs[$input];
	}

	/**
	 * Set if the validator is update only.
	 *
	 * @param boolean $updating
	 */
	public function setUpdating($updating)
	{
		$this->updating = $updating;
	}

	/**
	 * Return if the validator is update only.
	 *
	 * @return boolean
	 */
	public function isUpdating()
	{
		return $this->updating;
	}

	/**
	 * Run the validation check, will call laravels validator.
	 *
	 * @return boolean
	 */
	public function check()
	{
		if ($this->preCheck() === false)
		{
			return false;
		}

		$this->lastValidator = $this->validation->make($this->getInput(), $this->getRules(), $this->getFailedMessages());

		if ($this->lastValidator->fails())
		{
			$this->request->getSessionStore()->flash('errors', $this->lastValidator->getMessageBag());
			$this->request->flashOnly(array_keys($this->selectInput(false)));

			return false;
		}

		return true;
	}

	/**
	 * Just runs check and returns opposite.
	 *
	 * @return boolean
	 */
	public function fails()
	{
		return ! $this->check();
	}

	/**
	 * Return the value of the input specified.
	 *
	 * @param  mixed $input
	 * @return string
	 */
	public function get($input)
	{
		$inputs = $this->selectInput();

		if ($input instanceof Input)
		{
			foreach ($inputs AS $name => $in)
			{
				if ($input === $in)
				{
					$input = $name;
					break;
				}
			}

			if ( ! is_string($input))
			{
				return null;
			}
		}

		return isset($inputs[$input]) ? $input : null;
	}

	/**
	 * Get input from the request object.
	 *
	 * @param  boolean $withHidden
	 * @return array
	 */
	public function getInput($withHidden = true)
	{
		return $this->request->only(array_keys($this->selectInput($withHidden)));
	}

	/**
	 * Get a list of fail messages to give to the laravel validator.
	 *
	 * @return array
	 */
	public function getFailedMessages()
	{
		$messages = array();
		foreach ($this->selectInput() AS $k => $input)
		{
			if ($input->hasRules() AND $input->hasFailMessage())
			{
				$messages[$k] = $input->getFailMessage();
			}
		}

		return $messages;
	}

	/**
	 * Get an array of rules.
	 *
	 * @return array
	 */
	public function getRules()
	{
		$rules = array();
		foreach ($this->selectInput() AS $k => $input)
		{
			if ($input->hasRules())
			{
				$rules[$k] = $input;
			}
		}

		return $rules;
	}

	/**
	 * Get the laravel validator.
	 *
	 * @return Illuminate\Validation\Validator
	 */
	public function getValidator()
	{
		return $this->lastValidator;
	}

	/**
	 * Get all the inputs for this instance.
	 *
	 * @param  boolean $withHidden
	 * @return array
	 */
	protected function selectInput($withHidden = true)
	{
		$inputs = $this->inputs;
		foreach ($inputs AS $k => $input)
		{
			if (($this->updating AND ! $input->canUpdate()) OR (! $withHidden AND $input->isHidden()))
			{
				unset($inputs[$k]);
			}
		}

		return $inputs;
	}

	/**
	 * Get the message container for the validator.
	 *
	 * @return Illuminate\Support\MessageBag
	 */
	public function messages()
	{
		return $this->lastValidator()->messages();
	}

	/**
	 * An alternative more semantic shortcut to the message container.
	 *
	 * @return Illuminate\Support\MessageBag
	 */
	public function errors()
	{
		return $this->messages();
	}

	/**
	 * Get the messages for the instance.
	 *
	 * @return ILluminate\Support\MessageBag
	 */
	public function getMessageBag()
	{
		return $this->messages();
	}
}