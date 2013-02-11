Laravel Input Validator
===============

This package is designed to abstract out input validation for your controllers and reduce repeated code.

[![Build Status](https://secure.travis-ci.org/bigelephant/input-validator.png)](http://travis-ci.org/bigelephant/input-validator)

### Installation

Add the following to the "require" section of your `composer.json` file:

```json
	"bigelephant/input-validator": "dev-master"
```

Edit the `app/config/app.php` file and...

* Add the following to your `providers` array:

```php
	'BigElephant\InputValidator\ValidatorServiceProvider',
```

* Add the following to your `aliases` array:
```php
	'InputValidator' => 'BigElephant\InputValidator\ValidatorFacade',
```

## [Click here to skip down to the recommended usage](https://github.com/bigelephant/input-validator#example-validator-class-the-recommended-way)

## Examples

### Native Controller
```php
class SignupController extends BaseController {

	// This will be left out of some examples
	public function getIndex()
	{
		return View::make('signup');
	}

	public function postIndex()
	{
		$input = [
			'email' => 				Input::get('email'),
			'password' => 			Input::get('password'),
			'password_confirmation' => Input::get('password_confirmation'),

			'first_name' => 		Input::get('first_name'),
			'last_name' => 			Input::get('last_name'),

			'country' => 			Input::get('country'),
			'city' => 				Input::get('city'),
			'post_code' => 			Input::get('post_code'),

			'terms' => 				Input::get('terms')
		];

		// Note this might be done by people with 2 other options, Input::all() or Input::only(['everything', 'here'])

		$rules = [
			'email' => 		'required|email',
			'password' => 	'required|min:5|confirmed',

			'first_name' => 'required',
			'last_name' => 	'required',

			'country' => 	'in:'.implode(':', external_country_list()),
			'post_code' => 	'numeric',

			'terms' => 	'accepted',
		];

		$messages = [
			'email' => 'Please enter valid email bro.';
			'password' => 'No password? You crazy!',
		];

		$validator = Validator::make($input, $rules, $messages);
		if ($validator->fails())
		{
			return Redirect::back()->withErrors($validator)->onlyInput(array_keys($input));
		}

		$user = new User($input);
		$user->save();

		return Redirect::to('something/pretty');
	}
}
```

### Example closure to get input and rules
This first example is an option to group up input and rules. It isn't recommended but is an option, later examples are the designed way to use this package.

```php
class SignupController extends BaseController {

	public function postIndex()
	{
		list ($input, $rules, $messages) = InputValidator::make(function($input)
		{
			$input->add('email')->required()->email()->fails('Please enter valid email bro.');
			// This hidden() here means if we fail this input won't be flashed to the session
			$input->add('password')->required()->min(5)->confirmed()->hidden()->fails('No password? You crazy!');

			$input->add('first_name')->required();
			$input->add('last_name')->required();

			$input->add('country')->in(external_country_list());
			$input->add('city');
			$input->add('postal_code')->numeric();

			$input->add('terms')->accepted();
		});

		$validator = Validator::make($input, $rules, $messages);
		if ($validator->fails())
		{
			return Redirect::back()->withErrors($validator)->onlyInput(array_keys($input));
		}

		$user = new User($input);
		$user->save();

		return Redirect::to('something/pretty');
	}
}
```

### Example validator in closure
This is a closure method similar to Route closures...

#### First our closure
```php
InputValidator::add('signup', function($input)
{
	$input->add('email')->required()->email()->fails('Please enter valid email bro.');
	$input->add('password')->required()->min(5)->confirmed()->hidden()->fails('No password? You crazy!');

	$input->add('first_name')->required();
	$input->add('last_name')->required();

	$input->add('country')->in(external_country_list());
	$input->add('city');
	$input->add('postal_code')->numeric();

	$input->add('terms')->accepted();
});
```

#### Then our controller again with the updated information
```php
class SignupController extends BaseController {

	public function postIndex()
	{
		$validator = InputValidator::make('signup');
		if ($validator->fails())
		{
			return Redirect::back();

			// Old, why? Because the validator will automatically handle flushing the errors and input into the session for you
			//return Redirect::back()->withErrors($validator)->onlyInput(array_keys($input));
		}

		$user = new User($validator->getInput());
		$user->save();

		return Redirect::to('something/pretty');
	}
}
```

### Example validator class, the recommended way
This is the way this package was designed to be used.

#### The validator class
```php
class SignupValidator extends BigElephant\InputValidator\Validator {

	protected function defineInput()
	{
		$this->add('email')->required()->email()->fails('Please enter valid email bro.');
		$this->add('password')->required()->min(5)->confirmed()->hidden()->fails('No password? You crazy!');

		$this->add('first_name')->required();
		$this->add('last_name')->required();

		$this->add('country')->in(external_country_list());
		$this->add('city');
		$this->add('postal_code')->numeric();

		$this->add('terms')->accepted();
	}
}
```

Then we can either make the validator with `InputValidator::make('SignupValidator')` or it could be added like the example above with `InputValidator::add('signup', 'SignupValidator')`, we will assume the latter in our example.

#### Controller again, same as the previous one
```php
class SignupController extends BaseController {

	public function postIndex()
	{
		$validator = InputValidator::make('signup');
		if ($validator->fails())
		{
			return Redirect::back();
		}

		$user = new User($validator->getInput());
		$user->save();

		return Redirect::to('something/pretty');
	}
}
```

### Example validator with fields only created once
For example, a username can be created when they sign up and not edited.

```php
class UserValidator extends BigElephant\InputValidator\Validator {
	
	protected function defineInputs()
	{
		$this->add('username')->required()->alphaDash()->noEdit();
		$this->add('password')->required()->min(5)->hidden();

		$this->add('email')->required()->email();
	}
}
```

With the above if the HTTP method is `PUT` or `PATCH` then when the validator is run the `username` input will be skipped completely as we are updating the user (if using your methods properly).
Alternatively you can call `UserValidator::setUpdating` to true or false to skip or include values set to `noEdit`. For example you would set it to false when an admin is editing a user.

### Example usage with filters
I added a little feature to shorten the code even more, completely bypassing any validation in a controller. 
If you use `InputValidation::add(...)` you can define the third parameter as a response, like the following:
```php
InputValidation::add('signup', 'SignupValidator', Redirect::back());
```
or you can fill out the `filterFailResponse` method of the specified validator for the same result. For example:
```php
class UserValidator extends BigElephant\InputValidator\Validator {
	
	protected function defineInputs()
	{
		$this->add('username')->required()->alphaDash()->noEdit();
		$this->add('password')->required()->min(5)->hidden();

		$this->add('email')->required()->email();
	}

	public function filterFailResponse()
	{
		return Redirect::back();
	}
}
Note: with this you will still have the old input and any errors flushed into the session. 

By doing this a filter is automatically created called `validator.{name}`, so in this case `validator.signup`. 
Now our controller is even smaller, with the addition of getting our data a different way:
```php
class SignupController extends BaseController {

	public function __construct()
	{
		$this->beforeFilter('validate.signup', ['only' => 'postIndex']);
	}

	public function postIndex()
	{
		$user = new User(InputValidator::input('signup'));
		$user->save();

		return Redirect::to('success/something/pretty');
	}
}
```
Here you will see the use of `InputValidator::input(...)`. This is used when we use filters, the usual `$input` array you get will instead be stored in the factory and you can access with `InputValidator::input('validator_name')`.