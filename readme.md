Sheduler Helper for PHP
========================

### Requirements

	- PHP>=5.4 (PDO~)
	- MySQL, PostgreSQL, Sqlite and etc.

### Installation

	- composer 
	
	or
	
	- just download the files from this repository reposetory 'git@github.com:DHTMLX/scheduler-helper-php.git'
	
	require_once "./SchedulerHelper.php";
	use Scheduler\Helper;

### How to use

Для создания объекта хелпера необходимо вызвать конструктор класса Scheduler\Helper::Helper([$connectorDataArray]):

```
	$helper = new Helper(
		array(
		    "dbsm": "mysql", //Необязательный параметр. По умолчанию установлено "mysql".
		    host: "localhost", //Необязательный параметр. По умолчанию установлено "localhost".
		    "db_name" => "scheduler_helper_db",
		    "user" => "root",
		    "password" => "root",
		    "table_name" => "events_rec" //Имя таблицы в которой хранятся данные повторяющихся событий.
		)
	);
```

В хелпере определены стандартный набор полей для работы с таблицей:

```
	helper::FLD_ID => "event_id",
	helper::FLD_START_DATE => "start_date",
	helper::FLD_END_DATE => "end_date",
	helper::FLD_TEXT => "text",
	helper::FLD_RECURRING_TYPE => "rec_type",
	helper::FLD_PARENT_ID => "event_pid",
	helper::FLD_LENGTH => "event_length"
```

Для переопределения и создания новые полей необходимо использовать метод 'setFieldsNames([$fieldsDataArray])': 

```
	$helper->setFieldsNames(array(
		$helper::FLD_RECURRING_TYPE => "my_recurring_type_field", //Переопределение поля 'FLD_RECURRING_TYPE'.
		"my_property_field" //Инициализация нового поля.
	));
```

Для сохранения данных в базу необходимо использовать метод 'saveData([dataArray])':

```
	//Для сохранения данных поля 'FLD_RECURRING_TYPE' вы можете использовать массив данных или строку формата 'week_2_____1,3#10'.
	$newRecurringTypeArray = array(
		"each" => "week",
		"step" => 2,
		"days_of_week" => "monday,wednesday", //Если поле 'week_number' установлено, то поле 'days_of_week' должно содержать только одно значение.
	//  "week_number" => 2,
		"repeat" => 10
	);

	$helper->saveData(array(
	//    $helper::FLD_ID => "20", //Если для сохранения передавать это поле, то данные в базе данных будут обновлены по этому значению, иначе записаны новые.
		$helper::FLD_RECURRING_TYPE => $newRecurringTypeArray,
		$helper::FLD_START_DATE => "2015-09-30 00:00:00",
		$helper::FLD_END_DATE => $helper->getRecurringEndDateStr($newRecurringTypeArray, "2015-09-30 00:00:00", 500), //Для расчета конечной даты повторяющейся серии вы можете использовать функцию 'getRecurringEndDateStr'.
		$helper::FLD_LENGTH => 500,
		"my_property_field" => "Any data..." //Новые поля определенные пользователем должны иметь такой вид.
	));
```

Для удаления данных из базы данных необходимо использовать метод 'deleteById([ID])':

```
	$helper->deleteById(48); //Удалит данные по полю 'FLD_ID'.
```

Для получения данных повторяющихся событий необходимо использовать метов 'getData([$startDateStr], [$endDateStr])':

```
	$helper->getData("2015-02-10 09:00:00", "2020-01-02 07:00:00");
	
	//Функция вернет повторяющиеся события по данному диапозону с учетом исключений серии событий.
	//Результат имеет вид:
	//array(
	//	array(
	//	 "start_date" => "2015-02-13 00:00:00",
	//	 "end_date" => "2015-02-15 00:00:00",
	//	 "text" => "Second Friday",
	//	 ...
	//	),
	//	....
	//);
```


### License

DHTMLX is published under the GPLv3 license.

License:

	Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
	to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
	and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
	IN THE SOFTWARE.


Copyright (c) 2015 DHTMLX
