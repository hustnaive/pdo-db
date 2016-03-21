# pdo-db

pdo-db基于PDO API进行二次封装，主要封装了一下常见的CRUD操作，避免自己写sql。关于PDO库的使用，请移步：<http://php.net/manual/zh/book.pdo.php>

同时，这个封装参考了yii的db的api接口方式，熟悉yii的db-api的人可以较快的入手。

# usage

`composer require --dev "hustnaive/pdo-db"`

## instance

```
$db = new \fangl\db\Connection($dsn, $username, $password);
```

目前只测试了mysql:开头的dsn，其他的没有经过测试，大家如果测试有bug，可以告诉我。


## query

```
$db->createCommand($sql,$params=[])->queryXXX();

$db->createCommand('select * from dual where id = :ID',[':ID'=>1])->queryOne();

$db->createCommand('select * from dual')->queryAll();

$db->createCommand('select id from dual')->queryColumn();

```

## execute

```
$db->createCommand($sql,$params=[])->execute();
```

## crud

```
$db->createCommand()->insert($table,$columns)->execute();

$db->createCommand()->update($table,$columns,$condition,$params=[])->execute();

$db->createCommand()->delete($table,$condtion,$params)->execute();

$db->createCommand()->truncateTable($table)->execute();

$db->createCommand()->batchInsert($table,$data,$fields);
```

## transaction

```
$db->beginTransaction();

//do some thing


if($isOk) {
	$db->commit();
}
else {
	$db->rollback();
}