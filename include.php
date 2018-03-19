<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function connect_me(){
  
   /*
    * The following few lines connect to the mysql database
    * (or throw an error message if connection fails)
    * This is now a stand alone function called from each routine
    * which needs db access, why duplicate this in each function?
    */
    
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if($mysqli->connect_errno) {
        $error_string = "Failed to connect to database: (" . $mysqli->connect_errno . ")" . $mysqli->connect_error;
        printf("%s\n", $error_string);
    }
    
    /*
     * Return the connection if successfull
     */
    return($mysqli);
}    

function beg_end_of_week($day_of_week, $date_to_parse){

/*
 * This is the raw routine before editing, as listed it takes a date
 * and spits out the Sunday preceding the given date (or the same
 * date provided, if it is already Sunday) and the next Saturday (unless
 * the given date is a Saturday, in which case you get the preceding 
 * Sunday, with Saturday being the Saturday provided,) this is for
 * arrangement of attendance calendars and attendance reports.
 * 
 * Unfortunately this is needed because the dumbarses who wrote PHP have
 * determined that the first day of the week shall always be Monday, and
 * the calendar shall be some ISO format which makes the year have
 * 53 weeks under certain circumstances (like leap year), thus, December 31st
 * can fall as being in week 53, and January 1st may also be in week 53! - 
 * Arrogant Europeans.
 * 
 * The function takes two arguments:
 * The day of the week you wish returned (Usually Saturday or Sunday)
 * And the date to calculate from
 * 
 */    
    
//define('SEC_IN_DAY', (24*60*60));
if(!defined('SEC_IN_DAY')) define ('SEC_IN_DAY', (24*60*60));

$d = strtotime($date_to_parse);
// "w" is the day of the week. 0 for Sunday through 6 for Saturday
$delta_sun = -date('w', $d);
$delta_sat = $delta_sun + 6;

//echo 'The day ' . date('d/m/Y', $d) . "<br>\n";
//echo 'Last Sunday '. date('d/m/Y', $d + $delta_sun * SEC_IN_DAY) . "<br>\n";
//echo 'Next Saturday '. date('d/m/Y', $d + $delta_sat * SEC_IN_DAY) . "<br>\n";

$prev_sunday = date('Y-m-d', $d + $delta_sun * SEC_IN_DAY);
$next_saturday = date('Y-m-d', $d + $delta_sat * SEC_IN_DAY);

return array($prev_sunday, $next_saturday);
}

function dateRange($begin, $end, $interval = null)
{
    
/*
 * 
 * format:
 * dateRange(Starting date, ending date an interval - if desired otherwise null);
 * 
 *  returns an array of dates which must be parsed by a filter
 */
    
    $begin = new DateTime($begin);
    $end = new DateTime($end);
    // Because DatePeriod does not include the last date specified.
    $end = $end->modify('+1 day');
    $interval = new DateInterval($interval ? $interval : 'P1D');

    return iterator_to_array(new DatePeriod($begin, $interval, $end));
}

function dateFilter($daysOfTheWeek)
{
/*
 * Pass the desired days of the week in long form 
 * I.E. ['Tuesday', 'Thursday']
 */
    
    return function ($date) use ($daysOfTheWeek) {
        return in_array($date->format('l'), $daysOfTheWeek);
    };
}

function numofweeks($from_date, $to_date) {
    
/*
 * returns the number of weeks between two given dates
 */    
    
/*    if($from_date > $to_date) return numofweeks($to_date, $from_date);
    $first = DateTime::createFromFormat('m/d/Y', $from_date);
    $second = DateTime::createFromFormat('m/d/Y', $to_date);
    return floor($first->diff($second)->days/7); */
    
        $from_date = DateTime::createFromFormat('d/m/Y',$from_date);
        $to_date = DateTime::createFromFormat('d/m/Y',$to_date);
        $interval = $from_date->diff($to_date);
        $week_total = $interval->format('%a')/7;
        return floor($week_total);
}

function date_range($first, $last, $step = '+1 day', $output_format = 'd/m/Y' ) {

/*
 * This routine returns an array containg all of the
 * dates between (and including) two dates...
 */
    
    $dates = array();
    $current = strtotime($first);
    $last = strtotime($last);

    while( $current <= $last ) {

        $dates[] = date($output_format, $current);
        $current = strtotime($step, $current);
    }

    return $dates;
}

function mondays_in_range($from_date, $to_date){
    $date_from = new DateTime($from_date);
    $date_to = new DateTime($to_date);
    $dates = [];
    
    if($date_from > $date_to){
        return $dates;
    }
    
    if(1 != $date_from->format('N')){
        $date_from->modify('next monday');
    }
    
    while($date_from <= $date_to){
        $dates[] = $date_from->format('Y-m-d');
        $date_from->modify('+1 week');
    }
    
    return $dates;
}

