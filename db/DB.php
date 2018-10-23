<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/27
 * Time: 下午8:00
 */

namespace rap\db;


use rap\ioc\Ioc;

class DB {


    /**
     * 插入
     *
     * @param string          $table 表
     * @param array           $data  数据
     * @param Connection|null $connection
     *
     * @return Insert|string
     */
    public static function insert($table, $data = null, Connection $connection = null) {
        if ($data !== null) {
            return Insert::insert($table, $data, $connection);
        } else {
            return Insert::table($table, $connection);
        }
    }

    /**
     * 删除
     *
     * @param string          $table 表
     * @param array           $where 条件
     * @param Connection|null $connection
     *
     * @return null|Delete
     */
    public static function delete($table, $where = null, Connection $connection = null) {
        if ($where) {
            Delete::delete($table, $where, $connection);
        } else {
            return Delete::table($table, $connection);
        }
        return null;
    }


    /**
     * 更新
     *
     * @param string     $table 表
     * @param array      $data  数据
     * @param array      $where
     * @param Connection $connection
     *
     * @return null|Update
     */
    public static function update($table, $data = null, $where = null, Connection $connection = null) {
        if ($data) {
            Update::update($table, $data, $where, $connection);
            return null;
        } else {
            return Update::table($table, $connection);
        }
    }

    /**
     * 查询
     *
     * @param string     $table 表
     * @param Connection $connection
     *
     * @return Select
     */
    public static function select($table, Connection $connection = null) {
        return Select::table($table, $connection);
    }

    /**
     * 事务中运行
     *
     * @param \Closure $closure
     *
     * @return mixed
     */
    public static function runInTrans(\Closure $closure) {
        /* @var $connection Connection */
        $connection = Ioc::get(Connection::class);
        return $connection->runInTrans($closure);
    }

    /**
     * 执行sql语句
     *
     * @param string $sql  sql
     * @param array  $bind 数据绑定
     */
    public static function execute($sql, $bind = []) {
        /* @var $connection Connection */
        $connection = Ioc::get(Connection::class);
        $connection->execute($sql, $bind);
    }

    /**
     * 使用sql查询
     *
     * @param string $sql   sql
     * @param array  $bind  数据绑定
     * @param bool   $cache 是否使用缓存
     *
     * @return array
     */
    public static function query($sql, $bind = [], $cache = false) {
        /* @var $connection Connection */
        $connection = Ioc::get(Connection::class);
        return $connection->query($sql, $bind, $cache);
    }
}