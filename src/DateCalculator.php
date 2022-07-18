<?php

namespace majorbio\helper;

use Exception;

class DateCalculator
{
    // 【干活】工作日
    const DATE_TYPE_WEEKDAYS     = 1;
    // 【干活】调休（工作日）【指周六、周日上班】
    const DATE_TYPE_WEEKEND_WORK = 2;
    // 【休息】周末
    const DATE_TYPE_WEEKEND      = 3;
    // 【休息】节假日
    const DATE_TYPE_HOLIDAY      = 4;

    public static $dateTypes = [
        self::DATE_TYPE_WEEKDAYS     => [
            'k'    => self::DATE_TYPE_WEEKDAYS,
            'v'    => '工作日',
            'work' => true
        ],
        self::DATE_TYPE_WEEKEND_WORK => [
            'k'    => self::DATE_TYPE_WEEKEND_WORK,
            'v'    => '调休',
            'work' => true
        ],
        self::DATE_TYPE_WEEKEND      => [
            'k'    => self::DATE_TYPE_WEEKEND,
            'v'    => '周末',
            'work' => false
        ],
        self::DATE_TYPE_HOLIDAY      => [
            'k'    => self::DATE_TYPE_HOLIDAY,
            'v'    => '节假日',
            'work' => false
        ],
    ];

    /**
     * 判断指定日期是否“工作日”
     *
     * @param string $date 日期格式 Y-m-d
     *
     * @return boolean
     */
    public static function isWorkday(string $date = ''): bool
    {
        $flag = true;
        // 处理参数
        $date = trim($date);
        if (empty($date)) {
            return $flag;
        }
        if (!self::isValidYmd($date, '-')) {
            return $flag;
        }
        $dateTimeStamp = strtotime($date);
        // 年月日
        $year  = (int)date('Y', $dateTimeStamp);
        $month = (int)date('m', $dateTimeStamp);
        $day   = (int)date('d', $dateTimeStamp);
        // 节假日 & 周末工作日
        $data = self::getOfficialData($year);
        if (isset($data[$month][$day])) {
            $flag = in_array($data[$month][$day], [self::DATE_TYPE_WEEKEND_WORK, self::DATE_TYPE_WEEKDAYS]);
        } else {
            $flag = !(self::isWeekend($date));
        }
        return $flag;
    }

    /**
     * 是否“休息日”
     *
     * @param string $date 日期格式 Y-m-d
     *
     * @return boolean
     */
    public static function isRestDay(string $date = ''): bool
    {
        return !self::isWorkday($date);
    }

    /**
     * 是否“周未”
     *
     * @param string $date 日期格式 Y-m-d
     *
     * @return boolean
     */
    public static function isWeekend(string $date = ''): bool
    {
        $date = trim($date);
        if (!self::isValidYmd($date, '-')) {
            return false;
        }
        return in_array(intval(date("N", strtotime($date))), [6, 7]);
    }

    /**
     * 是否合法的日期
     *
     * @param string $Ymd 日期格式 Y-m-d 2019-12-12
     * @param string $sep
     *
     * @return bool
     */
    public static function isValidYmd(string $Ymd = '', string $sep = '-'): bool
    {
        $Ymd = trim($Ymd);
        if (empty($Ymd)) {
            return false;
        }
        return date('Y' . $sep . 'm' . $sep . 'd', strtotime($Ymd)) == $Ymd;
    }

    /**
     * 获取指定日期的“下n个工作日”的日期
     *
     * @param string $startDate 日期格式 Y-m-d
     * @param int $n 下n个工作日
     *
     * @return string
     */
    public static function getNextWorkDate(string $startDate = '', int $n = 5): string
    {
        $startDate = trim($startDate);
        if (!self::isValidYmd($startDate)) {
            $startDate = date('Y-m-d');
        }
        $startTimeStamp = strtotime($startDate);
        $i              = 1;
        $d              = 0;
        $max            = 30;
        if ($n >= ($max / 2)) {
            $max = $n * 2;
        }
        while ($i < $max) {
            $okDate = date('Y-m-d', strtotime("+{$i} days", $startTimeStamp));
            if (self::isWorkday($okDate)) {
                $d++;
                if ($d == $n) {
                    return $okDate;
                }
            }
            $i++;
        }
    }

