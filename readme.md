Scheduler Helper for PHP
========================

### Requirements

- PHP>=5.4 (PDO~)
- MySQL, PostgreSQL, Sqlite, etc.

### Installation
 
- composer 'dhtmlx/scheduler-helper'
  
or
  
- just download the files from this repository 'git@github.com:DHTMLX/scheduler-helper-php.git'
  
```php
require_once "./SchedulerHelper.php";
use DHTMLX_Scheduler\Helper;
```

### How to use

In order to create a helper object you should call the class constructor `DHTMLX_Scheduler\Helper([$connectorDataArray]):`

```php
  $helper = new Helper(
    array(
      "dbsm" => "mysql", // optional, "mysql" by default
      "host" => "localhost", // optional, "localhost" by default
      "db_name" => "scheduler_helper_db",
      "user" => "root",
      "password" => "root",
      "table_name" => "events_rec" // name of the table that contains data of recurring events 
    )
  );
```

In the helper a standard set of fields for working with a table is defined:

```php
  helper::FLD_ID => "event_id",
  helper::FLD_START_DATE => "start_date",
  helper::FLD_END_DATE => "end_date",
  helper::FLD_TEXT => "text",
  helper::FLD_RECURRING_TYPE => "rec_type",
  helper::FLD_PARENT_ID => "event_pid",
  helper::FLD_LENGTH => "event_length"
```

To redefine the helper and create new fields, you should use the method 'setFieldsNames([$fieldsDataArray])': 

```php
  $helper->setFieldsNames(array(
    $helper::FLD_RECURRING_TYPE => "my_recurring_type_field", // redefining the field 'FLD_RECURRING_TYPE'.
    "my_property_field" // initialization of a new field
  ));
```

For getting only fields that have set, you need just to set 'true' like second parameter in the function 'setFieldsNames([$fieldsDataArray], true)'

```php
  $helper->setFieldsNames(array(
    $helper::FLD_RECURRING_TYPE => "my_recurring_type_field", // redefining the field 'FLD_RECURRING_TYPE'.
    "my_property_field" // initialization of a new field
  ), true);
```

For setting weather server time zone will be considered you should use server_date config(true by default). If true helper will use server time, else will use dates as it goes from database.

```php
  $helper->config["server_date"] = true;
```

To save data to the database, use the method 'saveData([dataArray])':

```php
  // To save data of the field 'FLD_RECURRING_TYPE', you can use a data array or a string in the format 'week_2_____1,3#10'.
  $newRecurringTypeArray = array(
    "each" => "week",
    "step" => 2,
    "days_of_week" => "monday,wednesday", // if the field 'week_number' is set, the field 'days_of_week' must contain only one value
  //  "week_number" => 2,
    "repeat" => 10
  );

  $helper->saveData(array(
  //    $helper::FLD_ID => "20", // if you pass this field for saving, data in the database will be updated by this value, otherwise new data will be written
    $helper::FLD_RECURRING_TYPE => $newRecurringTypeArray,
    $helper::FLD_START_DATE => "2015-09-30 00:00:00",
    $helper::FLD_END_DATE => $helper->getRecurringEndDateStr($newRecurringTypeArray, "2015-09-30 00:00:00", 500), // to count the end date of the recurring series, you can use the function 'getRecurringEndDateStr'
    $helper::FLD_LENGTH => 500,
    "my_property_field" => "Any data..." // new fields defined by the user must be presented in this way
  ));
```

To delete data from the database, you should use the method 'deleteById([ID])':

```php
  $helper->deleteById(48); // will delete data by the field 'FLD_ID'.
```

To get data of the recurring events, use the method 'getData([$startDateStr], [$endDateStr])':

```php
  $helper->getData("2015-02-10 09:00:00", "2020-01-02 07:00:00");
  
  // The function will return recurring events from the defined range taking into account exclusion of events series
  // The result will look as follows:
  //array(
  //  array(
  //   "start_date" => "2015-02-13 00:00:00",
  //   "end_date" => "2015-02-15 00:00:00",
  //   "text" => "Second Friday",
  //   ...
  //  ),
  //  ....
  //);
```

#####Tests

In order to run tests
1) Install PHPUnit following this instruction https://phpunit.de/manual/current/en/installation.html
2) Configure DB settings in tests/TestConfig.php
3) Enter repository folder and execute
```
    phpunit --bootstrap SchedulerHelper.php tests/SchedulerHelperTest
```

### License

The MIT License

Copyright (c) 2015 DHTMLX

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
IN THE SOFTWARE.
===============
