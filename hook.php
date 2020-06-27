<?php
// подрубаем API
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/credentials.php';
require __DIR__ . '/functions.php';

$bot = new \TelegramBot\Api\Client($bot_api_key,null);
if($_GET["bname"] == $bot_username){
    $bot->sendMessage($bot_username, "Тест");
}

// если бот еще не зарегистрирован - регистируем
if(!file_exists("registered.trigger")){
    /**
     * файл registered.trigger будет создаваться после регистрации бота.
     * если этого файла нет значит бот не зарегистрирован
     */

    // URl текущей страницы
    $result = $bot->setWebhook($hook_url);
    if($result){
        file_put_contents("registered.trigger",time()); // создаем файл дабы прекратить повторные регистрации
    } else die("ошибка регистрации");
}

// Команды бота
// пинг. Тестовая
$bot->command('ping', function ($message) use ($bot) {
    $bot->sendMessage($message->getChat()->getId(), 'pong!');
});

$keyboards = [
    "start" => [
        [
            ["go", "text" => "Поехали"]
        ]
    ],
    "haveCode" => [
        [
            [
                ['callback_data' => 'myFriend_yes', 'text' => 'Да 😃'],
                ['callback_data' => 'myFriend_no', 'text' => 'Нет, я тут первый']
            ]
        ]
    ],
    "mainMenu" =>[
        [
            ["text" => "Профиль"],
            ["text" => "Карта"]
        ],
        [
            ["text" => "Пройти отрезок лабиринта"]
        ]
    ],
    "goToMainMenu" => [
        [
            ["back", "text" => "Вернуться"]
        ]
    ],
    "mapMenu" => [
        [
            ["text" => "Схема уровней"],
            ["text" => "Описание туров"]
        ],
        [
            ["text" => "Картина лабиринта"]
        ],
        [
            ["text" => "Вернуться"]
        ]
    ],
    "goToMap" => [
        [
            ["text" => "Вернуться в раздел карта"]
        ],
        [
            ["text" => "Вернуться в главное меню"]
        ]
    ],
    "labirint" => [
        [
            ["text" => "Цель недели"],
            ["text" => "Инструменты"],
            ["text" => "Ресурсы"]
        ],
        [
            ["text" => "Дневник"],
            ["text" => "Трекеры"],
            ["text" => "Зигзаги дня"]
        ],
        [
            ["text" => "ВЫБИРАЙ ПОВОРОТ"]
        ],
        [
            ["text" => "Вернуться в главное меню"]
        ]
    ],
    "direction" => [
        [
            ["text" => "Влево"],
            ["text" => "Прямо"],
            ["text" => "Вправо"]
        ]
    ]
];

function checkFriend($message) {
    global $bot;
//    $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
//        [
//            [
//                ['callback_data' => 'myFriend_yes', 'text' => 'Да 😃'],
//                ['callback_data' => 'myFriend_no', 'text' => 'Нет, я тут первый']
//            ]
//        ]
//    );
    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(
        [
            [
                ['callback_data' => 'myFriend_yes', 'text' => 'Да 😃'],
                ['callback_data' => 'myFriend_no', 'text' => 'Нет, какой код?']
            ]
        ],null, true
    );
    $bot->sendMessage($message->getChat()->getId(), "У тебя уже есть код от друга?",false, null, null, $keyboard);
}

// обязательное. Запуск бота
$bot->command('start', function ($message) use ($bot, $keyboards, $conn) {
    $from = $message->getFrom();
    $user_id    = $from->getId();
    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['start'], null, true);

    $sql = "SELECT name, user_id from users where user_id='".$user_id."'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $bot->sendMessage($message->getChat()->getId(), 'Ты уже зарегистрирован, '.$row['name'], null, false, null, $keyboard);
        }
    } else {
        $sql = "INSERT INTO users (name, lastname, username, previousCommand, user_id)
VALUES ('".$from->getFirstName()."','".$from->getLastName()."','".$from->getUsername()."', 'start', '".$user_id."')";
        if ($conn->query($sql) === TRUE) {
            $answer = 'Ну, что, '.$from->getFirstName().', готов отправиться в удивительное путешествие со
своим Другом по Лабиринту Дружбы? Проверить свою
Дружбу на прочность, убедиться, что ваша Дружба не
токсична для общества и вы не являетесь потребителями
чужого времени. Научиться сонаставничеству и помочь
своему Другу преодолеть свои страхи, и наконец, найти
выход, стать победителями и выиграть главный приз.

Все что тебе нужно это пригласить с собой своего Лучшего
Друга! Запастись особыми инструментами, выбрать
правильные повороты, использовать клубок ниток, собрать
кусочки общей карты и преодолевая разные препятствия
вместе с Другом найти выход из Лабиринта.';

            $bot->sendMessage($message->getChat()->getId(), $answer, null, false, null, $keyboard);
        } else {
            $bot->sendMessage($message->getChat()->getId(), "Error: ".$sql."<br>".$conn->error);
        }
    }
});

