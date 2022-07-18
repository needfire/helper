- 工具包一、日期计算、工作日排班
    - 基于节假日
    - 每年需要手工维护节假日数据
- 工具包二、用于接口、程序内部数据交互的结构

<br>


# 安装
```bash
composer require majorbio/helper
```

# 示例

工具包一、日期计算、工作日排班
```php
<?php

use majorbio\helper\DateCalculator;

// 是否工作日
DateCalculator::isWorkday('2022-03-02');

// 是否休息日
DateCalculator::isRestDay('2022-03-02');

// 是否周六or周日（不一定是休息日！）
DateCalculator::isWeekend('2022-02-27');

// 是否正确的日期
DateCalculator::isValidYmd('2022-02-29');

// “下n个工作日”的日期
DateCalculator::getNextWorkDate('2022-03-02', 7);

// 日期是否在连续n天的假日“之内”
DateCalculator::isInConsecutiveHolidays('2022-02-06', 7);

// 两个“时间戳”之间有多少个工作日
DateCalculator::howManyWorkDay(1644076888, 1646208036);
```

工具包二、用于接口、程序内部数据交互的结构
```php
<?php

use majorbio\helper\RS;

$rs = new RS(0, 'ok', ['数据', 'else']);

response()->json($rs);
/* 响应
{
    "code": 0,
    "message": "ok",
    "data": [
        "数据",
        'else'
    ]
}
*/
```