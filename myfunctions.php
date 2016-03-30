<?php
function rdebug( $str = array() ) {
    echo '<pre>';
    print_r($str);
    echo '</pre>';
}

function xdebug( $str = null ) {
    echo '<pre>';
    var_dump($str);
    echo '</pre>';
}
