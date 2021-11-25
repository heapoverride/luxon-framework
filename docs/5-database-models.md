# Database models
Minimal database model for Luxon framework

### Code examples
```php
/**
 * Define model for `todos`
 */
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

/**
 * Get one todo by it's primary key
 */
$todo = $todoModel->getByPK(1);

/**
 * Get todo's `todo` column's value
 */
$strTodo = $todo->get("todo");

/**
 * Set todo's `time` to current unix timestamp
 */
$todo->set("time", time());

/**
 * Save changes to this todo model to database
 */
$todo->save();

/**
 * Create new todo (this creates a new row in `todos` table)
 */
$todo = $todoModel->create([
    "todo" => "Push updated Luxon framework's code to GitHub",
    "time" => time()
]);

/**
 * Delete this todo from database
 */
$todo->delete();

/**
 * Get all todos
 */
$todos = $todoModel->getMany();

/**
 * Get all todos that match a specific criteria
 */
$todos = $todoModel->getMany("id", 1);

$todos = $todoModel->getMany("todo", "like", "%GitHub%");

$todos = $todoModel->getMany(
    ["todo", "like", "%GitHub%"], "AND",
    ["time", ">", time() - 3600]
);
```