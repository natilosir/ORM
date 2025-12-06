<?php

require '../vendor/autoload.php';

use app\Models\Personal;
use natilosir\orm\Database;

function assertFail( $msg ) {
    echo "[FAIL] $msg\n";
}

function assertOk( $msg ) {
    echo "[OK] $msg\n";
}

function run( $name, $fn ) {
    echo "\n=== TEST: $name ===\n";
    try {
        $fn();
    } catch ( Throwable $e ) {
        assertFail("$name | Exception: " . $e->getMessage());
    }
}

Database::pdo()->exec("TRUNCATE personal_access_tokens");

/* --------------------------------------------------------------------
   BASIC INSERT / SAVE
--------------------------------------------------------------------- */
run("insert & save()", function () {
    $m                 = new Personal();
    $m->tokenable_type = "App\\User";
    $m->tokenable_id   = 1;
    $m->name           = "t1";
    $m->token          = "AAA111";
    $m->abilities      = json_encode([ "read" ]);

    $obj = $m->save();

    if ( !$obj->id ) {
        return assertFail("id not set");
    }
    assertOk("insert OK");
});

/* --------------------------------------------------------------------
   FIND / FIRST / FINDORFAIL
--------------------------------------------------------------------- */
run("find / first / findOrFail", function () {
    $f  = Personal::first();
    $id = $f->id;

    if ( !Personal::find($id) ) {
        return assertFail("find failed");
    }

    try {
        Personal::findOrFail($id + 9999);
        return assertFail("findOrFail didn't throw");
    } catch ( Exception $e ) {
        // Expected
    }

    assertOk("find & first OK");
});

/* --------------------------------------------------------------------
   WHERE BASIC / LIKE / RAW / GROUP
--------------------------------------------------------------------- */
run("where / orWhere / whereLike / whereRaw / whereGroup", function () {
    $rows = Personal::where("name", "t1")->get();
    if ( !count($rows) ) return assertFail("basic where failed");

    $rows = Personal::orWhere("name", "t1")->get();
    if ( !count($rows) ) return assertFail("orWhere failed");

    $rows = Personal::whereLike("name", "t")->get();
    if ( !count($rows) ) return assertFail("whereLike failed");

    $rows = Personal::whereRaw("tokenable_id = ?", [ 1 ])->get();
    if ( !count($rows) ) return assertFail("whereRaw failed");

    $rows = Personal::query()->whereGroup(function ( $q ) {
        $q->where("tokenable_id", 1);
        $q->orWhere("tokenable_id", 2);
    })->get();

    if ( !count($rows) ) return assertFail("whereGroup failed");

    assertOk("all where variants OK");
});

/* --------------------------------------------------------------------
   WHERE IN / NOT IN / BETWEEN / NOT BETWEEN
--------------------------------------------------------------------- */
run("whereIn / whereNotIn / between", function () {
    $in1 = Personal::whereIn("id", [ 1 ])->get();
    if ( !count($in1) ) return assertFail("whereIn failed");

    $nin = Personal::whereNotIn("id", [ 999 ])->get();
    if ( !count($nin) ) return assertFail("whereNotIn failed");

    $bt = Personal::whereBetween("id", [ 1, 999 ])->get();
    if ( !count($bt) ) return assertFail("whereBetween failed");

    $nbt = Personal::whereNotBetween("id", [ 9999, 19999 ])->get();
    if ( !count($nbt) ) return assertFail("whereNotBetween failed");

    assertOk("IN / NOT IN / BETWEEN OK");
});

/* --------------------------------------------------------------------
   WHERE DATE / MONTH / YEAR / DAY
--------------------------------------------------------------------- */
run("date filters", function () {
    $today = date('Y-m-d');
    $rows  = Personal::whereDate("created_at", "=", $today)->get();

    if ( !is_array($rows) ) return assertFail("whereDate failed");

    Personal::whereMonth("created_at", "=", date('m'))->get();
    Personal::whereYear("created_at", "=", date('Y'))->get();
    Personal::whereDay("created_at", "=", date('d'))->get();

    assertOk("date/month/year/day OK");
});

/* --------------------------------------------------------------------
   ORDER / DISTINCT / LIMIT
--------------------------------------------------------------------- */
run("orderBy / distinct / limit", function () {
    $r = Personal::orderBy("id", "DESC")->limit(1)->get();
    if ( count($r) !== 1 ) return assertFail("limit failed");

    Personal::distinct()->get(); // verify no crash

    assertOk("order/distinct/limit OK");
});

/* --------------------------------------------------------------------
   SELECTRAW / SQL()
--------------------------------------------------------------------- */
run("selectRaw + SQL()", function () {
    Personal::selectRaw("1 as x")->get();

    $sql = Personal::SQL();
    if ( !str_contains($sql, "SELECT") ) return assertFail("SQL() wrong");

    assertOk("selectRaw + SQL OK");
});

/* --------------------------------------------------------------------
   INSERT / UPDATE
--------------------------------------------------------------------- */
run("update()", function () {
    $m   = Personal::first();
    $old = $m->token;

    Personal::where("id", $m->id)->update([ "token" => "CHG" ]);
    $fresh = Personal::find($m->id);

    if ( $fresh->token === $old ) return assertFail("update didn't apply");

    assertOk("update OK");
});

