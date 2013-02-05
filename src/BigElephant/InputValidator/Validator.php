<?php namespace BigElephant\InputValidator;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationFactory as ValidationFactory;
use Illuminate\Support\Contracts\MessageProviderInterface;

abstract class Validator implements MessageProviderInterface {

	protected $request;

	protected $validation;

	protected $inputs;

	protected $updating;

	protected $lastValidator;

	public function __construct(Request $request, ValidationFactory $validation)
	{
		$this->request = $request;
		$this->validation = $validation;

		$this->setUpdating(in_array(strtolower($request->getMethod()), array('put', 'patch')));

		$this->defineInput();
	}

	abstract protected function defineInput();

	public function add($input)
	{
		$this->input[$input] = new Input($this);

		return $this->input[$input];
	}

	public function setUpdating($updating)
	{
		$this->updating = $updating;
	}

	public function check()
	{
		$this->lastValidator = $this->validation->make($this->getInput(), $this->getRules(), $this->getFailMessages());

		if ($this->lastValidator->fails())
		{
			$this->request->getSessionStore()->flash('errors', $this->lastValidator->getMessageBag());
			$this->request->flashOnly($this->getInput(false));

			return false;
		}

		return true;
	}

	public function fails()
	{
		return ! $this->check();
	}

	public function getInput($withHidden = true)
	{
		return $this->request->only(array_keys($this->selectInput($withHidden)));
	}

	public function getFailedMessages()
	{
		$messages = array();
		foreach ($this->selectInput() AS $k => $input)
		{
			if ($input->hasRule() AND $input->hasFailMessage())
			{
				$messages[$k] = $input->getFailMessage();
			}
		}

		return $messages;
	}

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

	public function getValidator()
	{
		return $this->lastValidator;
	}

	protected function selectInput($withHidden = true)
	{
		$inputs = $this->inputs;
		if ($this->updating)
		{
			foreach ($inputs AS $k => $input)
			{
				if ( ! $input->isUpdatable() OR (! $withHidden AND $input->isHidden()))
				{
					unset($inputs[$k]);
				}
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
		return $this->lastValidator()->messages;
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