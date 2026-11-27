<?php

    include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");
    // 2020 added by Carlos

    function avoid_empty_arr($items){
        if($items) {
            return $items;
        }
        return [];
    }

    function console_log( $data ) {
        echo '<script>';
        echo 'console.log('. json_encode( $data, JSON_THROW_ON_ERROR ) .')';
        echo '</script>';
    }

    function handle_value($value){
        if (is_string($value)) {
          $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        } else if (is_array($value)) {
          $clean_array = [];
          foreach ($value as $child) {
            $clean_array []= handle_value($child);
          }
          return $clean_array;
        }
        return $value;
    }

    function _get_function($para_name, $default_value = '') {
        $value = $_GET[$para_name] ?? $default_value;
        $value = handle_value($value);
        return $value;
    }

    function _post_function($para_name, $default_value = '') {
        $value = $_POST[$para_name] ?? $default_value;
        $value = handle_value($value);
        return $value;
    }

    function _request_function($para_name, $default_value = '') {
        $value = $_REQUEST[$para_name] ?? $default_value;
        $value = handle_value($value);
        return $value;
    }

    function _get_if_not_set($para, $para_name, $default_value = ''){
        if($para) {
            return $para;
        }
        return _get_function($para_name, $default_value);
    }

    function _request_if_not_set($para, $para_name, $default_value = ''){
        if($para) {
            return $para;
        }
        return _request_function($para_name, $default_value);
    }