function to_new_date($date, $in_form, $out_form, $in_delim, $out_delim){

/*------------------------------------------------------------------------------*/
/* Ok folks, I am tired of the Sh**ty stuck on the "European" date format       */
/* routine of PHP - this routine is a simple "give me the damn date in          */
/* a format *I* want" function.                                                 */
/* Output can be ymd, dmy, mdy or epoch                                         */
/* $date is the date you want parsed                                            */
/* $in_form is ymd, dmy or mdy                                                  */
/*      Null assumes form is dmy                                                */
/* $out_form is ymd, dmy, mdy or epoch                                          */
/*      Leaving as NULL will automatically output epoch                         */
/* $in_delim is what ever you have chosen as a delimiter usually '/' or '-'     */
/*          **anything other than '/', '-' or '\' will force the function to    */
/*            default to '/' as the input delimeter **                          */
/*            May be left NULL, in which case '/' is assumed.                   */
/* $out_delim can be what ever you want, it is ignored for epoch out            */
/*      Leaving null assumes '/' as delim (or nothing, for epoch)               */
/*                                                                              */
/* There probably IS an easier way of doing this, but for me, this one size     */
/* fits all routine works for now.                                              */
/*------------------------------------------------------------------------------*/
    
    /*-------------------------------*/
    /* Parse $in_delim               */
    /* if it's anything but '/', '-' */
    /* or '\' then assume '/'        */
    /*-------------------------------*/
    
    if($in_delim === "/"){
        $delim = "/";
    } elseif ($in_delim === "-") {
        $delim = "-";
    } elseif ($in_delim === "\\") {
        $delim = "\\";
    } else {
        $delim = "/";
    }
    
    if($out_delim === "/"){
        $out_del = "/";
    } elseif ($out_delim === "-") {
        $out_del = "-";
    } elseif ($out_delim === "\\") {
        $out_del = "\\";
    } else {
        $out_del = "/";
    }    
    
    /*---------------------------------------------------*/
    /* Explode the input date into an array              */
    /*---------------------------------------------------*/
    
    $temp_date = explode($delim, $date);
    
    if($in_form === "dmy"){
        if($out_form === "dmy"){
            $ret_date = $temp_date[0] . $out_del . $temp_date[1] . $out_del . $temp_date[2];
        } elseif($out_form === "mdy"){
            $ret_date = $temp_date[1] . $out_del . $temp_date[0] . $out_del . $temp_date[2];
        } elseif($out_form === "ymd"){
            $ret_date = $temp_date[2] . $out_del . $temp_date[1] . $out_del . $temp_date[0];
        } elseif($out_form === "ydm"){
            $ret_date = $temp_date[2] . $out_del . $temp_date[0] . $out_del . $temp_date[1];
        } elseif($out_form === "epoch") {
            $parse_this = $temp_date[1] . "/" . $temp_date[0] . "/" . $temp_date[2];
            $new_date = new DateTime($parse_this);
            $ret_date = $new_date->format('U');
        } else {
            $ret_date = $temp_date[0] . $out_del . $temp_date[1] . $out_del . $temp_date[2];
        }
    } elseif($in_form === "mdy"){
        if($out_form === "dmy"){
            $ret_date = $temp_date[1] . $out_del . $temp_date[0] . $out_del . $temp_date[2];
        } elseif($out_form === "mdy"){
            $ret_date = $temp_date[0] . $out_del . $temp_date[1] . $out_del . $temp_date[2];
        } elseif($out_form === "ymd"){
            $ret_date = $temp_date[2] . $out_del . $temp_date[0] . $out_del . $temp_date[1];
        } elseif($out_form === "ydm") {
            $ret_date = $temp_date[2] . $out_del . $temp_date[1] . $out_del . $temp_date[0];
        } elseif($out_form === "epoch") {
            $parse_this = $temp_date[0] . "/" . $temp_date[1] . "/" . $temp_date[2];
            $new_date = new DateTime($parse_this);
            $ret_date = $new_date->format('U');
        } else {
            $ret_date = $temp_date[1] . $out_del . $temp_date[0] . $out_del . $temp_date[2];
        }
    } elseif($in_form === "ymd"){
        if($out_form === "dmy"){
            $ret_date = $temp_date[2] . $out_del . $temp_date[1] . $out_del . $temp_date[0];
        } elseif($out_form === "mdy") {
            $ret_date = $temp_date[1] . $out_del . $temp_date[2] . $out_del . $temp_date[0];
        } elseif($out_form === "ymd") {
            $ret_date = $temp_date[0] . $out_del . $temp_date[1] . $out_del . $temp_date[2];
        } elseif($out_form === "ydm"){
            $ret_date = $temp_date[0] . $out_del . $temp_date[2] . $out_del . $temp_date[1];
        } elseif($out_form === "epoch"){
            $parse_this = $temp_date[1] . "/" . $temp_date[2] . "/" . $temp_date[0];
            $new_date = new DateTime($parse_this);
            $ret_date = $new_date->format('U');
        }
    }
    
    return($ret_date);
}

?>