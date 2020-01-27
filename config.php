<?php
$config['db']['host'] = 'localhost';
$config['db']['user'] = 'root';
$config['db']['password'] = '';
$config['db']['dbname'] = 'otelseasy_live';


$config['secret'] = 'subinrabin';


$config['memcached']['host'] = '15.206.192.189';
$config['memcached']['port'] = 22122;


$config['log']['path'] = __DIR__ . '/apilogs/app-'.(date('Y-m-d')).'.log';

$config['Auth-link'] = 'test_webapi.otelseasy.com';
$config['HotelSearch-link'] = 'test_webapi.hotelsearch.otelseasy.com';
$config['AvailableHotelRooms-link'] = 'test_webapi.availablehotelrooms.otelseasy.com';
$config['HotelDetails-link'] = 'test_webapi.hoteldetails.otelseasy.com';
$config['BookingReview-link'] = 'test_webapi.bookingreview.otelseasy.com';
$config['HotelBook-link'] = 'test_webapi.hotelbook.otelseasy.com';
$config['BookingDetail-link'] = 'test_webapi.bookingdetails.otelseasy.com';
$config['BookingCancel-link'] = 'test_webapi.bookingcancel.otelseasy.com';
$config['CancelStatus-link'] = '"test_webapi.cancelstatus.otelseasy.com"';



return $config;
