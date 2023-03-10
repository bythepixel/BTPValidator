# Bytepath Shared Libraries

This package contains a bunch of libraries we use on every project. These libraries are framework agnostic in that you typically must provide an implementation for your framework of choice

## Validator

Validator is a framework agnostic library for performing validation on client data. The class provides both an interface that defines how the validator works, and an abstract implementation of that interface that you can use to implement validation in your application.

### ValidatorInterface

ValidatorInterface has two methods

```php
    public function rules($rules): self;

    public function validate($data, ?Closure $callback): ValidationResult;
```

**rules()**

The first method, rules, accepts a list of $rules and returns a new instance of whatever validator you are using. Rules are not currentlyy formally defined. It's up to you to decide what rules are. In the future I plan to add some sort of rule definition class that makes your rules a bit more portable.

I use this with laravel, so 'rules' looks like this for me.

```php
$rules = [
  "name" => "required|string|max:100",
  "age" => "required|numeric",
];
```

this method returns a new class, leaving the original intact, so you can call this method at any time without having to worry about state etc.

**validate()**

The second method, validate, runs your rules against the provided $data. If all rules pass and the data is considered validated, the closure provided in the second argument will be ran. This closure will only run if validation passes, so you can do "dangerous" actions such as creating rows in the database etc, here.

The closure will be passed a variable of validated $data. Any fields in the data array that do not have a corresponding rule will be filtered out so you don't need to explicitly check for this.

```php
$rules = [
  "name" => "required|string|max:100",
  "age" => "required|numeric",
];

$userData = [
  "name" => "andrew",
  "age"  => 12,
  "zip" => "90210" // No rule for this 
];

return $validator->rules($rules)->validate($userData, function($validatedData) {
   // zip will be filtered out of the $validatedData array

   // Returns a theoretical user object containing the validated data
   return App\Models\User::create($validatedData);
});
```

If a value is returned from the closure, you can access this value with the getData() method of the ValidationResult returned by this method.

### Validator

Validator is an abstract class that does most of the work of implementing the ValidatorInterface for you. Though it's recommended you use this Validator as a base for your implementation, you are free to just implement the ValidationInterface directly if that fits your use case better.

This class has one abstract method

```php
    abstract protected function checkData($data, $rules): ValidationResult;
```

That performs the validation action in the method of your choice. You must implement this method yourself.

This method returns an object that extends the ValidationResult class described below.

### ValidationResult

The validate method of the ValidatorInterface returns an object that extends the abstract ValidationResult class. Assuming you are using the premade Validator class in this library, there are two possible objects that can be returned.

**PassedValidation**

As the name suggests, this object will be returned if the provided data passed validation. This object will contain any data that you returned from the closure passed to the validate() method described above in the ValidatorInterface section. To access this data you can run the getData() method of the PassedValidation class

**FailedValidation**

As the name suggests, this object will be returned if the provided data fails validation. This object will contain a key/val list of rules that did not pass as  well as a human readable string that you can provide to the user in your form. You can access this list of errors with the getErrors() method

### Changing The Behaviour Of PassedValidation/FailedValidation In Your Apps

You can return whatever you want from your implementation of Validator as long as the returned object extends ValidationResult. To make this process a bit simpler, the Validator class has two protected methods that you can override to change the class that will be returned in the event of pass/fail

```php
    protected function passed($data = []): PassedValidation
    protected function failed($errors = []): FailedValidation
```

If you extend either of these methods in your implementation you can change the value that gets returned. Values returned must extend Passed/FailedValidation respectively.

An example of where you might want to do this is in Laravel, you could implement the Responsible interface to automatically transform these objects in to valid Laravel Response objects. Now you can just return the result directly from your controller method and it will magically transform into a valid http response with proper headers etc.

### Transforming Data Returned By Validator

The ValidationResult object has a method called transform that you can use to mutate the data returned by the ValidationResult object. This class accepts a callback function and returns "self" meaning you can chain this function call directly in your implementation.

As an example lets modify the previous example where we validated data and used it to create a user. Imagine instead we want to return a JSON string instead of an object. We could do so using the transform function like this

```php
// Pulled from the validate() example from earlier in this doc
$result = $validator->rules($rules)->validate($data, function($validatedData) {
   return App\Models\User::create($validatedData);
});

// Transforms the "User" into JSON.
$result->transform(function($data) {
 return json_encode($data);
});

// Returns {"name": "andrew", "age": 12}
$shouldBeJSON = $result->getData();
```

### The transform() class on the FailedValidation object

The FailedValidation object does not have any data to transform, so if you call the transform() method on a FailedValidation object, the class just returns self without actually running the callback.

This was done intentionally so that we can return failed results while still retaining the ability to transform successful results.

```php
$failed = $validator->rules($rules)->validate($data, $fn);  // Validation failed here returning a FailedValidation object

$failed->passes(); // False

$failed->transform(function($data) {
   return json_encode($data);   // This function will NOT be ran on FailedValidation so it's safe to assume we have validated data here
});
```

### Example Implementation

You can use the concepts described above to make really verbose, easy to read code. Here is a sample implementation you can use

```php
class SomeService
{
    public function __construct(protected Validator $validator)
    {}

    /**
     * Creates a validator, then uses that validator to validate the data 
     * and create a new user
    */
    public function createUser($data): ValidationResult
    {
        return $this->getCreateValidator()->validate($data, function($validatedData) {
            return App\Models\User::create($validatedData);
        });
    }

    /**
     * Creates a validator by passing it a list of rules required to make a new user
    */
    protected function getCreateValidator(): Validator
    {
        $rules = [
            "name" => "required|string|max:100",
            "age" => "required|numeric",
        ];

        return $this->validator->rules($rules);  
    }
}
```

```php
class SomeController
{
    public function __construct(protected SomeService $service)
    {}

    public function createUserReturnJSON($data)
    {
      /**
        If validation succeeds,we will create a new user and convert it to JSON

        If validation fails, we return a FailedValidation object that contains
        a list of validation errors you can provide to the user. You could
        process these errors here, or catch them in a middleware of some
        sort
      */
      return $this->service->createUser($data)->transform(function($data){
          // This callback will only run if validation was successful
          return json_encode($data);
      });
    }
}
```
