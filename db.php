<?php

//require('tools.php');


function connectToDB () // конектимся к базе
{
    $link = mysqli_connect("localhost", "admin_bitrix", "Ilya_96_chazoF", "admin_bitrix");

    if (!$link) {
        writeToLog(mysqli_connect_error() . mysqli_connect_errno(), 'Ошибка соединения с БД');
        exit;
    }
    writeToLog(mysqli_get_host_info($link), 'Соединение с MySQL установлено!');
    return $link;
}

function sendReqestToDB ($queryString) //делаем запрос к базе
{
    $link = connectToDB();
    $query = mysqli_query($link, $queryString);
    $data = mysqli_fetch_assoc($query);
    writeToLog($data, "Данные из БД");
    mysqli_close($link);
    return $data;
}

