# BTPValidator
A client data validator for use in the yet to be named reusable BTP App Skeleton but should be usable in any PHP(Laravel) Environment.

BTPValidator is a class that implements ValidatorInterface

ValidatorInterface has two methods

```php
    public function rules($rules): self;

    public function validate($data, ?Closure $callback): ValidationResult;
```

**rules()**

The first method, rules, accepts a list of $rules and returns a new instance of whatever validator you are using. Since the BTPValidator class wraps around laravel's validation system, rules are just normal Laravel [Validation Rules](https://laravel.com/docs/10.x/validation#available-validation-rules). Anything that would be a valid rule in a laravel request is a valid rule here.  Example

```php
$rules = [
  "name" => "required|string|max:100",
  "age" => "required|numeric",
];
```

the rules() method returns a new instance of BTPValidator loaded with the provided rules, leaving the original intact. This means that you can call this method at any time without having to worry about state etc. The reason we do this is so that we can rely on the Laravel IOC container to setup validation for us so we don't do it manually. 

```php
$rules = [
  "name" => "required|string|max:100",
  "age" => "required|numeric",
];

$userValidator = $validator->rules($rules);

// Returns Bythepixel\Validator\BTPValidator
get_class($userValidator);
```

**validate($data, $callback)**

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
   // This callback will only run if validation passes.
   // 'zip' will be filtered out of the $validatedData array as there is no rule to handle it

   // Returns a user object containing the validated data
   return App\Models\User::create($validatedData);
});
```

If a value is returned from the closure, you can access this value with the getData() method of the ValidationResult returned by this method. Note that you typically don't need to do this as will see below.

## ValidationResult

The validate method of the BTPValidator class returns an object that extends the abstract ValidationResult class. There are two possible objects that can be returned by the BTPValidator class.

**PassedValidation**

As the name suggests, this object will be returned if the provided data passed validation. This object will contain any data that you returned from the closure passed to the validate() method. To access this data you can run the getData() method of the PassedValidation class. 

This class implements the laravel [Responsible interface](https://laravel-news.com/laravel-5-5-responsable) meaning if you return this object from a controller method, it will automatically convert it's contents into valid JSON output that can be consumed by the front end SPA. 

**FailedValidation**

As the name suggests, this object will be returned if the provided data fails validation. This object will contain the list of validation errors generated by the Laravel validation system so that you can provide them to the user. You can access this list of errors at any time with the getErrors() method

```php
$rules = ["name" => "required|string"];
$data = ["name" => 5];

$result =  $validator->rules($rules)->validate($data);

$result->passes();  // False

$errors = $result->getErrors();
/**
    [
        "name" => ["Must be a string"]
    ]
*/
```

This class implements the laravel [Responsible interface](https://laravel-news.com/laravel-5-5-responsable) meaning if you return this object from a controller method, it will automatically return a "422 Unprocessable" status code and also convert the list of errors into valid JSON output that can be consumed by the front end SPA. 

### Transforming Data Returned By Validator

The ValidationResult object has a method called transform that you can use to mutate the data returned by the ValidationResult object. This class accepts a callback function and returns "self" meaning you can chain this function call directly in your implementation.

We use this function to automatically convert domain data into [spatie/laravel-data](https://spatie.be/docs/laravel-data/v3/introduction) objects. These objects handle converting our data into well defined JSON objects that have typescript definitions for supercharged front end development. Here is an Example of how we might do this

```php
// Pulled from the validate() example from earlier in this doc
$result = $validator->rules($rules)->validate($data, function($validatedData) {
   return App\Models\User::create($validatedData);
});

// Transforms the "User" into JSON.
$result->transform(function($data) {
 return UserData::from($data);
});

// Returns a JSON Object with full typescript definitions on the front end
// Meaning Front devs know exactly what this response is going to look
// like before it even gets returned from the server.
// {"name": "andrew", "age": 12}
$shouldBeJSON = $result->getData();
```

### The transform() class on the FailedValidation object

The FailedValidation object does not have any data to transform, so if you call the transform() method on a FailedValidation object, the class just returns self without actually running the callback.

This was done intentionally so that we can return failed results while still retaining the ability to transform successful results.

```php
// Validation failed here returning a FailedValidation object
$failed = $validator->rules($rules)->validate($data, $fn); 

$failed->passes(); // False

$failed->transform(function($data) {
   // This function will NOT be ran on FailedValidation 
   // so it's safe to assume we have validated data here
   return json_encode($data);  
});
```

### Example Implementation

You can use the concepts described above to make really verbose, easy to read code. Here is a sample implementation you can use

```php
class SomeService
{
    public function __construct(protected BTPValidator $validator)
    {}

    /**
     * Creates a validator, then uses that validator to 
     * validate the data and create a new user
    */
    public function createUser($data): ValidationResult
    {
        return $this->getCreateValidator()->validate($data, function($validatedData) {
            // This callback will only run if validation is successful
            return App\Models\User::create($validatedData);
        });
    }

    /**
     * Creates a validator by calling the rules() method.
     * we pass this method a list of rules required to 
     * make a new user
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
        a list of validation errors. If Validation fails the transform method
        will be ignored. 
       
       The FailedValidation object will convert itself to a 422 unprocessable
       resoponse and provide a list of errors exactly how you would expect
       to happen when doing a normal laravel $request validation
      */
      return $this->service->createUser($data)->transform(function($user){
          // This callback will only run if validation was successful
          return UserData::from($user);
      });
    }
}
```
