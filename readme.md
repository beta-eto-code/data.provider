# Провайдер данных

Данная библиотека предоставляет единый инетрфейс для работы различными источниками данных: pdo, json, xml, csv...

В библиотеке реализованы 2 основные сущности:

* Провайдер (DataProviderInterface) - представляет из себя интерфейс для работы с инточником данных поддерживает: запрос, фильтрацию, сортировку, добавление, обновление и удаление данных.
* Мигратор (DataMigratorInterface) - позволяет обмениваться данными между различными источниками данных.

На текущий момент реализованы след. провайдеры данных:

* PdoDataProvider - позволяет работать с СУБД через интерфейс PDO, для работы неоходимо пробросить конструктор SQL запросов, на текущий момент реализован конструктор с поддержкой MySql диалекта.
* JsonDataProvider - прозволяет пработать с данными json файлов.
* XmlDataProvider - позволяет работать с данными xml файлов.
* CsvDataProvider - позволяет работать с данными csv файлов.
* ClosureDataProvider - произодный провайдер, предназначен для запроса произвольных данных, 
но без поддержки операций сохранения, обновления и удаления, в качестве аргумента принимает анонимную функцию которая должна возвращать ассоциативный массив с данными.
Подходит для генерации тестовых данных например через библиотеку fzaninotto/faker.


## Пример работы с провайдером данных:

```php
use PDO;
use Data\Provider\Providers\PdoDataProvider;
use Data\Provider\Providers\JsonDataProvider;
use Data\Provider\Providers\XmlDataProvider;
use Data\Provider\Providers\CsvDataProvider;
use Data\Provider\Providers\ClosureDataProvider;
use Data\Provider\SqlBuilderMySql;
use Data\Provider\QueryCriteria;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\Interfaces\CompareRuleInterface;
use Faker\Factory;

$pdo = new PDO('mysql:host=localhost;dbname=mydb;charset=UTF8', 'username', 'password');
$pdoProvider = new PdoDataProvider(
    $pdo,                       // указываем объект для подключения через интерфейс PDO
    'users',                    // указываем таблицу с которой будем работать
    new SqlBuilderMysql(),      // указываем конструктор SQL запросов
    'id'                        // указываем первичный плюч
);

$jsonProvider = new JsonDataProvider(
    $_SERVER['DOCUMENT_ROOT'].'/users.json', // указываем путь к json файлу
    'id'                                     // указываем первичный ключ
);

$xmlProvider = new XmlDataProvider(
    $_SERVER['DOCUMENT_ROOT'].'/users.xml', // указываем путь к xml файлу 
    'list',                                 // имя xml узла от которого будут читаться данные
    'item',                                 // имя xml узла элемента
    'id',                                   // указываем первичный ключ
    'otheritem'                             // имя xml узла для перечисляемых данных (массивы)
);

$csvProvider = new CsvDataProvider(
    $_SERVER['DOCUMENT_ROOT'].'/users.csv', // указываем путь к csv файлу 
    'id',                                   // указываем первичный ключ
    ';',
    '"',
    '\\'
);

$faker = Factory::create('ru_RU');
$fakerProvider = new ClosureDataProvider(
    10,                                     // указываем ограничение по количеству генерируемых данных по-умолчанию (можно изменить через объект QueryCriteriaInterface указав свой лимит)
    function (QueryCriteriaInterface $query) use ($fakerFactory) {
        return [
            'id' => $fakerFactory->randomNumber(),
            'name' => $fakerFactory->firstName,
            'email' => $fakerFactory->safeEmail
        ];
    }
);

$query = new QueryCriteria();
$query->addCriteria('id', CompareRuleInterface::IN, [1,3,5]);
$query->setLimit(3);
$query->setOrderBy('id', false);

$pdoData = $pdoProvider->getData($query);       // данные из таблицы users соотвествующие критериям выборки
$jsonData = $jsonProvider->getData($query);     // данные из файла users.json соотвествующие критериям выборки
$xmlData = $xmlProvider->getData($query);       // данные из файла users.xml соотвествующие критериям выборки
$csvData = $csvProvider->getData($query);       // данные из файла users.csv соотвествующие критериям выборки
$fakerData = $fakerProvider->getData($query);   // сгенерированные данные (в этом примере через бибилотеку fzaninotto/faker)

$resultAdd = $pdoProvider->save([                                           // пример добавления данных
    'name' => 'test',
    'email' => 'user@example.com'
]);

$updateQueryCriteria = new QueryCriteria();
$updateQueryCriteria->addCriteria('id', CompareRuleInterface::MORE, 10);    // будем обновлять только записи со значением столбца id > 10
$updateQueryCriteria->setLimit(10);                                         // обновим не более 10 записей соотвествующих условию выше

$updateResult = $jsonProvider->save([                                       // пример обновления данных
    'name' => 'hidden',
], $query);


$deleteQuery = new QueryCriteria();
$deleteQuery->addCriteria('name', CompareRuleInterface::LIKE, 'test');      // будем удалять всех пользователей в имени которых есть слово test
$deleteQuery->setLimit(100);                                                // удалим не более 100 пользователей

$csvProvider->remove($query);                                               // пример удаления данных
```

