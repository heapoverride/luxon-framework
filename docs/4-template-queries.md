# Template queries
You can use this method `Database::template(string $template, mixed[] ...$data)` to build safe SQL query strings.

```php
// example template
$pageOffset = 0;
$pageSize = 50;

$query = Database::template("SELECT * FROM requests ORDER BY Id DESC LIMIT &", [$pageOffset, $pageSize]);

// Value of $query would then be
// SELECT * FROM requests ORDER BY Id DESC LIMIT 0, 50
// which can be executed safely
$result = Database::query($query);
```

### Symbols and their behavior
| Symbol | Data (mixed)               | Result (string)           | Example                 |
|-:      |-                           |-                          |-                        |
| &      | `'a'`                      | `` `a` ``                 |                         |
|        | `['a', 'b']`               | `` `a`, `b` ``            | SELECT & ...            |
|        | `[1, 2]`                   | `` 1, 2 ``                |                         |
| @      | `a`                        | `` `a` ``                 |                         |
|        | `['a', 'b']`               | `` `a`.`b` ``             | ... JOIN ON @ = @       |
|        | `[1, 2]`                   | `` 1.2 ``                 |                         |
| $      | `'a'`                      | `` `a` ``                 |                         |
|        | `['a', 'b']`               | `` (`a`, `b`) ``          | INSERT INTO table $ ... |
|        | `[1, 2]`                   | `` (1, 2) ``              |                         |
| ?      | `'a'`                      | `'a'`                     | ... WHERE \`id\` = ?      |
|        | `['a', 'b']`               | `('a', 'b')`              | ... VALUES ?            |
|        | `[['a', 'b'], ['a', 'b']]` | `('a', 'b'), ('a', 'b')`  | ... VALUES ?            |
|        | `[1, 2]`                   | `` (1, 2) ``              | ... VALUES ?            |

### Template examples
Coming soon...