    /**
     * 判断指定日期是否在连续n天的假日“之内”
     *
     * @param string $date 日期格式 Y-m-d
     * @param int $consecutiveHolidays 连续假日天数
     *
     * @return bool
     */
    public static function isInConsecutiveHolidays(string $date = '', int $consecutiveHolidays = 3): bool
    {
        $date = trim($date);
        if (!self::isValidYmd($date)) {
            $date = date('Y-m-d');
        }
        // 当天就是工作日
        if (self::isWorkday($date)) {
            return false;
        }
        //
        $ts = strtotime($date);
        $times = [];
        // 当天休息
        $times[$date] = false;
        // 前后
        for ($i = 1; $i < $consecutiveHolidays; $i++) {
            // 前 (n - 1) 天
            $beforeDate         = date('Y-m-d', strtotime("-{$i} days", $ts));
            $times[$beforeDate] = self::isWorkday($beforeDate);
            // 后 (n - 1) 天
            $afterDate         = date('Y-m-d', strtotime("+{$i} days", $ts));
            $times[$afterDate] = self::isWorkday($afterDate);
        }
        unset($beforeDate, $afterDate, $ts, $i);
        // 排序
        ksort($times);
        // ddd($times, 1);
        // 滑动次数 $consecutiveHolidays
        for ($i = 1; $i <= $consecutiveHolidays; $i++) {
            $isContinuous = true;
            $boxKey = 1;
            // 窗口宽度
            $windowBegin = $i;
            $windowEnd = $i + $consecutiveHolidays;
            // 在 $times 上滑动窗口
            foreach ($times as $working) {
                // 窗口起始
                if ($boxKey < $windowBegin) {
                    $boxKey++;
                    continue;
                }
                // 窗口宽度到达边界则停止
                if ($windowBegin >= $windowEnd) {
                    break;
                }
                // 出现工作日则不连续，停止当前窗口
                if ($working) {
                    $isContinuous = false;
                    break;
                }
                //
                $windowBegin++;
                $boxKey++;
            }
            if ($isContinuous) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取两个日期内有多少个“工作日”，包含开始当天和结束当天
     *
     * @param int $beginTimestamp 开始时间戳
     * @param int $endTimestamp 结束时间戳
     *
     * @return int
     */
    public static function howManyWorkDay(int $beginTimestamp = 0, int $endTimestamp = 0): int
    {
        // 处理参数 $beginTimestamp
        $beginTimestamp = intval($beginTimestamp);
        if ($beginTimestamp < 1) {
            $beginTimestamp = time();
        }
        $beginTimestamp = strtotime(date('Y-m-d', $beginTimestamp));
        // 处理参数 $endTimestamp
        $endTimestamp = intval($endTimestamp);
        if ($endTimestamp < 1) {
            $endTimestamp = time();
        }
        $endTimestamp = strtotime(date('Y-m-d', $endTimestamp));
        // 比较大小
        if ($beginTimestamp > $endTimestamp) {
            $tmp = $beginTimestamp;
            $beginTimestamp = $endTimestamp;
            $endTimestamp = $tmp;
        }
        // 如果相等
        if ($beginTimestamp == $endTimestamp) {
            return 1;
        }
        // 开始计算
        $i = 0;
        $currentTimeStamp = $beginTimestamp;
        while ($currentTimeStamp <= $endTimestamp) {
            if (self::isWorkday(date('Y-m-d', $currentTimeStamp))) {
                $i++;
            }
            $currentTimeStamp += 86400;
        }
        return $i;
    }

    /**
     * 获取一天的节假日情况
     *
     * @param $timestamp 日期时间戳
     *
     * @return int 1 工作日 2调休(工作日) 3周未 4假日
     */
    public static function getDateType(int $timestamp = 0): int
    {
        $timestamp = intval($timestamp);
        if ($timestamp < 1) {
            $timestamp = time();
        }
        $date  = date('Y-m-d', $timestamp);
        $year  = (int)date('Y', $timestamp);
        $month = (int)date('m', $timestamp);
        $day   = (int)date('d', $timestamp);
        $data  = self::getOfficialData($year);
        if (empty($data)) {
            throw new Exception('没有配置' . $year . '年的节假日', 10404);
        }
        if (isset($data[$month][$day])) {
            $dateType = $data[$month][$day];
        } else {
            $dateType = self::isWeekend($date) ? self::DATE_TYPE_WEEKEND : self::DATE_TYPE_WEEKDAYS;
        }
        return $dateType;
    }

    /**
     * 官方 - 节假日 & 周末工作日
     *
     * @param integer $year 如果传值，则取出对应年份的数据，否则全部取出
     * 
     * @throws Exception
     *
     * @return array
     */
    public static function getOfficialData($year = 0): array
    {
        $year = intval($year);
        // 节假日 & 周末工作日
        $data = [
            2019 => [
                1  => [
                    1 => self::DATE_TYPE_HOLIDAY,
                ],
                2  => [
                    2  => self::DATE_TYPE_WEEKEND_WORK,
                    3  => self::DATE_TYPE_WEEKEND_WORK,
                    4  => self::DATE_TYPE_HOLIDAY,
                    5  => self::DATE_TYPE_HOLIDAY,
                    6  => self::DATE_TYPE_HOLIDAY,
                    7  => self::DATE_TYPE_HOLIDAY,
                    8  => self::DATE_TYPE_HOLIDAY,
                    9  => self::DATE_TYPE_HOLIDAY,
                    10 => self::DATE_TYPE_HOLIDAY,
                ],
                4  => [
                    5  => self::DATE_TYPE_HOLIDAY,
                    6  => self::DATE_TYPE_HOLIDAY,
                    7  => self::DATE_TYPE_HOLIDAY,
                    28 => self::DATE_TYPE_WEEKEND_WORK,
                ],
                5  => [
                    1 => self::DATE_TYPE_HOLIDAY,
                    2 => self::DATE_TYPE_HOLIDAY,
                    3 => self::DATE_TYPE_HOLIDAY,
                    4 => self::DATE_TYPE_HOLIDAY,
                    5 => self::DATE_TYPE_WEEKEND_WORK,
                ],
                6  => [
                    7 => self::DATE_TYPE_HOLIDAY,
                    8 => self::DATE_TYPE_HOLIDAY,
                    9 => self::DATE_TYPE_HOLIDAY,
                ],
                9  => [
                    13 => self::DATE_TYPE_HOLIDAY,
                    14 => self::DATE_TYPE_HOLIDAY,
                    15 => self::DATE_TYPE_HOLIDAY,
                    29 => self::DATE_TYPE_WEEKEND_WORK,
                ],
                10 => [
                    1  => self::DATE_TYPE_HOLIDAY,
                    2  => self::DATE_TYPE_HOLIDAY,
                    3  => self::DATE_TYPE_HOLIDAY,
                    4  => self::DATE_TYPE_HOLIDAY,
                    5  => self::DATE_TYPE_HOLIDAY,
                    6  => self::DATE_TYPE_HOLIDAY,
                    7  => self::DATE_TYPE_HOLIDAY,
                    12 => self::DATE_TYPE_WEEKEND_WORK,
                ],
            ],
            2020 => [
                1  => [
                    1  => self::DATE_TYPE_HOLIDAY,
                    24 => self::DATE_TYPE_HOLIDAY,
                    25 => self::DATE_TYPE_HOLIDAY,
                    26 => self::DATE_TYPE_HOLIDAY,
                    27 => self::DATE_TYPE_HOLIDAY,
                    28 => self::DATE_TYPE_HOLIDAY,
                    29 => self::DATE_TYPE_HOLIDAY,
                    30 => self::DATE_TYPE_HOLIDAY,
                    31 => self::DATE_TYPE_HOLIDAY,
                ],
                2  => [
                    1 => self::DATE_TYPE_HOLIDAY,
                    2 => self::DATE_TYPE_HOLIDAY,
                ],
                4  => [
                    4  => self::DATE_TYPE_HOLIDAY,
                    5  => self::DATE_TYPE_HOLIDAY,
                    6  => self::DATE_TYPE_HOLIDAY,
                    26 => self::DATE_TYPE_WEEKEND_WORK,
                ],
                5  => [
                    1 => self::DATE_TYPE_HOLIDAY,
                    2 => self::DATE_TYPE_HOLIDAY,
                    3 => self::DATE_TYPE_HOLIDAY,
                    4 => self::DATE_TYPE_HOLIDAY,
                    5 => self::DATE_TYPE_HOLIDAY,
                    9 => self::DATE_TYPE_WEEKEND_WORK,
                ],
                6  => [
                    25 => self::DATE_TYPE_HOLIDAY,
                    26 => self::DATE_TYPE_HOLIDAY,
                    27 => self::DATE_TYPE_HOLIDAY,
                    28 => self::DATE_TYPE_WEEKEND_WORK,
                ],
                9  => [
                    27 => self::DATE_TYPE_WEEKEND_WORK,
                ],
                10 => [
                    1  => self::DATE_TYPE_HOLIDAY,
                    2  => self::DATE_TYPE_HOLIDAY,
                    3  => self::DATE_TYPE_HOLIDAY,
                    4  => self::DATE_TYPE_HOLIDAY,
                    5  => self::DATE_TYPE_HOLIDAY,
                    6  => self::DATE_TYPE_HOLIDAY,
                    7  => self::DATE_TYPE_HOLIDAY,
                    8  => self::DATE_TYPE_HOLIDAY,
                    10 => self::DATE_TYPE_WEEKEND_WORK,
                ],
            ],
            2021 => [
                1  => [
                    1 => self::DATE_TYPE_HOLIDAY,
                    2 => self::DATE_TYPE_HOLIDAY,
                    3 => self::DATE_TYPE_HOLIDAY,
                ],
                2  => [
                    7  => self::DATE_TYPE_WEEKEND_WORK,
                    11 => self::DATE_TYPE_HOLIDAY,
                    12 => self::DATE_TYPE_HOLIDAY,
                    13 => self::DATE_TYPE_HOLIDAY,
                    14 => self::DATE_TYPE_HOLIDAY,
                    15 => self::DATE_TYPE_HOLIDAY,
                    16 => self::DATE_TYPE_HOLIDAY,
                    17 => self::DATE_TYPE_HOLIDAY,
                    20 => self::DATE_TYPE_WEEKEND_WORK,
                ],
                4  => [
                    3  => self::DATE_TYPE_HOLIDAY,
                    4  => self::DATE_TYPE_HOLIDAY,
                    5  => self::DATE_TYPE_HOLIDAY,
                    25 => self::DATE_TYPE_WEEKEND_WORK,
                ],
                5  => [
                    1 => self::DATE_TYPE_HOLIDAY,
                    2 => self::DATE_TYPE_HOLIDAY,
                    3 => self::DATE_TYPE_HOLIDAY,
                    4 => self::DATE_TYPE_HOLIDAY,
                    5 => self::DATE_TYPE_HOLIDAY,
                    8 => self::DATE_TYPE_WEEKEND_WORK,
                ],
                6  => [
                    12 => self::DATE_TYPE_HOLIDAY,
                    13 => self::DATE_TYPE_HOLIDAY,
                    14 => self::DATE_TYPE_HOLIDAY,
                ],
                9  => [
                    18 => self::DATE_TYPE_WEEKEND_WORK,
                    19 => self::DATE_TYPE_HOLIDAY,
                    20 => self::DATE_TYPE_HOLIDAY,
                    21 => self::DATE_TYPE_HOLIDAY,
                    26 => self::DATE_TYPE_WEEKEND_WORK,
                ],
                10 => [
                    1 => self::DATE_TYPE_HOLIDAY,
                    2 => self::DATE_TYPE_HOLIDAY,
                    3 => self::DATE_TYPE_HOLIDAY,
                    4 => self::DATE_TYPE_HOLIDAY,
                    5 => self::DATE_TYPE_HOLIDAY,
                    6 => self::DATE_TYPE_HOLIDAY,
                    7 => self::DATE_TYPE_HOLIDAY,
                    9 => self::DATE_TYPE_WEEKEND_WORK,
                ],
            ],
            2022 => [
                1  => [
                    1  => self::DATE_TYPE_HOLIDAY,
                    2  => self::DATE_TYPE_HOLIDAY,
                    3  => self::DATE_TYPE_HOLIDAY,
                    29 => self::DATE_TYPE_WEEKEND_WORK,
                    30 => self::DATE_TYPE_WEEKEND_WORK,
                    31 => self::DATE_TYPE_HOLIDAY,
                ],
                2  => [
                    1 => self::DATE_TYPE_HOLIDAY,
                    2 => self::DATE_TYPE_HOLIDAY,
                    3 => self::DATE_TYPE_HOLIDAY,
                    4 => self::DATE_TYPE_HOLIDAY,
                    5 => self::DATE_TYPE_HOLIDAY,
                    6 => self::DATE_TYPE_HOLIDAY,
                ],
                4  => [
                    2  => self::DATE_TYPE_WEEKEND_WORK,
                    3  => self::DATE_TYPE_HOLIDAY,
                    4  => self::DATE_TYPE_HOLIDAY,
                    5  => self::DATE_TYPE_HOLIDAY,
                    24 => self::DATE_TYPE_WEEKEND_WORK,
                    30 => self::DATE_TYPE_HOLIDAY,
                ],
                5  => [
                    1 => self::DATE_TYPE_HOLIDAY,
                    2 => self::DATE_TYPE_HOLIDAY,
                    3 => self::DATE_TYPE_HOLIDAY,
                    4 => self::DATE_TYPE_HOLIDAY,
                    7 => self::DATE_TYPE_WEEKEND_WORK,
                ],
                6  => [
                    3 => self::DATE_TYPE_HOLIDAY,
                    4 => self::DATE_TYPE_HOLIDAY,
                    5 => self::DATE_TYPE_HOLIDAY,
                ],
                9  => [
                    10 => self::DATE_TYPE_HOLIDAY,
                    11 => self::DATE_TYPE_HOLIDAY,
                    12 => self::DATE_TYPE_HOLIDAY,
                ],
                10 => [
                    1 => self::DATE_TYPE_HOLIDAY,
                    2 => self::DATE_TYPE_HOLIDAY,
                    3 => self::DATE_TYPE_HOLIDAY,
                    4 => self::DATE_TYPE_HOLIDAY,
                    5 => self::DATE_TYPE_HOLIDAY,
                    6 => self::DATE_TYPE_HOLIDAY,
                    7 => self::DATE_TYPE_HOLIDAY,
                    8 => self::DATE_TYPE_WEEKEND_WORK,
                    9 => self::DATE_TYPE_WEEKEND_WORK,
                ],
            ],
        ];
        if ($year > 0) {
            if (!isset($data[$year])) {
                throw new Exception('没有配置' . $year . '年的节假日', 10404);
            }
            return $data[$year];
        } else {
            // 全部返回
            return $data;
        }
    }
}
