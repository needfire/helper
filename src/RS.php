<?php

namespace majorbio\helper;

class RS
{
    /**
     * 代号
     *
     * @var integer
     */
    public int $code = 0;

    /**
     * 信息
     *
     * @var string
     */
    public string $message = '';

    /**
     * 数据
     *
     * @var mixed
     */
    public $data;

    /**
     * 构造函数
     *
     * @param integer $c
     * @param string $m
     * @param mixed $d
     * 
     * @return void
     */
    public function __construct(int $c = 0, string $m = '', $d = null)
    {
        $this->code = $c;
        $this->message = $m;
        $this->data = $d;
    }
}