/* --------------------------------------------------------------------
   DELETE
--------------------------------------------------------------------- */
run("delete()", function () {
    $m                 = new Personal();
    $m->tokenable_type = "App\\User";
    $m->tokenable_id   = 99;
    $m->name           = "del";
    $m->token          = "DEL";

    $m->save();
    $id = $m->id;

    $m->delete();

    if ( Personal::find($id) ) return assertFail("delete() failed");

    assertOk("delete OK");
});

/* --------------------------------------------------------------------
   firstOrNew / firstOrCreate / createOrFirst / updateOrCreate
--------------------------------------------------------------------- */
run("firstOrNew / firstOrCreate / createOrFirst / updateOrCreate", function () {
    $a = Personal::firstOrNew([ "token" => "X1" ], [ "name" => "xx" ]);
    if ( isset($a->id) ) return assertFail("firstOrNew returned existing");

    $b = Personal::firstOrCreate([ "token" => "X1" ], [ "name" => "xx" ]);
    if ( isset($b->id) ) print_r($b);

    $c = Personal::createOrFirst([ "token" => "X1" ], [ "name" => "yy" ]);
    if ( $c->name !== "yy" ) return assertFail("createOrFirst must NOT update existing");

    $d = Personal::updateOrCreate([ "token" => "X1" ], [ "name" => "zz" ]);
    if ( $d->name !== "zz" ) return assertFail("updateOrCreate update failed");

    assertOk("all creators OK");
});

/* --------------------------------------------------------------------
   createOrUpdate
--------------------------------------------------------------------- */
run("createOrUpdate", function () {
    $m = Personal::createOrUpdate([ "token" => "CUP" ], [ "name" => "one" ]);
    $m = Personal::createOrUpdate([ "token" => "CUP" ], [ "name" => "two" ]);

    if ( $m->name !== "two" ) return assertFail("createOrUpdate failed");

    assertOk("createOrUpdate OK");
});

/* --------------------------------------------------------------------
   updateOrInsert
--------------------------------------------------------------------- */
run("updateOrInsert", function () {
    Personal::updateOrInsert([ "token" => "UPI" ], [ "name" => "one" ]);
    Personal::updateOrInsert([ "token" => "UPI" ], [ "name" => "two" ]);

    $r = Personal::where("token", "UPI")->first();
    if ( $r->name !== "two" ) return assertFail("updateOrInsert failed");

    assertOk("updateOrInsert OK");
});

/* --------------------------------------------------------------------
   increment / decrement + binding collision
--------------------------------------------------------------------- */
run("increment / decrement", function () {
    $m                 = new Personal();
    $m->tokenable_type = "App\\C";
    $m->tokenable_id   = 501;
    $m->name           = "counter-test";
    $m->token          = "CCC111";

    $m->save();
    $id = $m->id;

    Personal::where("id", $id)->increment("tokenable_id", 5);
    Personal::where("id", $id)->decrement("tokenable_id", 2);

    $fresh = Personal::find($id);
    if ( $fresh->tokenable_id != 504 ) return assertFail("increment/decrement wrong");

    assertOk("increment/decrement OK");
});

/* --------------------------------------------------------------------
   whereNotNull / orWhereNotNull
--------------------------------------------------------------------- */
run("whereNotNull", function () {
    Personal::whereNotNull("id")->get();
    Personal::orWhereNotNull("id")->get();

    assertOk("whereNotNull OK");
});

/* --------------------------------------------------------------------
   whereIn empty array → empty result
--------------------------------------------------------------------- */
run("whereIn empty", function () {
    $r = Personal::whereIn("id", [])->get();
    if ( !is_array($r) ) return assertFail("must return empty array");

    assertOk("whereIn([]) OK");
});

/* --------------------------------------------------------------------
   search / searchMulti
--------------------------------------------------------------------- */
run("search / searchMulti", function () {
    $a = Personal::search("name", "t")->get();
    $b = Personal::searchMulti([ "name", "token" ], "A")->get();

    assertOk("search OK");
});

/* --------------------------------------------------------------------
   RELATIONS: hasMany / belongsTo / nested with()
--------------------------------------------------------------------- */
run("relations (no crash)", function () {
    $r = Personal::with("owner")->get();
    // Should not crash even if relation doesn't exist

    assertOk("relations safe fallback OK");
});

/* --------------------------------------------------------------------
   SAVE UPDATE ROUNDTRIP + FRESH
--------------------------------------------------------------------- */
run("save update + fresh", function () {
    $m   = Personal::first();
    $old = $m->token;

    $m->token = "FRESH1";
    $f        = $m->save();

    if ( $f->token !== "FRESH1" ) return assertFail("save update failed");

    assertOk("save update/fresh OK");
});

/* --------------------------------------------------------------------
   DONE
--------------------------------------------------------------------- */
echo "\n\n======== ALL TEST GROUPS COMPLETED ======== \n";