// помощь
$bot->command('help', function ($message) use ($bot) {
    $answer = 'Команды:
/help - помощь';
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

// Кнопки у сообщений
$bot->command("ibutton", function ($message) use ($bot, $keyboards) {
    $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($keyboards['haveCode']);

    $bot->sendMessage($message->getChat()->getId(), "тест", false, null,null,$keyboard);
});

// Обработка кнопок у сообщений
$bot->on(function($update) use ($bot, $callback_loc, $find_command, $keyboards, $conn){
    $callback = $update->getCallbackQuery();
    $message = $callback->getMessage();
    $username = $message->getFrom()->getUsername();
    $chatId = $message->getChat()->getId();
    $data = $callback->getData();

    if($data == "myFriend_yes"){
        $sql = "UPDATE users SET previousCommand='myFriend_yes' WHERE username='" . $username . "'";
        $bot->sendMessage($chatId, $sql);
        if ($conn->query($sql) === TRUE) {
            $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['mainMenu'], null, true);
            $bot->sendMessage($chatId, 'Введи код ниже:', null, false, null, $keyboard);
            $bot->answerCallbackQuery($callback->getId());
        } else {
            $bot->sendMessage($chatId, 'Что-то пошло не так, мы над этим работаем');
            $bot->answerCallbackQuery($callback->getId());
        }
    }
    if($data == "myFriend_no"){
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(
            [
                [
                    ["test", "text" => "Test"]
                ]
            ], null, true);
        $bot->sendMessage($chatId, "Вот твой код для друга, вышли ему!", null, false, null, $keyboard);
        $bot->answerCallbackQuery($callback->getId());
    }
},function($update){
    $callback = $update->getCallbackQuery();
    if (is_null($callback) || !strlen($callback->getData()))
        return false;
    return true;
});

// Reply-Кнопки
$bot->command("buttons", function ($message) use ($bot) {
    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[["text" => "как дела"], ["text" => "курсы валюты"]]], true, true);

    $bot->sendMessage($message->getChat()->getId(), "тест", false, null,null, $keyboard);
});

