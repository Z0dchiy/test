<?php

class Notification {
    public static function add_not($data=[]) {
        // Создаем запись об изменениях информации
        DB::query("INSERT INTO user_notifications (user_id, created, description, title) VALUES ('".Session::$user_id
            ."', '".time()."', '".$data."', 'Информация обновлена');") or die (DB::error());
    }
    public static function get($data=[]) {
        // Получаем массив уведомлений пользователей
        // Если n указан - возвращаем только непрочитанные
        $n = isset($data['n']) ? 0:1;
        $q = DB::query("SELECT title, description, viewed, created FROM user_notifications WHERE `user_id` = '".Session::$user_id."' AND `viewed` <= '".$n."';") or die (DB::error());
        return DB::fetch_all($q);
    }
    public static function read() {
        // Читает все уведомления
        DB::query("UPDATE `user_notifications` SET `viewed`='1' WHERE `user_id` = '".Session::$user_id."';") or die (DB::error());
        return 'ok';
    }

}
