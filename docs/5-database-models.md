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
$todos = $todoModel->getMany("id", 1);
```
```php
$todos = $todoModel->getMany("todo", "like", "%GitHub%");
```
```php
$todos = $todoModel->getMany(
    ["todo", "like", "%GitHub%"], "AND",
    ["time", ">", time() - 3600]
);
```