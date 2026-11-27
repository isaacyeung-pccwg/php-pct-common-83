<?php
global $insight_lib_path, $intranet_naming;
include_once($insight_lib_path . "/log4php/apache-log4php-2.3.0/src/main/php/Logger.php");
$default_log_level = str_contains((string) $intranet_naming, "SIT") ? 'DEBUG' : 'INFO';
define('LOG_DEFAULT_LEVEL', $default_log_level); // define application log level
define('LOG_SQL_LEVEL', $default_log_level); // define sql log level

$config_log4php = [];

$config_log4php["loggers"]["cir-log"]["appenders"] = ['cir-log'];
$config_log4php["loggers"]["cir-log"]["level"] = LOG_DEFAULT_LEVEL;
$config_log4php["appenders"]["cir-log"]["class"] = "LoggerAppenderConsole";
$config_log4php["appenders"]["cir-log"]["layout"]["class"] = "LoggerLayoutPattern";
$config_log4php["appenders"]["cir-log"]["layout"]["params"]["conversionPattern"] = "%d [%t] %-5p %c - %F:%L - %m%n"; // log pattern
$config_log4php["loggers"]["cir-sql-log"]["appenders"] = ['cir-sql-log'];
$config_log4php["loggers"]["cir-sql-log"]["level"] = LOG_SQL_LEVEL;
$config_log4php["appenders"]["cir-sql-log"]["class"] = "LoggerAppenderConsole";
$config_log4php["appenders"]["cir-sql-log"]["layout"]["class"] = "LoggerLayoutPattern";
$config_log4php["appenders"]["cir-sql-log"]["layout"]["params"]["conversionPattern"] = "%d [%t] %-5p %c - %F:%L - %m%n";
$config_log4php["loggers"]["base-log"]["appenders"] = ['base-log']; // base-log” will be the logger name appeared
$config_log4php["loggers"]["base-log"]["level"] = LOG_DEFAULT_LEVEL;
$config_log4php["appenders"]["base-log"]["class"] = "LoggerAppenderConsole";
$config_log4php["appenders"]["base-log"]["layout"]["class"] = "LoggerLayoutPattern";
$config_log4php["appenders"]["base-log"]["layout"]["params"]["conversionPattern"] = "%d [%t] %-5p %c - %F:%L - %m%n"; // log

Logger::configure($config_log4php);

$logger = Logger::getLogger('cir-log'); // declare default logger as $logger
$sql_logger = Logger::getLogger('cir-sql-log'); // declare sql logger as $sql_logger