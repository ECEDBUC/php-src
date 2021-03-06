--TEST--
PDO PgSQL Bug #69752 (memory leak with closeCursor)
--SKIPIF--
<?php
if (!extension_loaded('pdo') || !extension_loaded('pdo_pgsql')) die('skip not loaded');
require dirname(__FILE__) . '/config.inc';
require dirname(__FILE__) . '/../../../ext/pdo/tests/pdo_test.inc';
PDOTest::skip();
?>
--FILE--
<?php
require dirname(__FILE__) . '/../../../ext/pdo/tests/pdo_test.inc';
$pdo = PDOTest::test_factory(dirname(__FILE__) . '/common.phpt');

$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$pdo->beginTransaction();

$pdo->exec("
    create table foo (
        id bigserial not null primary key,
        field1 text not null,
        field2 text not null,
        field3 text not null,
        field4 int4 not null
    )
");
$stmt = $pdo->prepare("insert into foo (field1, field2, field3, field4) values (:field1, :field2, :field3, :field4)");
$max = 1000;
$first_time_usage = null;

for($i = 0; $i < $max; $i++) {
    $data = array(
        'field1' => "field1: $i",
        'field2' => "field2: $i",
        'field3' => "field3: $i",
        'field4' => $i
    );
    $stmt->execute($data);
    $stmt->closeCursor();
    $usage = intval(memory_get_usage() / 1024);

    if ($first_time_usage === null) $first_time_usage = $usage;

    if ($first_time_usage != $usage){
        printf("Memory Leak Detected: %d != %d\n", $usage, $first_time_usage);
        break;
    }
}
$pdo->rollBack();
echo "done\n"
?>
--EXPECTF--
done
