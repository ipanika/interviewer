<?php

/*
 * Создать пустой массив для хранения констант в виде пары ключ-значение
 */
$C = array();

/*
 * URL-адрес хоста базы данных
 */
$C['DB_HOST'] = 'localhost';

/*
 * Имя пользователя базы данных
 */
$C['DB_USER'] = 'root';

/*
 * Пароль доступа к базе данных
 */
$C['DB_PASS'] = '';

/*
 * Имя открываемой базы данных
 */
$C['DB_NAME'] = 'interviewer';

/*
 * Вид опроса (0 - метод треугольника, 1 - профильный метод
 * 2 - метод комплексной оценки 3 - потребительское тестирование
 */
$C['M_TRIANG'] = 0;
$C['M_PROFIL'] = 1;
$C['M_COMPLX'] = 2;
$C['M_CONSUM'] = 3;

/*
 * Определяет вид вопроса 0 - закрытый, 1 - открытый, 2 - вопрос для метода треугольника
 */
$C['Q_CLOSE'] = 0;
$C['Q_OPEN'] = 1;
$C['Q_TRIANG'] = 2;

/*
 * Определяет количество вариантов ответов на вопрос в профильном и комплексном
 * опросе
 */
$C['NUM_OF_OPTIONS'] = 7;

?>