<?php

class User {

    // GENERAL

    public static function user_info($data) {
        // vars
        $user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
        $phone = isset($data['phone']) ? preg_replace('~[^\d]+~', '', $data['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else return [];
        // info
        // 16.11.2021 [Python] Здесь не получают значение phone, но пробует его подставлять в запрос. Их необходимо вывести или убрать из return?
        $q = DB::query("SELECT user_id, first_name, last_name, middle_name, email, gender_id, count_notifications, phone FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'middle_name' => $row['middle_name'],
                'gender_id' => (int) $row['gender_id'],
                'email' => $row['email'],
                'phone' => (int) $row['phone'],
                'phone_str' => phone_formatting($row['phone']),
                'count_notifications' => (int) $row['count_notifications']
            ];
        } else {
            return [
                'id' => 0,
                'first_name' => '',
                'last_name' => '',
                'middle_name' => '',
                'gender_id' => 0,
                'email' => '',
                'phone' => '',
                'phone_str' => '',
                'count_notifications' => 0
            ];
        }
    }

    public static function user_get_or_create($phone) {
        // validate
        $user = User::user_info(['phone' => $phone]);
        $user_id = $user['id'];
        // create
        if (!$user_id) {
            DB::query("INSERT INTO users (status_access, phone, created) VALUES ('3', '".$phone."', '".Session::$ts."');") or die (DB::error());
            $user_id = DB::insert_id();
        }
        // output
        return $user_id;
    }

    // TEST

    public static function owner_info($data=[]) {
        // your code here ...
      //Не увидел смысла подобной функции
    }

    public static function owner_update($data=[]) {
        //Возможно я ошибаюсь, но подобный формат работы с данными является уязвимым для инъекций. Недочет не закрываю, т.к. в т.з. указано: повторять код по аналогии с вашим варинатом :)
        $user_id = Session::$user_id;
        $q = DB::query("SELECT first_name, last_name, middle_name, email, phone FROM users WHERE `user_id` = ".$user_id." LIMIT 1;") or die (DB::error());
        if ($user = DB::fetch_row($q)) {
            if(!empty($data['first_name']) || !empty($data['last_name']) || isset($data['middle_name']) || isset($data['email']) || !empty($data['phone'])) {
                //Сохраняем предыдущую версию для отчетности
                $temp_data = json_encode($user);

                $user['first_name'] = !empty($data['first_name']) ? $data['first_name']:$user['first_name'];
                $user['last_name'] = !empty($data['last_name']) ? $data['last_name']:$user['last_name'];
                $user['middle_name'] = $data['middle_name'] ?? $user['middle_name'];
                $user['email'] = isset($data['email']) ? strtolower($data['email']):$user['email'];
                if(!empty($data['phone'])){
                    $data['phone'] = preg_replace('/[^0-9]/', '', $data['phone']);
                    if((strlen($data['phone']) == 11) && (substr($data['phone'], 0, 1) == 7)) $user['phone'] = $data['phone'];
                }
                DB::query("UPDATE `users` SET 
                    `first_name`='" . $user['first_name'] . "', 
                    `last_name`='" . $user['last_name'] . "', 
                    `middle_name`='" . $user['middle_name'] . "', 
                    `email`='" . $user['email'] . "',
                    `phone`='" . $user['phone'] . "'
                     WHERE `user_id` = '".$user_id."';") or die (DB::error());

                //Если обновление прошло удачно и какой либо параметр изменился, записываем уведомление с массивом
                // предыдущей версии
                if($temp_data != json_encode($user)) Notification::add_not($temp_data);
            } else return error_response(1003, 'Нет ни одного параметра.');
        }
    }

}
