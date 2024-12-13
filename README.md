# ORM
eloquent ORM in PHP

## required

- PHP >= 5.4
- Composer

## install

```bash
composer require natilosir/orm
```
```bash
git clone https://github.com/natilosir/ORM/
```
<br><br>

## Example

- **Select** all data with chaining methods
```bash
$users = DB::Table('users')
    ->where('name', 'second')
    ->where('email', 'third')
    ->orderBy('id', 'max') // max = DESC, min = ASC
    ->limit(3)
    ->get();
    
```

- **Search** with multiple conditions
```bash
$searchResults = DB::Table('users')
    ->search(['name' => 'Jane', 'email' => 'example.com'])
    ->orderBy('id', 'ASC') // max = DESC, min = ASC
    ->where('age', '>', 25)
    ->limit(3)
    ->get();

foreach ($searchResults as $result) {
    echo $result['id'].' - '.$result['name'].' - '.$result['email'].'<br>';
}
```

- **Count** users with where condition
```bash
$userCount = DB::Table('users')
 // ->where('age', 25)  or search
    ->where('age', '>', 25)
    ->where('age', '>', 25, 'OR')
    ->where('age', '=', 25, 'and')
    ->count();
```

- Use the **orderBy** clause to sort users by email in ascending order with a limit
```bash
$orderedResults = DB::Table('users')
    ->where('name', 'John')
    ->where('name', 'jane')
    ->orderBy('id', 'deSC') // max = DESC, min = ASC
    ->limit(3)
    ->get();

foreach ($orderedResults as $result) {
    echo $result['id'].' - '.$result['name'].' - '.$result['email'].'<br>';
}
```

- **Insert** new data with **array**
```bash
$newUser = [
    'user'  => 'Jane.Doe',
    'name'  => 'Jane Doe',
    'email' => 'jane.doe@example.com'];
DB::Table('users')
    ->insert($newUser);
```

- **Insert** new data with model instance **eloquent**
```bash
$data        = DB::Table('users');
$data->user  = 'first';
$data->name  = 'second';
$data->email = 'third';
$data->save();
```

- **Update** data with **array**
```bash
$updateData = [
    'user'  => 'Jane.Doe',
    'name'  => 'John Smith',
    'email' => 'john.smith@example.com'];
DB::Table('users')
    ->update(1, $updateData); // 1 is id and where is with array
//AND
DB::Table('users')
    ->update(['name' => 1, 'user' => 3], $updateData); // update({where}, {UpdateArray})
//AND
DB::Table('users')
//  ->where(['name' => 1, 'user' => 2])
    ->where('name', 1) //AND oder methods in where
    ->update($updateData);
```

- **Update** data with model instance **eloquent**
```bash
$data        = DB::Table('users');
$data->user  = 'first';
$data->name  = 'second';
$data->email = 'third';
$data->save(1); // 1 is id and where is with array $data->save('name' => 'Jane Doe'); 
```

- **Delete** data
```bash
DB::Table('users')
    ->delete(1); // 1 is id
//AND
DB::Table('users')
    ->delete(['name' => 1, 'user' => 6]);
//AND
DB::Table('users')
    ->where(['name' => 1, 'user' => 5]) //AND oder methods in where
    ->delete();
```

- **Using DISTINCT in SQL**
```bash
$users = DB::Table('users')
    ->select('email')
    ->distinct()
    ->get();

foreach ($users as $user) {
    echo $user['email'].'<br>';
}
```

- **JSON**
```bash
$users = DB::Table('users')
    ->limit(3)
    ->json()
    ->get();

echo $users;
// [{"id":116,"name":"John Smith", ...
```

- **Run a custom SQL query**
```bash
$customQueryResults = DB::Table('users')
    ->query("SELECT * FROM users WHERE email LIKE '%example.com%' LIMIT 5");

foreach ($customQueryResults as $result) {
    echo $result['id'].' - '.$result['name'].' - '.$result['email'].'<br>';
}
```
