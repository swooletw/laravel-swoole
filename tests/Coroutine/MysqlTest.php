<?php

namespace SwooleTW\Http\Tests\Coroutine;

use DB;

class MysqlTest
{
    public function testQuery()
    {
        //TODO pls config database.connections.mysql-coroutine and then test below
        $ret = DB::table('cash_order')->where('id', 32)->get(['order_num', 'cuser_name']);
//        CashOrder::on('mysql-coroutine')->where('id',32)->update(['cuser_name'=>'Linda']);
//        $ret=CashOrder::on('mysql-coroutine')->where('id',32)->get(['order_num','cuser_name'])->toArray();
//        $ret=CashOrder::on('mysql-coroutine')->insertGetId(['order_num'=>'xxx0011','cuser_name'=>'myname']);
//        $ret=CashOrder::on('mysql-coroutine')->where('id',32)->get(['order_num','cuser_name'])->toArray();
        $ret = DB::connection('mysql-coroutine::write')->table('cash_order')->where('id', 32)->get(['order_num', 'cuser_name']);
        $ret = DB::connection('mysql-coroutine')->select("SELECT sleep(1)");
//        $id=CashUser::on('mysql-coroutine')->insertGetId(['cid'=>78,'name'=>'ddd']);

        $id = 0;
        try {
            // I set cash_user table column `cid` for UNIQUE INDEX to test transaction err;
            DB::connection('mysql-coroutine')->transaction(function () use (&$id) {
                CashOrder::on('mysql-coroutine')->whereKey(45)->update(['order_num' => 'abcdef1111', 'cuser_name' => 'zzk']);
                $id = CashUser::on('mysql-coroutine')->insertGetId(['cid' => 89, 'name' => 'hello']);
            });
        } catch (\Exception $e) {
            $err = $e->getMessage();
            return compact('id', 'ret', 'err');
        }
        return compact('id', 'ret');
    }
}