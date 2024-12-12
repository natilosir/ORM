<?php

require 'ORM.php';

echo 'Select all data with chaining methods<br>';
$users = DB::Table('users')
    ->where('name', 'second')
    ->where('email', 'third')
    ->orderBy('id', 'max') // max = DESC, min = ASC
    ->limit(3)
    ->get();

foreach ($users as $user) {
    echo $user->id.' - '.$user->name.' - '.$user->email.'<br>';
}

echo 'Count users with where condition<br>';
$searchResults = DB::Table('users')
    ->search(['name' => 'Jane', 'email' => 'example.com'])
    ->orderBy('id', 'max') // max = DESC, min = ASC
    ->where('age', '>', 25)
    ->limit(3)
    ->get();

echo 'Total number of users named John: '.$userCount.'<br>';

echo 'Search with multiple conditions<br>';
$searchResults = DB::Table('users')
    ->search(['name' => 'Jane', 'email' => 'example.com'])
    ->orderBy('id', 'ASC') // max = DESC, min = ASC
    ->limit(3)
    ->get();

foreach ($searchResults as $result) {
    echo $result->id.' - '.$result->name.' - '.$result->email.'<br>';
}

echo 'Use the orderBy clause to sort users by email in ascending order with a limit<br>';
$orderedResults = DB::Table('users')
    ->where('name', 'John')
    ->where('name', 'jane')
    ->orderBy('id', 'max') // max = DESC, min = ASC
    ->limit(3)
    ->get();

foreach ($orderedResults as $result) {
    echo $result->id.' - '.$result->name.' - '.$result->email.'<br>';
}

echo 'Insert new data with array<br>';
$newUser = [
    'user'  => 'Jane.Doe',
    'name'  => 'Jane Doe',
    'email' => 'jane.doe@example.com'];
DB::Table('users')
    ->insert($newUser);

echo 'Insert new data with model instance eloquent<br>';
$data        = DB::Table('users');
$data->user  = 'first';
$data->name  = 'second';
$data->email = 'third';
$data->save();

echo 'Update data with array<br>';
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

echo 'Update data with model instance eloquent<br>';
$data        = DB::Table('users');
$data->user  = 'first';
$data->name  = 'second';
$data->email = 'third';
$data->save(); // 1 is id and where is with array $data->save('name' => 'Jane.Doe');

echo 'Delete data<br>';
DB::Table('users')
    ->delete(115); // 1 is id
//AND
DB::Table('users')
    ->delete(['name' => 1, 'user' => 6]);
//AND
DB::Table('users')
    ->where(['name' => 1, 'user' => 5])
    ->delete();

echo 'Using DISTINCT in SQL<br>';
$users = DB::Table('users')
    ->select('email')
    ->distinct()
    ->get();

foreach ($users as $user) {
    echo $user->email.'<br>';
}

echo 'Run a custom SQL query<br>';
$customQueryResults = DB::Table('users')
    ->query("SELECT * FROM users WHERE email LIKE '%example.com%' LIMIT 5");
foreach ($customQueryResults as $result) {
    echo $result->id.' - '.$result->name.' - '.$result->email.'<br>';
}
