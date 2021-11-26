# Database models
Minimal database model for Luxon framework

### Code examples

Define model for `todos`
```php
$todoModel = Model::define("todos", [
    "id" => [
        "primary" => true,
        "type" => "int",
    ],
    "todo" => [
        "type" => "string"
    ],
    "time" => [
        "type" => "int"
    ]
]);
```

Save model by name so that it can be loaded at any time from anywhere
```php
$todoModel->saveModel("todo");
```

Load saved model by it's name from anywhere
```php
$todoModel = Model::loadModel("todo");
```

Get one todo by it's primary key
```php
$todo = $todoModel->getByPK(1);
```

Get todo's `todo` column's value
```php
$strTodo = $todo->get("todo");
```

Set todo's `time` to current unix timestamp
```php
$todo->set("time", time());
```

Save changes to this todo model to database
```php
$todo->save();
```

Create new todo (this creates a new row in `todos` table)
```php
$todo = $todoModel->create([
    "todo" => "Push updated Luxon framework's code to GitHub",
    "time" => time()
]);
```

Delete this todo from database
```php
$todo->delete();
```

Get all todos
```php
$todos = $todoModel->getMany();
```

Get all todos that match a specific criteria
```php
$todos = $todoModel->getMany([
    "where" => ["id", 1]
]);
```
```php
$todos = $todoModel->getMany([
    "order" => [
        "order" => "DESC",
        "columns" => ["time"]
    ],
    "limit" => [15]
]);
```
```php
$todos = $todoModel->getMany([
    "where" => ["todo", "like", "%GitHub%"],
]);
```
```php
$todos = $todoModel->getMany([
    "where" => [
        ["todo", "like", "%GitHub%"], "AND",
        ["time", ">", time() - 3600]
    ]
]);
```