## Пример обмена данными между различными источниками

```php

use PDO;
use Data\Provider\Providers\PdoDataProvider;
use Data\Provider\Providers\ClosureDataProvider;
use Data\Provider\DefaultDataMigrator;
use Data\Provider\QueryCriteria;
use Data\Provider\Interfaces\CompareRuleInterface;

$pdo = new PDO('mysql:host=localhost;dbname=mydb;charset=UTF8', 'username', 'password');
$pdoProvider = new PdoDataProvider(
    $pdo,                       // указываем объект для подключения через интерфейс PDO
    'users',                    // указываем таблицу с которой будем работать
    new SqlBuilderMysql(),      // указываем конструктор SQL запросов
    'id'                        // указываем первичный плюч
);

$faker = Factory::create('ru_RU');
$fakerProvider = new ClosureDataProvider(
    10,                                     // указываем ограничение по количеству генерируемых данных по-умолчанию (можно изменить через объект QueryCriteriaInterface указав свой лимит)
    function (QueryCriteriaInterface $query) use ($fakerFactory) {
        return [
            'id' => $fakerFactory->randomNumber(),
            'name' => $fakerFactory->firstName,
            'email' => $fakerFactory->safeEmail
        ];
    }
);

$migrator = new DefaultDataMigrator(
    $fakerProvider,                                     // источник данных
    $pdoProvider                                        // приемник данных
);

$query = new QueryCriteria();
$query->setLimit(20);                                   // увеличиваем количество генерируемых данных с 10 до 20
$migrateInsertResult = $migrator->runInsert($query);    // добавление сгенерированных данных в таблицу users
$migrateInsertResult->getErrors();                      // есть ли ошибки в процессе добавления данных в таблицу
$migrateInsertResult->getErrors();                      // список ошибок
$migrateInsertResult->getSourceData();                  // сгененрированные для записи данные
$migrateInsertResult->getUnimportedDataList();          // список данных которые не удалось добавить в таблицу

$migrator->runUpdate($query, function ($dataForMigrate) {   // обновление данных в таблице соотвествующие критериям укзанным во втором аргументе сгенерированными данными
    $query = new QueryCriteria();
    $query->addCriteria('id', CompareRuleInterface::EQUAL, $dataForMigrate['id']);
    
    return $query;
}, true);                                                   // указываем что данные не найденые по критериям будут добавлены в таблицу как новые записи


$migrator->runUpdate($query, 'id');                         // обновление данных в таблице аналогично примеру выше, в данном случае во втором аргументе передается имя ключа источника, данные которого будут сравниваться с первичным ключом приемника
```