// Отлов любых сообщений + обрабтка reply-кнопок
$bot->on(function($Update) use ($bot, $conn, $keyboards){
    $message = $Update->getMessage();
    $chatId = $message->getChat()->getId();
    $user_id = $message->getFrom()->getId();
    $mtext = $message->getText();

    if(mb_stripos($mtext,"Поехали") !== false){
        checkFriend($message);
    }
    elseif(mb_stripos($mtext,"Да 😃") !== false){
        $res = setPreviousCommand($user_id,'setTeamCode');
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardRemove(true);
        if ($res === TRUE) {
            $bot->sendMessage($message->getChat()->getId(), 'Введи код ниже:', false, null,null, $keyboard);
        } else {
            $bot->sendMessage($message->getChat()->getId(), 'Что-то пошло не так, мы над этим работаем', false, null,null, $keyboard);
        }
    }
    elseif(mb_stripos($mtext,"Нет, какой код?") !== false){
        $code = random_strings();
        $result = setTeamCode($user_id, $code);
        if ($result === TRUE) {
            $res = setPreviousCommand($user_id,'setTeamName');
            $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardRemove(true);
            if ($res === TRUE) {
                $bot->sendMessage($message->getChat()->getId(), "Держи код. Отправь его другу:");
                $bot->sendMessage($message->getChat()->getId(), "*".$code."*", 'markdown', false, null);
                $bot->sendMessage($chatId, 'Давай назовем вашу команду:',false, null,null, $keyboard);
            } else {
                $bot->sendMessage($message->getChat()->getId(), 'Что-то пошло не так, мы над этим работаем',false, null,null, $keyboard);
            }
        } else {
            $bot->sendMessage($chatId, $result);
        }
    }
    elseif(mb_stripos($mtext,"Профиль") !== false) {
        $res = getProfileInfo($user_id);
        if ($res !== FALSE) {
            $sum = $res['me']['rate']+$res['friend']['rate'];
            $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['goToMainMenu'], null, true);
            $message = 'Ваше имя: *'.$res['me']['name'].'* (у вас *'.$res['me']['rate'].'* баллов)
Имя вашего друга: *'.$res['friend']['name'].'* (у вашего друга *'.$res['friend']['rate'].'* баллов)
Ваша команда: *'.$res['me']['teamName'].'*
У вашей команды: *'.$sum.'* баллов';
            $bot->sendMessage($chatId, $message, 'markdown', false, null, $keyboard);
        }
    }
    elseif(mb_stripos($mtext,"Карта") !== false) {
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['mapMenu'], null, true);
        $message = '*Карта*
Здесь информация о вашем нахождении';
        $bot->sendMessage($chatId, $message, 'markdown', false, null, $keyboard);

    }
    elseif(mb_stripos($mtext,"Пройти отрезок лабиринта") !== false) {
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['labirint'], null, true);
        $bot->sendMessage($chatId, '*Главное меню*', 'markdown', false, null, $keyboard);
    }
    elseif(mb_stripos($mtext,"Описание туров") !== false) {
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['goToMap'], null, true);
        $message = 'Тут описание туров';
        $bot->sendMessage($chatId, $message, 'markdown', false, null, $keyboard);
    }
    elseif(mb_stripos($mtext,"Схема уровней") !== false) {
        $pic = "https://bot.elen.kz/images/shema.jpg";
        $caption = 'Это схема уровней';
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['goToMap'], null, true);
        $bot->sendPhoto($chatId, $pic, $caption, null, $keyboard );

    }
    elseif(mb_stripos($mtext,"Картина лабиринта") !== false) {
        $pic = "https://bot.elen.kz/images/labirint.png";
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['goToMap'], null, true);
        $caption = 'Это карта лабиринта уровней';
        $bot->sendPhoto($chatId, $pic, $caption, null, $keyboard );

    }
    elseif(mb_stripos($mtext,"Вернуться") !== false) {
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['mainMenu'], null, true);
        $bot->sendMessage($chatId, '*Главное меню*', 'markdown', false, null, $keyboard);
    }
    elseif(mb_stripos($mtext,"Вернуться в раздел карта") !== false) {
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['mapMenu'], null, true);
        $bot->sendMessage($chatId, '*Карта*', 'markdown', false, null, $keyboard);
    }
    elseif(mb_stripos($mtext,"Вернуться в главное меню") !== false) {
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['mainMenu'], null, true);
        $bot->sendMessage($chatId, '*Главное меню*', 'markdown', false, null, $keyboard);
    }
    elseif(mb_stripos($mtext,"ВЫБИРАЙ ПОВОРОТ") !== false) {
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['direction'], null, true);
        $bot->sendMessage($chatId, '*Выбери куда ты пойдешь*', 'markdown', false, null, $keyboard);
    }
    elseif(mb_stripos($mtext,"Влево") !== false || mb_stripos($mtext,"Прямо") !== false || mb_stripos($mtext,"Вправо") !== false ) {
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['mainMenu'], null, true);
        $bot->sendMessage($chatId, '*Главное меню*', 'markdown', false, null, $keyboard);
    }
    elseif(mb_stripos($mtext,"Чубакабра") !== false){
        $pic='https://bot.elen.kz/images/mp4.mp4';
        $res = getAllUsers();
        if ($res !== FALSE) {
            foreach ($res as $usr) {
                try {
//                    $bot->sendAnimation($usr, $pic);
                    $bot->sendMessage($usr, "*Я вас всех удалил!!!*",'markdown');
                    setBlockStatus($usr, 0);
                } catch (Exception $e) {
                    setBlockStatus($usr, 1);
                }
            }
        } else {
            $bot->sendMessage($chatId, 'FALSE','markdown');
        }
    }
    elseif($mtext != '') {
        $row = getPreviousCommand($user_id);
        if ($row == 'setTeamCode') {
            $check = checkTeamCode($mtext);
            if ($check == 1) {
                $teamName = getTeamNameByCode($mtext);
                $result = setTeamCode($user_id, $mtext);
                if ($result === TRUE) {
                    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['mainMenu'], null, true);
                    if ($teamName !== NULL && $teamName !== FALSE) {
                        $bot->sendMessage($chatId, 'Ваша команда: *'.$teamName.'*', 'markdown', false, null, $keyboard);
                        setTeamName($user_id, $teamName);
                        setPreviousCommand($user_id,'welcome');
                    } else {
                        $bot->sendMessage($message->getChat()->getId(), 'Что-то пошло не так, мы над этим работаем',false, null,null, $keyboard);
                    }
                } elseif ($result !== FALSE) {
                    $bot->sendMessage($chatId, $result);
                }
            } elseif($check > 1) {
                $bot->sendMessage($chatId, 'В команде может быть только 2 друга.');
            } else {
                $bot->sendMessage($chatId, 'У вас неправильный код.');
            }
        } elseif($row == 'setTeamName') {
            $result = setTeamName($user_id, $mtext);
            if ($result === TRUE) {
                $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keyboards['mainMenu'], null, true);
                $bot->sendMessage($chatId, 'Мы сохранили название вашей команды.
Добро пожаловать в Twogether Navigator, '.$mtext, null, false, null, $keyboard);
                setPreviousCommand($user_id,'welcome');
            } else {
                $bot->sendMessage($chatId, $result);
            }
        }
    }
},function($update) use ($bot) {
    $msg = $update->getMessage();
    if (is_null($msg) || !strlen($msg->getText())) {  return false;   }
    return true;
});
// запускаем обработку
if(!empty($bot->getRawBody())){
    $bot->run();
}

echo "Twogether bot";
