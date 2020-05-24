<?php
// Для начала я оптимизировал таблицы, добавил foreign_key указав связь юзера с телефонными номерами
// изменил столбец gender с int(11) на более легое tinyint(1) и повесил на него индекс так как уже видно что запрос будет идти по гендорному признаку
// так же добавил генерируемое поле `age` но можно и без него, стоит просто указать это запись (TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(birth_date), CURDATE())) в секции where
/**
CREATE TABLE `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) DEFAULT NULL,
    `gender` TINYINT(1) NOT NULL COMMENT '0 - не указан, 1 - мужчина, 2 - женщина.',
    `birth_date` INT(11) NOT NULL COMMENT 'Дата в unixtime.',
    `age` TINYINT(3) AS (TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(birth_date), CURDATE())),
PRIMARY KEY (`id`),
INDEX (`gender`)
)ENGINE=INNODB;

CREATE TABLE `phone_numbers` (
    `id`	INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `phone`	VARCHAR(255) DEFAULT NULL,
PRIMARY KEY (`id`),
FOREIGN KEY (`user_id`)
    REFERENCES users(`id`)
    ON DELETE CASCADE
)ENGINE=INNODB;
 */

/**
Вот сам запрос на получение кол-ва номеров телефонов девушек в возрасте от 18 до 22 лет
SELECT
    `users`.`name`,
    count(`phone_numbers`.`phone`) as phones
FROM `users`
LEFT JOIN phone_numbers ON (`user_id` = `users`.`id`)
WHERE (`users`.`age` BETWEEN 18 AND 22) AND (gender = 2) GROUP BY (`phone_numbers`.`user_id`)
 */

/**
 * @param string $url
 * @return string
 */
function generateUrl($url)
{
    /**
     * разбиваю урл на комоненты
     */
    $urlComponents = parse_url($url);

    /**
     * получаю массив гет параметров из комнента урла 'query'
     */
    $queryArr = [];
    parse_str($urlComponents['query'], $queryArr);
    /**
     * сортирую массив по значению
     */
    asort($queryArr);

    /**
     * чтобы удалить элемент массива по значению, переворачиваю его,
     * не очень хороший прием т.к. могут быть повторяющиеся значения,
     * но в данном случае подходит
     * в случае чего, всегда можно использовать циклы, тут я оставил так, т.к. код состоит из использования функций)
     */
    $valueByKey = array_flip($queryArr);
    /** удалем значение 3*/
    unset($valueByKey[3]);

    /**
     * возращаем все как было
     */
    $keyByValue = array_flip($valueByKey);

    /**
     * добавляем новый параметр 'url'
     */
    $keyByValue['url'] = $urlComponents['path'];
    /**
     * осталось всё скрепить по средствам конкатинации
     */
    $generatedUrl = $urlComponents['scheme'] . '://' . $urlComponents['host'] . '/?' . http_build_query($keyByValue);

    return $generatedUrl;
}
echo '<pre>';
var_dump(generateUrl('https://www.somehost.com/test/index.html?param1=4&param2=3&param3=2&param4=1&param5=3'));


//первое что мне не нравится и сразу бросается в глаза, что если не будет найден не один пользователь то не будет создан
//массив $data, и во втором фориче бедет ошибка
//Второе - это у функции много обязанностей она и пурсит гет, и коннектится к базе и делает фыборки и даже после этого закрывает коннект к дб)
// если все это делать в процедурном стиле, я бы сделал примерно так
function getConfig()
{
    return [
        'host' => 'localhost',
        'user' => 'root',
        'password' => '123123',
        'database' => 'database',
    ];
}

/**
 * @return false|mysqli
 * @throws Exception
 */
function getConnect()
{
    $config = getConfig();

    $db = mysqli_connect(...$config);

    if (!$db) {
        throw new Exception('Connection refused');
    }

    return $db;
}

/**
 * @param $table
 * @param $id
 * @param string $row
 * @return array
 * @throws Exception
 */
function selectById($table, $id, $row = "name")
{
    $data = [];
    $db = getConnect();
    $sql = "SELECT * FROM {$table} WHERE id=?";
    $query = mysqli_prepare($db, $sql);
    $query->bind_param('i', $id);
    
    if (!$query->execute()) {
        return $data;
    }
    
    $result = $query->get_result();
    while($obj = $result->fetch_object()){
        $data[$id] = $obj->$row;
    }

    $result->close();

    $db->close();

    return $data;
}

/**
 * @param $str
 * @param string $delimeter
 * @return array
 * @throws Exception
 */
function parsStringToArray($str, $delimeter = ',')
{
    if (empty($str)) {
        throw new Exception('Empty get param');
    }
    
    return explode($delimeter, $str);
}

/**
 * @param $user_ids
 * @return array
 * @throws Exception
 */
function loadUsersData($user_ids)
{
    $data = [];

    $user_ids = parsStringToArray($user_ids);

    foreach ($user_ids as $user_id) {
        $data = selectById('users', $user_id, 'name');
    }

    if (empty($data)) {
        throw new Exception('No users by params');
    }

    return $data;

}

// Как правило, в $_GET["user_ids"] должна приходить строка
// с номерами пользователей через запятую, например: 1,2,17,48
try {
    $data = loadUsersData($_GET["user_ids"]);
    foreach ($data as $user_id => $name) {
        echo "<a href=\"/show_user.php?id={$user_id}\">$name</a>";
    }
} catch (Exception $exception) {
    echo $exception->getMessage();
}


