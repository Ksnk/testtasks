<?php
/**
 * Created by PhpStorm.
 * User: Ksnk
 * Date: 24.05.2018
 * Time: 22:00
 */

include 'iterator2gb.php';

try {

    $it = new iterator2gb('text.txt');

    echo $it->current(), "\n";
    foreach(
        [
            1,2,
            470,471,472, // граница чтения 1 буфера
            25256207,25256208,// финальные строки
            939,940,941,// граница чтения 2-го буфера
            25256209,25256210,// последняя и несуществующая строки
        ]
        as $tell
    ){
        echo 'pos:'.$tell.': ';
        $it->seek($tell);
        echo $it->current(), "\n";
    }

} catch (OutOfBoundsException $e) {
    echo $e->getMessage();
}

//print_r($it->stat);