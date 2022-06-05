# JSON API

JSON API helper can be used to decode, validate and respond to API requests.\
\
This module is not enabled by default but can be found from `disabled-modules` 
and enabled by moving it under `modules`.

### Examples

#### Successful response to an empty request
```php
// Decode request
$api = JSONAPI::accept();

// Set response
$api->response = [
    "greeting" => "Hello world!"
];

// Send response
// - Optional status code
$api->sendResponse();
```
Response
```JSON
{
    "success": true 
}
```

#### Successful response to request with a payload
```php
// Decode request
// - Expects request to have a body
// - Body should contain valid JSON data
// - Use provided template to validate incoming JSON data
$api = JSONAPI::accept([
    ":type" => "object",
    "greeting" => [
        ":type" => "string"
    ]
]);

// Set response
$api->response = [
    "greeting" => $api->request->greeting
];

// Send response
// - Optional status code
$api->sendResponse();
```
Request
```JSON
{
    "greeting": "Testing"
}
```
Response
```JSON
{
    "success": true,
    "response": {
        "greeting": "Testing"
    }
}
```

#### Default error response
```php
// 500 (Internal Server Error)
$api->sendError();
```
Response
```JSON
{
    "success": false
}
```

#### Error response with error description and an optional status code
```php
// 429 (Too Many Requests)
$api->sendError("You are being rate-limited", 429);

// Note that error description can be any JSON compatible type
```
Response
```JSON
{
    "success": false,
    "error": "You are being rate-limited"
}
```

#### Custom array type definition
```php
$api = JSONAPI::accept([
    ":type" => "array:mytype",
    "mytype" => [
        ":type" => "object",
        "id" => [
            ":type" => "number",
        ],
        "name" => [
            ":type" => "string",
            ":length" => [1, 230]
        ]
    ]
]);
```
Example accepted request
```json
[
    {
        "id": 1,
        "name": "Testing"
    },
    {
        "id": 2,
        "name": "More testing"
    },
]
```
Example invalid request
```json
[
    {
        "id": "1",
        "name": ""
    }
]
```