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
| Symbol | Data (mixed)               | Result (string)           |
|-:      |-                           |-                          |
| &      | `'a'`                      | `` `a` ``                 |
|        | `['a', 'b']`               | `` `a`, `b` ``            |
|        | `[1, 2]`                   | `` 1, 2 ``                |
| @      | `a`                        | `` `a` ``                 |
|        | `['a', 'b']`               | `` `a`.`b` ``             |
|        | `[1, 2]`                   | `` 1.2 ``                 |
| $      | `'a'`                      | `` `a` ``                 |
|        | `['a', 'b']`               | `` (`a`, `b`) ``          |
|        | `[1, 2]`                   | `` (1, 2) ``              |
| ?      | `'a'`                      | `'a'`                     |
|        | `['a', 'b']`               | `('a', 'b')`              |
|        | `[['a', 'b'], ['a', 'b']]` | `('a', 'b'), ('a', 'b')`  |
|        | `[1, 2]`                   | `` (1, 2) ``              |

### Template examples
Coming soon...