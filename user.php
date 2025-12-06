<?php

namespace app\Models;

use natilosir\orm\Models;

class User extends Models {
    public string $table      = 'bot_users';
    public bool   $timestamps = true;
}