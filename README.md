# Eloquent ORM in PHP

This is a simple and elegant implementation of an ORM (Object-Relational Mapping) for PHP, designed to work with relational databases using fluent query building and Eloquent-style syntax.

## Requirements

- PHP >= 5.4
- Composer

## Installation

You can install this ORM package via Composer:

```bash
composer require natilosir/ORM
```
Alternatively, you can clone the repository directly:
```bash
git clone https://github.com/natilosir/ORM
```
<br><br>

## Example
When using the DB class, make sure to include it with `use` .
```php
use natilosir\orm\db; 
```
### Select
- **Select** all data with chaining methods
```php
$users = DB::Table('users')
    ->where('name', 'second')
    ->where('email', 'third')
    ->orderBy('id', 'max') // max = DESC, min = ASC
//  ->orderBy('max') // default value is id
    ->limit(3)
    ->get();
    
```
### Search
- **Search** with multiple conditions
```php
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
### Count
- **Count** users with where condition
```php
$userCount = DB::Table('users')
 // ->where('age', 25)  or search
    ->where('age', '>', 25)
    ->where('age', '>', 25, 'OR')
    ->where('age', '=', 25, 'and')
    ->count();
```
### orderBy
- Use the **orderBy** clause to sort users by email in ascending order with a limit
```php
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
### value
- Use the **value** function to add multiple columns dynamically with automatic numbering for duplicates.
```php
$Price = DB::Table('extra')
    ->value('price','date')->get();
```
output
```sql
SELECT price, id FROM extra //...
```
### Insert array
- **Insert** : Inserting new data with an **array**
```php
$newUser = [
    'user'  => 'Jane.Doe',
    'name'  => 'Jane Doe',
    'email' => 'jane.doe@example.com'];
DB::Table('users')
    ->insert($newUser);
```
### Insert model
- **Insert** new data with model instance
```php
$data        = DB::Table('users');
$data->user  = 'first';
$data->name  = 'second';
$data->email = 'third';
$data->save();
```
### Update array
- **Update** data with an **array**
```php
$updateData = [
    'user'  => 'Jane.Doe',
    'name'  => 'John Smith',
    'email' => 'john.smith@example.com'];
DB::Table('users')
    ->update(1, $updateData); // 1 is the ID and update({where}, {UpdateArray})

// Alternatively, update with multiple conditions:
DB::Table('users')
    ->update(['name' => 1, 'user' => 3], $updateData); // update({whereArray}, {UpdateArray})

//AND
DB::Table('users')
//  ->where(['name' => 1, 'user' => 2])
    ->where('name', 1) //AND oder methods in where
    ->update($updateData);
```
### Update model
- **Update**: Updating data with a model instance
```php
$data        = DB::Table('users');
$data->user  = 'first';
$data->name  = 'second';
$data->email = 'third';
$data->save(1);  // 1 is the ID for the record to update $data->save('name' => 'Jane Doe'); 
```
### Delete
- **Delete** data
```php
DB::Table('users')
    ->delete(1); // 1 is the ID of the record to delete

// Alternatively, delete using conditions:
DB::Table('users')
    ->delete(['name' => 1, 'user' => 6]);

//AND
DB::Table('users')
    ->where(['name' => 1, 'user' => 5]) //AND oder methods in where
    ->delete();
```
### DISTINCT
- **Using DISTINCT in SQL**
```php
$users = DB::Table('users')
    ->select('email')
    ->distinct()
    ->get();

foreach ($users as $user) {
    echo $user['email'].'<br>';
}
```
### JSON
- **JSON**
```php
$users = DB::Table('users')
    ->limit(3)
    ->json()
    ->get();

echo $users;
// [{"id":116,"name":"John Smith", ...
```
### sql
- **show sql**
```php
$price = DB::Table('extra')->WHERE('id', '>', '1')->value('price','date')
    ->sql();
```
output
```sql
SELECT price, id FROM extra WHERE id > :id
```
### query
- **Run a custom SQL query**
```php
$customQueryResults = DB::query("SELECT * FROM users WHERE email LIKE '%example.com%' LIMIT 5");

foreach ($customQueryResults as $result) {
    echo $result['id'].' - '.$result['name'].' - '.$result['email'].'<br>';
}
```

For more details, consult the documentation or check out the full repository.

This README provides an overview of how to use the ORM package for various database operations in PHP, from selecting and updating data to inserting and deleting records, all using a clean, expressive syntax.
