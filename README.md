# ORM
ORM PHP

## required

- PHP >= 5.4
- Composer

## install

```bash
git clone https://github.com/natilosir/ORM/
```

## example

Select all data with chaining methods
```bash
$users = DB::Table('users')
    ->where('name', 'Jane Doe')
    ->orderBy('id', 'DESC')
    ->limit(3)
    ->get();
    
```

<br>

Count users with where condition
```bash
$userCount = DB::Table('users')
    ->count();
```

<br>

Search with multiple conditions
```bash
$searchResults = DB::Table('users')
    ->search(['name' => 'Jane', 'email' => 'example.com'])
    ->orderBy('id', 'deSC')
    ->limit(3)
    ->get();

foreach ($searchResults as $result) {
    echo $result['id'].' - '.$result['name'].' - '.$result['email'].'<br>';
}
```

<br>

Use the orderBy clause to sort users by email in ascending order with a limit
```bash
$orderedResults = DB::Table('users')
    ->where('name', 'John')
    ->andwhere('name', 'jane')
    ->orderBy('id', 'deSC')
    ->limit(3)
    ->get();

foreach ($orderedResults as $result) {
    echo $result['id'].' - '.$result['name'].' - '.$result['email'].'<br>';
}
```

<br>

Insert new data with array
```bash
$newUser = [
    'name'  => 'Jane Doe',
    'email' => 'jane.doe@example.com'];
DB::Table('users')
    ->insert($newUser);
```

<br>

Insert new data with model instance eloquent
```bash
$data        = DB::Table('users');
$data->user  = 'first';
$data->name  = 'second';
$data->email = 'third';
$data->save();
```

<br>

Update data with array
```bash
$updateData = [
    'name'  => 'John Smith',
    'email' => 'john.smith@example.com'];
DB::Table('users')
    ->update(1, $updateData);
```

<br>

Update data with single value
```bash
$updateDataSingle = 'Jane Smith';
DB::Table('users')
    ->update(2, ['name' => $updateDataSingle]);
```

<br>

Delete data
```bash
DB::Table('users')
    ->delete(1);
```

<br>

Run a custom SQL query
```bash
$customQueryResults = DB::Table('users')
    ->query("SELECT * FROM users WHERE email LIKE '%example.com%' LIMIT 5");
foreach ($customQueryResults as $result) {
    echo $result['id'].' - '.$result['name'].' - '.$result['email'].'<br>';
}
```
