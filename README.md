# Fixed Width Annotation
FixedWidth annotation for describing, reading, and writing fixed width text files.

This library uses the Doctrine Annotation library to enable defining fixed width file schemas using a `@FixedWidth` annotation on PHP classes.

## Installation
```sh
$ composer require tcshields/fixed-width-annotation
```

## Basic Usage
Define your fixed width file schema using a PHP class. Annotations defined on the class are used as defaults for all properties. Annotations defined on the properties will override any values set on the class. The `length` annotation on the class will also be used to validate the length of the entire record when writing to a file.

Available Annotations:
* `start` the starting position of the field
* `length` the length of the field
* `type` the data type of the value on the object
* `padCharacter` the character to use to pad the value when writing to a file
* `padPosition` the position of the padding in relation to the value (left or right)
* `format` a formatting string for the field value to use when writing to a file

### Example of an annotated class

```php
<?php
use FixedWidth\Annotation\FixedWidth;

/**
 * @FixedWidth(length=47, type=FixedWidth::TYPE_STRING, padCharacter=" ", padPosition=STR_PAD_LEFT)
 */
class MyFileRecord
{
    /**
     * @FixedWidth(start=1, length=1)
     */
    public $type = 'D';

    /**
     * @FixedWidth(start=2, length=10, type=FixedWidth::TYPE_INTEGER, padCharacter="0")
     */
    public $id;

    /**
     * @FixedWidth(start=12, length=20)
     */
    public $name;

    /**
     * @FixedWidth(start=32, length=8, type=FixedWidth::TYPE_DATE, format="mdY")
     */
    public $date;

    /**
     * @FixedWidth(start=40, length=8, type=FixedWidth::TYPE_DECIMAL, padCharacter="0", format="%.2f")
     */
    public $moneyValue;
}
```

### Example of writing a file
Create an instance of an annotated object and pass it to the addRecord method of the Writer.

```php
<?php
$record = new MyFileRecord();
$record->type = 'D'; // not necessary since set in class
$record->id = 263;
$record->name = 'John Doe';
$record->date = new DateTime();
$record->moneyValue = 25.38;

$writer = new FixedWidth\Writer();
$writer->open($filename);
$writer->addRecord($record);
$writer->close();
```

The above will write a file with the following content.
```sh
D0000000263            John Doe0327202000025.38
```

### Example of reading a file
A file with the above content is read using the Reader.
```php
<?php
$reader = new FixedWidth\Reader();
$reader->open($filename);
// use getRecordIdentifier() with a switch statement to determine
// the correct object type to pass to getRecord()
if ('D' === $reader->getRecordIdentifier(1, 1)) {
    $record = $reader->getRecord(new MyFileRecord());
    // $record is now a hydrated MyFileRecord object
    var_dump($record);
}
```
will output the following:
```sh
object(App\Processor\Galileo\Batch\MyFileRecord)#659 (5) {
  ["type"]=>
  string(1) "D"
  ["id"]=>
  int(263)
  ["name"]=>
  string(8) "John Doe"
  ["date"]=>
  object(DateTime)#665 (3) {
    ["date"]=>
    string(26) "2020-03-27 19:30:37.000000"
    ["timezone_type"]=>
    int(3)
    ["timezone"]=>
    string(3) "UTC"
  }
  ["moneyValue"]=>
  float(25.38)
}
```
### Annotation Caching
The Parser uses file based caching to prevent needing to parse the file annotations repeatedly. This improves performance, especially on larger files/data sets.

The cache file path defaults to `/tmp` but can be changed before using the Reader or Writer.
```php
<?php
FixedWidth\Annotation\Parser::$cacheDir = '/path/to/cache';
```

## License
This library is released under the MIT License. See the bundled LICENSE file for details.