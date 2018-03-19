<!DOCTYPE html>
<?php
/*
 * Attendance Recording
 * 
 * OSATT - Open Source Attendance
 * 
 * login_succeeded.php
 * Copyright (C) 2002, 2003, 2016 Ken Plumbly <frotusroom@gmail.com>
 *
 * login_succeeded.php is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * login_succeeded.php is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along
 * with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *------------------------------------------------------------------------------------------------
 * And now, for something - completely different:
 *------------------------------------------------------------------------------------------------
 * Dennis:      Come and see the violence inherent in the system. Help! Help! I'm being repressed!
 * King Arthur: Bloody peasant!
 * Dennis:      Oh, what a giveaway! Did you hear that? 
 *              Did you hear that, eh? That's what I'm on about! 
 *              Did you see him repressing me? You saw him, didn't you?
 *-----------------------------------------------------------------------------------------------
 *
 *
 * Version .01 alpha, October 2002 - first version of this project, as such, it
 * will probably have some security holes, it's inevitable, the
 * other thing which will be annoying is code repetition,
 * task one is just to get this working, task two: secure the holes
 * and reduce redundant code.
 * --------------
 * 
 * Version 1.0 December 2002 - code cleanup.
 *
 * --------------
 * 
 * Version 2 September 2003 - Just some basic attendance records
 * for program participants.
 *
 * --------------
 * 
 * Version 2.01 January 2003 - basic logins, basic menuing improvements
 * basic security improvements.
 *
 * --------------
 * 
 * Resurecting a very old project. Asked if I had anything
 * like an "attendance reporting script" I dug this dinosaur out. 
 * Version 2.05 April 2016 - major upgrades to suit modern 
 * PHP libraries
 *  
 * Almost all subroutines are now included within
 * this file, the exception: login is via index.php which is external,
 * there is also now a separate configuration file and the
 * .css file (which is a new addition since version 2.01)
 * There is also the calendar.php routine for inputting
 * program run dates.
 * 
 * OK - this is unfortunate, but the only known copies of
 * the original code disks were damaged, so can no longer be read....
 * however, there were some faded dot matrix printouts. :)
 * 
 * - Reduced a bunch of redundant/repetetive code
 * - Depricated Javascript in favour of using something easier:
 *   a switch statement in a div tag.
 * - mysql functions depricated in favour of mysqli. 
 * - Haven't touched this code in years, time to
 * - improve security, including random salts for passwords, use of
 *   asymetric encryption for some data for security sake.
 * - Change from GPL v2 to GPL v3
 * - Use of blowfish for password hash
 * - Random salt for password hash
 * - begin processes for reducing liklihood of injections.
 * 
 * November 2/2016 - modified the attendance reporting input system to show
 *      a student as w/d if they are withdrawn, this is in place of the select and
 *      option fields so that there is no way to further enter data for a student.
 * November 4/2016 - further modified addendance recording to not show a withdrawn
 *      student at all - their records still exist to that point, but they are
 *      simply not shown, this makes it less confusing for instructors and others
 *      who may record attendance records.
 * November 8/2016 - further modified attendance recording routines so that selecting
 *      a week now no longer requires a click on a change week button, instead,
 *      the field is automatically submitted when the date is clicked on. I have
 *      used a javascript "onchange" field for this.
 * December 1/2016 - inclusion of kludge in report by course which allows reporting
 *      for the week of November 6th - for some reason, the dates are calculated
 *      backwords, which crashes the script.
 * December 7/2016 - addition of function to_new_date() routine, this function returns the
 *      the date in a format requested and doesn't require all kinds of bodges to
 *      get the date in a desired format.
 * December 29/2016 - addition of "report by sponsor" item in the reports menu;
 *      this allows a selective report including only those students of an
 *      individual sponsor.
 * March 2017 - begin ripping out beg_end_of_week, it is buggy and unpredicatble.
 * August 2017 - Begin re - write of archiving facilities.
 *      What is this? The ability to archive a program, then, if desired, purge the data
 *      leaving just a pair of archive files, one is .csv format, the other and xls 
 *      spread sheet. This allows for later review of the data while not clogging up the 
 *      database.
 *-------------------------------------------------------  
 * 
 * Sir Bedevere: What makes you think she's a witch?
 * Peasant 3: Well, she turned me into a newt!
 * Sir Bedevere: A newt?
 * Peasant 3: ... I got better.
 * Crowd: Burn her anyway!
 * 
 *
 *
 * -------------------- Begin Actual Code ---------------------------
 *
 * Check if the session has been registered, if it has,
 * carry on to the rest of the routine, in other words,
 * the login was successful, if not, redirect back to the login
 * page.
 */

session_start();
if(!isset($_SESSION['username'])){

header("location:index.php");
}

/*
 * Clear the password out of the _SESSION array
 * else a clever lad or lass may just be able to grab
 * a password or two.
 */

$_SESSION['password'] = "";

// Expire the session if user is inactive for 30
// minutes or more. Move into settings, there should be NO EDITING
// of any settings going on in here at all!!

$expireAfter = 30;
 
// Check to see if our "last action" session
// variable has been set.
if(isset($_SESSION['last_action'])){
    
    //Figure out how many seconds have passed
    //since the user was last active.
    $secondsInactive = time() - $_SESSION['last_action'];
    
    //Convert our minutes into seconds.
    $expireAfterSeconds = $expireAfter * 60;
    
    //Check to see if they have been inactive for too long.
    if($secondsInactive >= $expireAfterSeconds){
        //User has been inactive for too long.
        //Kill their session.
        session_unset();
        session_destroy();
    }
    
}
 
// Assign the current timestamp as the user's
// latest activity
$_SESSION['last_action'] = time();

/*
 *  include the config file
 */

include 'nextdex.php';

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

function beg_end_of_week($day_of_week, $date_to_parse){

/*
 * *** NOTE ****
 * This function is on its way out - it is buggy and can be replaced
 * by one simple line of code (usually) where it is called.
 * But now to do just that.
 *******************************************************************************
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

function date_range($first, $last, $step = '+1 day', $output_format = 'Y/m/d' ) {

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

function dateFilter(array $daysOfTheWeek)
{
/*
 * Pass the desired days of the week in long form 
 * I.E. ['Tuesday', 'Thursday']
 */
    
    return function ($date) use ($daysOfTheWeek) {
        return in_array($date->format('l'), $daysOfTheWeek);
    };
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
/* There probably IS and easier way of doing this, but for me, this one size    */
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

/*------------------------------------------------------------------------------*/
/* let's feed the start/end/dow to this and spit up the exact number of the     */
/* desired days there is between the two dates.                                 */
/* use the form of YYYY-MM-DD for both start and end                            */
/* desired day is going to be 1 - 7 where Monday = 1 and Sunday = 7             */
/* example:                                                                     */
/* getNumbOfDays("2017-01-01","2017-12-31",7)                                   */
/* Give us a count of every Sunday (7) between January 1st 2017 and             */
/* December 31st 2017                                                           */
/*------------------------------------------------------------------------------*/
function getNumOfDays($start, $end, $day){
$no = 0;
$from = new DateTime($start);
$to   = new DateTime($end);
$interval = DateInterval::createFromDateString('1 day');
$period = new DatePeriod($from, $interval, $to);
foreach ($period as $dt)
{
    if ($dt->format('N') == $day)
    {
        $no++;
    }
}
return $no;
}

function admin_menu(){
/*
 * Generate administrative menus for logged in
 * system administrators
 */    

    printf("Administrative User\n<br>\n<br>\n");    
    printf("            <div class=\"dropdown\">\n");
        printf("            <button class=\"dropbtn\">Users</button>\n");
        printf("            <div class=\"dropdown-content\">\n");
           
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=adduser\">Add User</a>\n");
            printf("            <br>\n            <br>\n");
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=listusers\">List Users</a>\n");
              
printf("        </div>\n");
printf("    </div>\n");
printf("    <div class=\"dropdown\">\n");
printf("        <button class=\"dropbtn\">Sponsors</button>\n");
printf("        <div class=\"dropdown-content\">\n");

            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=addspons\">Add Sponsor</a>\n");
            
printf("            <br><br>\n");
            
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=listspons\">list Sponsors</a>\n");
            
printf("        </div>\n");
printf("    </div>\n");
printf("    <div class=\"dropdown\">\n");
printf("        <button class=\"dropbtn\">Classes</button>\n");
printf("        <div class=\"dropdown-content\">\n");
            
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=term\">Terms</a>\n");
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=programs\">Programs</a>\n");
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=addprog\">Add Class</a>\n");
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=listprogs\">List Classes</a>\n");
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=enterattendad\">Enter Attendance</a>\n"); 
                         
printf("        </div>\n");
printf("    </div>\n");
printf("        <div class=\"dropdown\">\n");
printf("        <button class=\"dropbtn\">Students</button>\n");
printf("        <div class=\"dropdown-content\">\n");
            
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=addstudent\">Add Student</a>\n");
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=liststu\">List Students</a>\n");
            
printf("        </div>\n");
printf("        </div>\n");
printf("    <div class=\"dropdown\">\n");
printf("        <button class=\"dropbtn\">Reports</button>\n");
printf("        <div class=\"dropdown-content\">\n");
           
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=genreports\">By Course</a>\n");
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=repbyspons\">By Sponsor</a>\n");
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=repbystude\">By Student</a>\n");
            
printf("        </div>\n");
printf("    </div>\n");
printf("    <div class=\"dropdown\">\n");
printf("        <button class=\"dropbtn\">Utilities</button>\n");
printf("        <div class=\"dropdown-content\">\n");
           
            printf("View Archived Classes\n");
            
printf("        </div>\n");
printf("    </div>\n");
printf("    &nbsp; &nbsp;\n");
printf("            <a href=\"");
printf(htmlspecialchars($_SERVER['PHP_SELF']));
printf("?routine=chsettings\" style=\"text-decoration: none;\"><img src=\"images/Gear_Plain_Blue.png\" alt=\"Settings\">&nbsp; Settings</a>\n");
           
}

function pa_menus(){
    printf("Program Assistant\n<br>\n<br>\n");    

printf("    <div class=\"dropdown\">\n");
printf("        <button class=\"dropbtn\">Classess</button>\n");
printf("        <div class=\"dropdown-content\">\n");
            
            printf("<a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=addprog\">Add Class</a>\n");
            printf("            <br>\n            <br>\n");
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=listprogs\">List Classs</a>");
                         
printf("        </div>\n");
printf("    </div>\n");
printf("        <div class=\"dropdown\">\n");
printf("        <button class=\"dropbtn\">Students</button>\n");
printf("        <div class=\"dropdown-content\">\n");
            
            printf("<a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=addstudent\">Add Student</a>\n");
            printf("            <br>\n            <br>\n");
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=liststu\">List Students</a>");
                         
printf("        </div>\n");
printf("        </div>\n");
printf("    <div class=\"dropdown\">\n");
printf("        <button class=\"dropbtn\">Reports</button>\n");
printf("        <div class=\"dropdown-content\">\n");
            
            printf("<a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=genreports\">By Course</a>\n");
            
printf("        </div>\n");
printf("    </div>\n");   
}

function instruct_menus(){
    printf("Instructor\n<br>\n<br>\n");  

printf("    <div class=\"dropdown\">\n");
printf("        <button class=\"dropbtn\">Classes</button>\n");
printf("        <div class=\"dropdown-content\">\n");
            printf("            <a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=enterattendad\">Enter Attendance</a>\n"); 
                         
printf("        </div>\n");
printf("    </div>\n");
printf("    <div class=\"dropdown\">\n");
printf("        <button class=\"dropbtn\">Reports</button>\n");
printf("        <div class=\"dropdown-content\">\n");
            
            printf("<a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=genreports\">By Course</a>\n");
            
printf("        </div>\n");
printf("    </div>\n");      
}
        
function add_new_users_form(){
    printf("<b>Add a New User</b>\n<br>\n<br>\n");
printf("    <form name=\"adduser\" method=\"post\" action=\"");
printf(htmlspecialchars($_SERVER['PHP_SELF']));
printf("\">\n");
printf("        First Name: &nbsp; <input type=\"text\" name=\"firstname\" />\n");
printf("        Last Name: <input type=\"text\" name=\"lastname\" />\n<br>\n<br>\n");
printf("        Login Name: <input type=\"text\" name=\"logname\" /> (Must be Unique from other users)\n<br>\n<br>\n");
printf("            <input type=\"radio\" name=\"privileges\" value=\"1\"> Administrator &nbsp; \n");
printf("            <input type=\"radio\" name=\"privileges\" value=\"2\"> P.A. &nbsp; \n");
printf("            <input type=\"radio\" name=\"privileges\" value=\"3\"> Instructor &nbsp; \n");
printf("            Password: <input type=\"password\" name=\"password\" />\n<br>\n<br>\n");
printf("            <input type=\"hidden\" name=\"routine\" value=\"new-user-submit\">\n");
printf("        <input type=\"submit\" name=\"new-user-submit\" value=\"Add User\" />\n");
printf("    </form>\n");
}

function adduser(){

$mysqli = connect_me();
    
 /* 
 * create the sql query
  * first step: convert the $_POST to appropriate strings
 */
    
    $logname=$_POST['logname'];
    $firstname=$_POST['firstname'];
    $lastname=$_POST['lastname'];
    $privileges=$_POST['privileges'];
    $password=$_POST['password'];
    
    
    $sqla = "SELECT * FROM U_ops WHERE log_name = '" . $logname . "'";
    $resultsa = $mysqli->query($sqla);
    $rowsa = $resultsa->fetch_assoc();
    
    if(mysqli_num_rows($resultsa) === 0){
    
/*
 * prep to hash the new password
 * This uses blowfish
 */
    
    $cost = 10;
    $salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
    $salt = sprintf("$2a$%02d$", $cost) . $salt;
    
/*
 * Hash the password
 */
    
    $hash = crypt($password, $salt);
    
/*
 * Create the prepared sql statement, bind it.
 */
    
    $sqlb = $mysqli->prepare("INSERT INTO U_ops (log_name, priv_level, U_F_name, U_L_name, U_pass) VALUES (?, ?, ?, ?, ?)");
    $sqlb->bind_param("sssss", $logname, $privileges, $firstname, $lastname, $hash);
    
/*
 * Execute the sql statement.
 */
    
       if($sqlb->execute()){
           printf("     User <b>%s</b> Successfully added.<br>\n", $logname);
       }
       else {
           printf("     <b>Some kind of error occurred (Helpful message, ain it?)</b>\n");
       }
    } else {
        
       printf("<br>\nUser Login Name: %s<br>Already Exists! Please try again.<br>\nClick below or wait 10 seconds<br>\n<hr>\n", $logname);
       printf("<a href=\"");
       printf(htmlspecialchars($_SERVER['PHP_SELF']));
       printf("?routine=adduser\">Back To Adduser</a>\n");
    }   
/*
 * Close the sql statement, close the database connection, unset post data,
 * redirect back to this page.
 */
       $resultsa->free();
       mysqli_close($mysqli);
       unset($_POST);
       header('Refresh: 10;url=login_succeeded.php?routine=listusers');
}

function list_users(){
 
 $mysqli = connect_me(); 
/* 
 * create the sql query which retrieves all of the data for all of the system users
 */
    
    $sql = <<<SQL
        SELECT *
        FROM U_ops
SQL;

    /*
    * Pass the query to mysql and retrieve the requested row.
    */    
    
    $results = mysqli_query($mysqli, $sql);
    
    /* Display system users in a table */
    
    printf("<b>System Users</b>\n<br>\n<br>\n");
    
    printf("<table class=\"standard\" cellpadding=\"2\" border=\"0\">\n");
    printf("    <tr>\n");
    printf("        <th class=\"th\">User Name</th>\n");
    printf("        <th>First Name</th>\n");
    printf("        <th>Last Name</th>\n");
    printf("        <th>Privileges</th>\n");
    printf("    </tr>\n");
    
    if($results->num_rows > 0) {
        while($rows = mysqli_fetch_array($results)) {
            printf("        <tr>\n");
            printf("        <td>%s</td>\n", $rows["log_name"]);
            printf("        <td>%s</td>\n", $rows["U_F_name"]);
            printf("        <td>%s</td>\n", $rows["U_L_name"]);
            
            /*
             * Strictly speaking; the following is unnessesary; since these
             * are only for administrators anyway.
             */
                switch($rows["priv_level"]){
                    case 1:
                        $privs = "Administrator";
                        break;
                    case 2:
                        $privs = "PA";
                        break;
                    case 3:
                        $privs = "Instructor";
                        break;
                }
            printf("        <td>%s</td>\n", $privs);
            
            /*
             * If this is user #1 make sure the record cannot be deleted
             * as user #1 is a superuser you do not want to lose!
             */
            if($rows["U_index"] == "1"){
            printf("        <td>Permanant Record</td>\n");
            }
                else {
                    printf("        <form name=\"deluser\" method=\"post\" action=\"");
                    printf(htmlspecialchars($_SERVER['PHP_SELF']));
                    printf("\">\n");
                    printf("        <input type=\"hidden\" name=\"userid\" value=\"%s\">\n", $rows['U_index']);
                    printf("        <input type=\"hidden\" name=\"routine\" value=\"deluser\">\n");
                    printf("        <td>&nbsp;<input type=\"submit\" name=\"del_user\" value=\"Delete User\"></td>\n");
                    printf("        </form>\n");
                    printf("        <form name=\"chmypass\" method=\"post\" action=\"");
                    printf(htmlspecialchars($_SERVER['PHP_SELF']));
                    printf("\">\n");                    
                    printf("        <input type=\"hidden\" name=\"userid\" value=\"%s\">\n", $rows['U_index']);
                    printf("        <input type=\"hidden\" name=\"routine\" value=\"edituser\">\n");
                    printf("        <td><input type=\"submit\" name=\"adchpass\" value=\"Edit User\"></form></td>\n");
               }
            printf("        </tr>\n");
        }
    }
    printf("     </table>\n");
}

function del_user(){
/*
 * Connect to database
 */
    
 $mysqli = connect_me();
    
$userid = $_POST['userid'];

//echo "Userid = " . $userid;

$sql=$mysqli->prepare("DELETE FROM U_ops WHERE U_index = ?");
$sql->bind_param("d", $userid);

/*
 * Execute the sql statement.
 */
    
       if($sql->execute()){
           printf("     <b>User Deleted</b>\n");
       }
       else {
           printf("     <b>Some kind of error occurred.</b>\n");
       }

/*
 * Close the sql statement, close the database connection, unset post data,
 * redirect back to this page.
 */
    
       mysqli_close($mysqli);
       unset($_POST);
       header('Refresh: 2;url=login_succeeded.php?routine=listusers');
       exit();
}

function edit_user_form(){
/*
 * Connect to database
 */
    
    $mysqli = connect_me();
    
    $userid = $_POST['userid'];
    
 /* 
 * create the sql query
 */
    
    $sql = <<<SQL
        SELECT *
        FROM U_ops
        WHERE U_index = '$userid'
SQL;
    
    $result = mysqli_query($mysqli, $sql);
    $row = mysqli_fetch_array($result);

    printf("        <b>Edit User:</b> %s\n<br>\n<br>\n", $row['log_name']);
    printf("        <table border=\"0\">\n");
    
    printf("        <form name=\"f1\" method=\"post\" action=\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");
    printf("        <input type=\"hidden\" name=\"routine\" value=\"updateuser\">\n");
    printf("            <tr>\n");
    printf("                <td>\n");
    printf("                    First Name: &nbsp; <input type=\"text\" name=\"firstname\" value=\"%s\">\n", $row['U_F_name']);
    printf("                </td>\n");
    printf("                <td>\n");
    printf("                    Last Name: &nbsp; <input type=\"text\" name=\"lastname\" value=\"%s\">\n", $row['U_L_name']);
    printf("                </td>\n");
    printf("                </tr>\n");
    printf("            <tr>\n");
    printf("                <td>\n");
    printf("                <br>\n");
    printf("                    Login Name: <input type=\"text\" name=\"logname\" value=\"%s\">\n", $row['log_name']);
    printf("                </td>\n");
    printf("            </tr>\n");
    printf("            <tr>\n");
    printf("                <td>\n");
    printf("                <br>\n");
    printf("                    <input type=\"radio\" name=\"privileges\" value=\"1\"");
    if($row['priv_level'] == 1){
    printf(" checked> Administrator &nbsp;\n");
    } else {
        printf("> Administrator &nbsp;\n");
    }
    printf("                    <input type=\"radio\" name=\"privileges\" value=\"2\"");
    if($row['priv_level'] == 2){
    printf(" checked> P.A. &nbsp;\n");
    } else {
        printf("> P.A. &nbsp;\n");
    }
    printf("                    <input type=\"radio\" name=\"privileges\" value=\"3\"");
    if($row['priv_level'] == 3){
    printf(" checked> Instructor &nbsp;\n");
    } else {
        printf("> Instructor &nbsp;\n");
    }
    printf("                    <input type=\"hidden\" name=\"userid\" value=\"%s\">\n", $userid);
    printf("                    <input type=\"submit\" name=\"updateuser\" value=\"Update User\">\n");
    printf("                </td>\n");
    printf("            </tr>\n");
    printf("            </form>\n");
    printf("            <tr>\n");
    printf("                <td>\n");
    printf("                <br>\n");
    printf("                    <form name=\"chmypass\" method=\"post\" action=\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");                    
    printf("                    <input type=\"hidden\" name=\"userid\" value=\"%s\">\n", $userid);
    printf("                    <input type=\"hidden\" name=\"routine\" value=\"chpass\">\n");
    printf("                    <input type=\"submit\" name=\"adchpass\" value=\"Change Password\">\n");
    printf("                    </form>\n");
    printf("                </td>\n");
    printf("            </tr>\n");
    printf("        </table>\n");
}

function updateuser(){
    /*
     * Connect to database
     */
    
    $mysqli = connect_me();
    
    $userid = $_POST['userid']; 
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $logname = $_POST['logname'];
    $privileges = $_POST['privileges'];
    
    $sql = $mysqli->prepare("UPDATE U_ops SET log_name=?, U_F_name=?, U_L_name=?, priv_level=? WHERE U_index=?");
    $sql->bind_param("sssss", $logname, $firstname, $lastname, $privileges, $userid);
    
/*
 * Execute the sql statement.
 */
    
       if($sql->execute()){
           printf("         User <b>%s %s </b> Successfully updated.\n", $firstname, $lastname);
       }
       else {
           printf("         <b>Error! User %s %s Not updated</b>\n", $firstname, $lastname);
       }
       
       unset($_POST);
       mysqli_affected_rows($mysqli);
       header('Refresh: 5;url=login_succeeded.php?routine=listusers');
}

function add_new_student_form(){
    
    
/*
 * Connect to database
 */

$mysqli = connect_me();

/*
 * Create prepared sql statement
 */
    
    $sql = "SELECT * FROM sponsor";
    $sqla = "SELECT * FROM program_codes";

    /*
    * Pass the query to mysql and retrieve the requested row.
    */    
    
$result = $mysqli->query($sql);
$resulta = $mysqli->query($sqla);

$todays_date_day = date("d");
$todays_date_month = date("m");
$todays_date_year = date("Y");

printf("<b>Add a New Student</b>\n<br>\n<br>\n");

if($result->num_rows > 0) {

printf("            <form name=\"newstudent\" method=\"post\" action=\"");
printf(htmlspecialchars($_SERVER['PHP_SELF']));
printf("\">\n");
printf("            <input type=\"hidden\" name=\"routine\" value=\"add_student\">\n");
printf("            First Name: &nbsp;<input type=\"text\" name=\"firstname\" />\n");
printf("            &nbsp; Middle Name/Initial (may leave blank): &nbsp; <input type=\"text\" name=\"middlename\" />\n<br>\n<br>\n");
printf("            Last Name: &nbsp;<input type=\"text\" name=\"lastname\" />\n");
printf("            &nbsp; Enrollment day: <input type=\"text\" name=\"enrolledday\" size=\"2\" value=\"%s\">\n", $todays_date_day);
printf("            &nbsp; Enrollment Month: <input type=\"text\" name=\"enrolledmonth\" size=\"2\" value=\"%s\">\n", $todays_date_month);
printf("            &nbsp; Enrollment Year: <input type=\"text\" name=\"enrolledyear\" size=\"4\" value=\"%s\">\n<br>\n<br>\n", $todays_date_year);
printf("            Sponsor: &nbsp;<select name=\"sponsorcode\">\n");
while($row = $result->fetch_assoc())
{
    printf("        <option value=\"%s\">%s</option>\n", $row["sponsor_index"], $row["sponsor"]);
}
printf("        </select>\n");
printf("            Program: &nbsp;<select name=\"program_code\">\n");
while($rowsa = $resulta->fetch_assoc()){
    printf("        <option value=\"%s\"> %s</option>\n", $rowsa['group_index'], $rowsa['group_name']);
}
printf("        </select>\n         <br>\n      <br>\n");
printf("            <input type=\"submit\" name=\"new-student-submit\" value=\"Add Student\">\n<br>\n<br>\n");
printf("            </form>\n");
}
 else {
     printf("<b>No Sponsors Yet!</b>\n");
}
mysqli_close($mysqli);
}

function add_new_student(){

$first_name = $_POST['firstname'];
$middle_name = $_POST['middlename'];
$last_name = $_POST['lastname'];
$sponsor_code = $_POST['sponsorcode'];
$enrollment_date = $_POST['enrolledday'] . "/" . $_POST['enrolledmonth'] . "/" . $_POST['enrolledyear'];
$program_code = $_POST['program_code'];

$enrolled_epoch = to_new_date($enrollment_date, "dmy", "epoch", "/", "");

/*
 * Connect to database
 */

$mysqli = connect_me();

$sql = $mysqli->prepare("INSERT INTO student (Stu_F_name, Stu_M_name, Stu_L_name, sponsor, enrollment_date, enrolled_epoch, finished_epoch, program_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$sql->bind_param("ssssssss", $first_name, $middle_name, $last_name, $sponsor_code, $enrollment_date, $enrolled_epoch, $enrolled_epoch, $program_code);

//$result = $mysqli->query($sql);

/*
 * Execute the sql statement.
 */
    
       if($sql->execute()){
           printf("     Student: <b>%s %s</b> successfully added<br>\n", $first_name, $last_name);
       }
       else {
           printf("     Student: <b>%s %s</b> not added<br>\nError: %s<br>\n", $first_name, $last_name, mysqli_error($mysqli));
       }
       mysqli_close($mysqli);
       unset($_POST);
       header('Refresh: 1;url=login_succeeded.php?routine=liststu');
}

function list_students(){

/*
 * Connect to database
 */

$mysqli = connect_me();

$sql = "SELECT * FROM student ORDER BY sponsor";

$result = $mysqli->query($sql);

printf("<b>List of Students</b>\n<br>\n<br>\n");

if($result->num_rows > 0){
    printf("            <table class=\"standard\" cellpadding=\"8\" border=\"0\">\n");
    printf("            <tr>\n");
    printf("            <th>Student Name</th>\n<th>Sponsor</th>\n      </tr>\n");
while($row = $result->fetch_assoc()){
    printf("        <tr>\n");
    $sqli = "SELECT sponsor FROM sponsor WHERE sponsor_index = " . $row['sponsor'];
    $results = $mysqli->query($sqli);
    $rows = $results->fetch_assoc();
    if($row['Stu_M_name'] == NULL){
        // printf("        <td><a href=\"#eduser?stuindex=%s\" onclick=\"showPane('eduser')\">%s %s</a></td>\n<td>%s</td>\n<td>\n", $row['stu_index'], $row['Stu_F_name'], $row['Stu_L_name'], $rows['sponsor']);
            printf("            <td>%s %s</td>\n            <td>%s</td>\n", $row['Stu_F_name'], $row['Stu_L_name'], $rows['sponsor']);
            printf("            <form name=\"editstudent\" method=\"post\" action=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("\">\n");
            printf("            <td>\n            <input type=\"hidden\" name=\"stuindex\" value=\"%s\">\n", $row['stu_index']);
            printf("            <input type=\"hidden\" name=\"routine\" value=\"edit_student\">\n");
            printf("            <input type=\"submit\" name=\"edit_student\" value=\"Edit\">\n          </form>\n</td>\n");
        }
    else{
            printf("            <td>%s %s %s</td>\n            <td>%s</td>\n", $row['Stu_F_name'], $row['Stu_M_name'], $row['Stu_L_name'], $rows['sponsor']);
            printf("            <form name=\"editstudent\" method=\"post\" action=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("\">\n");
            printf("            <td>\n            <input type=\"hidden\" name=\"stuindex\" value=\"%s\">\n", $row['stu_index']);
            printf("            <input type=\"hidden\" name=\"routine\" value=\"edit_student\">\n");
            printf("            <input type=\"submit\" name=\"edit_student\" value=\"Edit\">\n          </form>         </td>\n");        
    }
}
printf("        </table>\n");
}   
else{
    printf("<b>No students yet to list.</b>\n");
}
mysqli_close($mysqli);
}

function edit_student_form(){
/*
 * Connect to database
 */

$mysqli = connect_me();
$stuindex = $_POST['stuindex'];

/*
 * Create sql statements
 * (Yeah, I know, I should be joining, but, I
 * am somewhat lazy here, play with me on this, 'k?
 */

$sql = "SELECT * FROM student WHERE stu_index = " . $stuindex . "";

$sqli = "SELECT * FROM classes";

$sqlj = "SELECT * FROM sponsor";

$sqlk = "SELECT * FROM enrolled WHERE stu_index = " . $stuindex . "";

$sqll = "SELECT * FROM program_codes";
$resultl = $mysqli->query($sqll);
    
$result = $mysqli->query($sql);
$row = $result->fetch_assoc();

$resulti = $mysqli->query($sqli);

$resultk = $mysqli->query($sqlk);
$rowk = $resultk->fetch_assoc();

if($resulti->num_rows > 0){
        if($resultk->num_rows == 0){
            while($rowsi = mysqli_fetch_array($resulti)){
                $program_index = $rowsi['prog_index']; // These three rows are the dumbest... you have to create
                $ne = "3";                             // a variable to store the data in, you can't just put the data right into
                $dumb_arse = 0;                        // the bind statement or it is considered "passing by reference" which php doesn't allow here. :/
                $sqll = $mysqli->prepare("INSERT INTO enrolled (prog_index, stu_index, status, program_group) values (?, ?, ?, ?)");
                $sqll->bind_param("sssd", $program_index, $stuindex, $ne, $dumb_arse);
                // need some error handling here in case the execute fails for some reason!!!
                $sqll->execute();
            }
            
        }
        /*
         * This next line is a bit of a kluge, it's not the best coding practice, (or so
         * I'm told,) but this resets the query back to the beginning of the query execution
         * in order that the fetch later on actually gets the records starting with the
         * first record, not the second record.
         */
        mysqli_data_seek($resultk, 0);
}
    /*
    * Pass the query to mysql and retrieve the requested row.
    */    
    
$resultj = $mysqli->query($sqlj); 

printf("<b>Edit student</b>\n<br>\n<br>\n");

        if($result->num_rows > 0){
            
                if($row['Stu_M_name'] == ""){
                   $studentmiddlename = "";
                }
                else {
                   $studentmiddlename = $row['Stu_M_name'];
                }
                
//                $enrolled = explode(",", $row['enrolled']);
                
            printf("            <form name=\"editstudent\" method=\"post\" action=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("\">\n");
            printf("            <input type=\"hidden\" name=\"studentid\" value=\"%s\">", $stuindex);
            printf("            First Name: &nbsp; <input type=\"text\" name=\"studentfname\" value=\"%s\">\n", $row['Stu_F_name']);
            printf("            &nbsp; Middle Name (May Leave Blank): &nbsp; <input type=\"text\" name=\"studentmname\" value=\"%s\">\n<br>\n<br>\n", $studentmiddlename);
            printf("            &nbsp; Last Name: <input type=\"text\" name=\"studentlname\" value=\"%s\">\n", $row['Stu_L_name']);
            if($row['enrollment_date'] == NULL){
                    $todays_date_day = date("d");
                    $todays_date_month = date("m");
                    $todays_date_year = date("Y");
            }
            else {
                $exploded_date = explode("/", $row['enrollment_date']);
                    $todays_date_day = $exploded_date[0];
                    $todays_date_month = $exploded_date[1];
                    $todays_date_year = $exploded_date[2];   
       
            }
                    printf("            &nbsp; Enrollment day: <input type=\"text\" name=\"enrolledday\" size=\"2\" value=\"%s\">\n", $todays_date_day);
                    printf("            &nbsp; Enrollment Month: <input type=\"text\" name=\"enrolledmonth\" size=\"2\" value=\"%s\">\n", $todays_date_month);
                    printf("            &nbsp; Enrollment Year: <input type=\"text\" name=\"enrolledyear\" size=\"4\" value=\"%s\">\n<br>\n<br>\n", $todays_date_year);            
            printf("            Sponsor: &nbsp;<select name=\"sponsorcode\">\n");
                while($rowj = $resultj->fetch_assoc())
                    {
                    if($row['sponsor'] == $rowj['sponsor_index']){
                    printf("        &nbsp; &nbsp; <option value=\"%s\" selected>%s</option>\n", $rowj["sponsor_index"], $rowj["sponsor"]);
                    }
                    else {
                        printf("        &nbsp; &nbsp; <option value=\"%s\">%s</option>\n", $rowj["sponsor_index"], $rowj["sponsor"]);
                    }
                    }
            printf("        </select>\n");
            printf("        <select name=\"program_code\">\n");
                while($rowsl = $resultl->fetch_assoc()){
                    if($rowsl['group_index'] == $row['program_id']){
                        printf("        <option value=\"%s\" selected> %s </option>\n", $rowsl['group_index'], $rowsl['group_name']);
                    } else {
                        printf("        <option value=\"%s\"> %s </option>\n", $rowsl['group_index'], $rowsl['group_name']);
                    }
                }
            printf("        </select>\n         <br>\n      <br>\n");
            printf("            <hr>\n");
            printf("            <b>Classes</b>\n");
            printf("            <br>\n<br>\n");
            if($resulti->num_rows > 0){

                    printf("            <input type=\"hidden\" name=\"routine\" value=\"updatestudent\">\n");
                    printf("            <table class=\"standard\" cellspacing=\"0\" cellpadding=\"8\" style=\"border: 1px solid #000;\">\n");
                    printf("                <tr bgcolor=\"#cfcfcf\">\n");
                    printf("                    <th>Course</th><th>Enroll(ed)</th><th>Withdraw(n)</th>\n");
                    printf("                </tr>\n");
                while($rowsi = mysqli_fetch_array($resulti)){
                    printf("                <tr>\n");
                    printf("                    <td>\n");
                    printf("                        %s\n", $rowsi['program']);
                    printf("                    </td>\n");
                    

                    
                    if($rowsi['course_status'] == 0){
                       $total_rows = $resultk->num_rows;
                            $x = 0;
                            $sqlm = "SELECT * FROM enrolled WHERE prog_index = '" . $rowsi['prog_index'] . "' AND stu_index = '" . $stuindex . "'";
                            $resultsm = $mysqli->query($sqlm);
                            if(mysqli_num_rows($resultsm) > 0){
                                                       
                            while($rowsm = $resultsm->fetch_assoc()){
                            if($rowsm['status'] === "0"){
                                printf("                            <td align=\"left\" valign=\"middle\"><input type=\"radio\" name=\"enroll_%s\" value=\"0\" checked> Enroll(ed)\n", $rowsi['prog_index']);
                                printf("                            </td><td align=\"left\"  valign=\"middle\"><input type=\"radio\" name=\"enroll_%s\" value=\"2\"> WD\n", $rowsi['prog_index']);
                                //break;
                            
                            } elseif ($rowsm['status'] === "2"){
                                    printf("                            <td align=\"left\" valign=\"middle\"><input type=\"radio\" name=\"enroll_%s\" value=\"0\"> Enroll(ed)\n", $rowsi['prog_index']);
                                    printf("                            </td><td align=\"left\"  valign=\"middle\"><input type=\"radio\" name=\"enroll_%s\" value=\"2\" checked> WD\n", $rowsi['prog_index']);
                                    //break;
                            
                            } elseif ($rowsm['status'] === "3"){
                                    printf("                            <td align=\"left\" valign=\"middle\"><input type=\"radio\" name=\"enroll_%s\" value=\"0\"> Enroll\n", $rowsi['prog_index']);
                                    printf("                            </td><td align=\"left\"  valign=\"middle\"><input type=\"radio\" name=\"enroll_%s\" value=\"3\" checked> NE\n", $rowsi['prog_index']);
                                    //break;
                            } 
                                $x++; 
                            }
                            } else {
                                    printf("                            <td align=\"left\" valign=\"middle\"><input type=\"radio\" name=\"enroll_%s\" value=\"0\"> Enroll\n", $rowsi['prog_index']);
                                    printf("                            </td><td align=\"left\"  valign=\"middle\"><input type=\"radio\" name=\"enroll_%s\" value=\"3\" checked> NE\n", $rowsi['prog_index']);
                                   
                            }
                    } elseif($rowsi['course_status'] == 3) {
                        printf("                    </td><td align=\"left\"><img src=\"./images/closed.jpg\" height=\"59\" width=\"62\">\n");
                        printf("                            </td><td align=\"left\"><img src=\"./images/closed.jpg\" height=\"59\" width=\"62\">\n");
                    } else {
                        printf("                        <td align=\"left\"><img src=\"./images/exclamation.jpg\" height=\"48\" width=\"48\">\n");
                        printf("                    </td>\n");
                        printf("                        <td align=\"left\"><img src=\"./images/exclamation.jpg\" height=\"48\" width=\"48\">\n");
                    }
                    
                    printf("                    </td>\n");
                    printf("                </tr>\n");
                }
            }
            printf("                    </table>\n");
            printf("                    <br>\n");
            printf("                    <input type=\"submit\" name=\"updatestudent\" value=\"Update\">\n");
            printf("                    </form>\n");
            }
            else{
                Printf("<b>Oops!</b>\n");
            } 
            mysqli_close($mysqli);
}

function update_student(){
    
/*
 * Connect to database
 */    
    
$mysqli = connect_me();

    $stufirstname = $_POST['studentfname'];
    $stumiddlename = $_POST['studentmname'];
    $stulastname = $_POST['studentlname'];
    $stusponsor = $_POST['sponsorcode'];
    $studentid = $_POST['studentid'];
    $enrollment_date = $_POST['enrolledday'] . "/" . $_POST['enrolledmonth'] . "/" . $_POST['enrolledyear'];
    $program_code = $_POST['program_code'];
    
    
    /*
     * This next bit is going to be fun; enroll and withdraw have to be crunched into
     * one variable with the proper code(ie: e=enrolled, w=withdrawn etc) added
     * then placed in the update sql query, so as to update, without destroying
     * or adding to (unless a course hasn't been enrolled with tha student yet)
     * a student record... Fun fun fun, Fun fun fun, the Itchy and scratchy show!!!
     * 
     * So, I made some changes to the way the student update form works, it now
     * uses radio buttons instead of checkboxes, easier to handle in one way,
     * more difficult in another.
     * But, now there is no longer enroll or withdraw categories, now I have
     * to deal with enroll_## - ugh
     * 
     * Argh! A regex! I hate doing regex....
     * Oh well, overly complicated, I think, but it worked.
     * For my own future reference:
     * '/^enroll_(\d*)$/ means:
     * /^enroll_ = match only that input with the word "enroll_" in it (from start of line)
     * (\d*) = match any digits which follow enroll_
     * $/ = match to end of line
     * 
     * Thie following routine uses the above regex to extract the key of enroll_##
     * from the key => value pairs, then implode them into an enrollment code:course number
     * then into a complete line to be updated in the student's record via sql query.
     */
    

    $exp = '/^enroll_(\d*)$/';
    $values = Array();
    $enrolled = Array();
    foreach($_POST as $key => $val){
        $match = Array();
        $enrolled = explode('_', $key);
        if( preg_match($exp, $key, $match)){
            $values[] = $val;
            $base[] = $enrolled[1];
        }
        //echo "raw = " . $enrolled[1] . "<br>\n";
    }

    $enrollmentdata = implode(':', $base);
    
for($x = 0; $x < count($values); $x++){

    $keyval = $values[$x];
    $enrolled_courses = explode(":", $enrollmentdata);
    $course_id = $enrolled_courses[$x];
    
     $sqli = $mysqli->prepare("UPDATE enrolled SET status=?
            WHERE stu_index=? AND prog_index=?");
    
    //echo "Keyval = " . $keyval . " Course id = " . $course_id . "<br>\n";
     
    $sqll = "SELECT * FROM enrolled WHERE prog_index = '" . $course_id . "' AND stu_index = '" . $studentid . "'";
    //echo "SQLL = " . $sqll . "<br>\n";
    $resultsl = $mysqli->query($sqll);
    if(mysqli_num_rows($resultsl) > 0){
        $rowsl = $resultsl->fetch_assoc();
        $enroll_index = $rowsl['enroll_index'];
    } else {
        //$sqlb = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'pdt_attend' AND TABLE_NAME = 'enrolled'";
        $sqlb = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'attend_pdt' AND TABLE_NAME = 'enrolled'";
        $resultsb = $mysqli->query($sqlb);
        $rowsb = $resultsb->fetch_assoc();
        $enroll_index = $rowsb['AUTO_INCREMENT'];
    }
    
    //echo "Enroll Index = " . $enroll_index . "<br>\n";
    
    $sqli = $mysqli->prepare("INSERT INTO enrolled (enroll_index, prog_index, stu_index, status)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                prog_index = VALUES(prog_index),
                stu_index = VALUES(stu_index),
                status = VALUES(status)
            ");
    //printf("sqli: enroll_index = %s prog_index = %s stu_index = %s status = %s<br>\n", $enroll_index, $course_id, $studentid, $keyval);
    $sqli->bind_param('iiii', $enroll_index, $course_id, $studentid, $keyval);
    
    /*$sqli->bind_param("ssd",
            $keyval, 
            $studentid, 
            $course_id);
    */
    
//    printf("SQLI = UPDATE enrolled SET course_index = %s, status = %s, WHERE stu_index = %s<br>\n", $course_id, $keyval, $studentid);

// Need to add some error handling in the next routine...!!!
    
    if($sqli->execute()){
    } else {
        printf("    <b>Error! %s</b>\n", mysqli_error($mysqli));
    }
}

    $sql = $mysqli->prepare("UPDATE student SET Stu_F_name=?,
            Stu_M_name=?,
            Stu_L_name=?,
            sponsor=?,
            enrollment_date=?,
            program_id=?
            WHERE stu_index=?");


    $sql->bind_param("sssssdi",
            $stufirstname,
            $stumiddlename,
            $stulastname,
            $stusponsor,
            $enrollment_date,
            $program_code,
            $studentid);

/*
 * Execute the sql statement.
 */
    
       if($sql->execute()){
           printf("     <b>Successfuly updated: %s %s</b>\n", $stufirstname, $stulastname);
       }
       else {
           printf("         <b>Error: unsuccessfully attempted to update %s %s</b>\n", $stufirstname, $stulastname);
       }    
       unset($_POST);
       //$resultl->free();
       $resultsb->free();
       $resultsl->free();
       mysqli_close($mysqli);
       //header('Refresh: 1;url=login_succeeded.php?routine=liststu');
 

}

function list_courses(){
/*
 * Connect to database
 */

$mysqli = connect_me();

$sql = "SELECT * FROM classes ORDER BY program";

$result = $mysqli->query($sql);

printf("<b>List of Courses</b>\n<br>\n<br>\n");

if($result->num_rows > 0) {
    printf("            <table class=\"standard\" cellpadding=\"8\" border=\"0\">\n");
    printf("            <tr>\n");
    printf("            <th>Class Code</th>\n<th>Class Name</th>\n<th>Instructor</th>\n<th>Start Date</th>\n<th>End Date</th>\n<th>Status</th>\n");
while($row = $result->fetch_assoc()){
    

/* 
 * create the second sql query for this section
 */
    
    $sqli = "SELECT * FROM U_ops WHERE U_index=\"" . $row['inst_index'] ."\"";
//    echo "Sqli = " . $sqli;
    $results = $mysqli->query($sqli);
    if($rows = $results->fetch_assoc() == 0){
        $instrfname = "n/a";
        $instrlname = "";
    }
    else{
        $results = $mysqli->query($sqli);
        $rows = $results->fetch_assoc();
        $instrfname = $rows['U_F_name'];
        $instrlname = $rows['U_L_name'];
    }
    if($row['course_status'] == 0){
        $image = "checkmark.png";
        $title = "Course is current and running.";
    }
    elseif($row['course_status'] == 1){
        $image = "stop.png";
        $title = "Course is not current, and is cancelled.";
    }
    else {
        $image = "completed.jpg";
        $title = "Course is closed, not current.";
    }
    $start_date = $row['start_date_day'] . "/" . $row['start_date_month'] . "/" . $row['start_date_yr'];
    $end_date = $row['end_date_day'] . "/" . $row['end_date_month'] . "/" . $row['end_date_yr'];
    printf("            <tr>\n");
    printf("            <td>%s</td>\n<td>%s</td><td>%s %s</td><td>%s</td><td>%s</td><td><img src=\"./images/%s\" height=\"32\" width=\"32\" title=\"%s\"></td><td>\n", $row['program'], $row['prog_id'], $instrfname, $instrlname, $start_date, $end_date, $image, $title);
    printf("        <form name=\"f1\" method=\"post\" action=\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");    
    printf("            <input type=\"hidden\" name=\"courseid\" value=\"%s\">\n", $row['prog_index']);
    printf("            <input type=\"hidden\" name=\"routine\" value=\"edcourse\">\n");
    printf("            <input type=\"submit\" name=\"edcourse\" value=\"Edit Course\">\n");
    printf("            </form>\n            </td>\n");
    // Let's add an archive button
    // this will allow us to archive all of the data for a term
    // then we can purge IF we desire - purging is not easily reverseable
    printf("            <td>\n");
    printf("                <form name=\"archive_it\" method=\"post\" action=\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");
    printf("                    <input type=\"hidden\" name=\"id\" value=\"%s\">\n", $row['prog_index']);
    printf("                    <input type=\"hidden\" name=\"from\" value=\"%s\">\n", $row['from_date']);
    printf("                    <input type=\"hidden\" name=\"to\" value=\"%s\">\n", $row['to_date']);
    printf("                    <input type=\"hidden\" name=\"routine\" value=\"arch_class\">\n");
    printf("                    <input type=\"submit\" name=\"archcourse\" value=\"Archive\">\n");
    printf("                </form>\n");
    printf("            </td>\n");
    printf("            </tr>\n");
}   
printf("            </table>\n");
}
else {
    printf("<b>No Courses Added</b>\n");
}

}

function edit_course_form(){
    
    /*
     * Connect to database
     */
    
    $mysqli = connect_me();

    $days_of_week = array( "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
    $numbered_days = array("1", "2", "3", "4", "5", "6", "7");
    $minutes = array("00", "05", "10", "15", "20", "25", "30", "35", "40", "45");
    
    $courseid = $_POST['courseid'];
    
    $sql = <<<SQL
        SELECT *
        FROM classes
        WHERE prog_index = '$courseid'
SQL;

$sqli = "SELECT * FROM U_ops WHERE priv_level = '3'";
//$sqlj = "SELECT * FROM program_codes";
    
/*
 * Pass the query to mysql and retrieve rows.
 */    
    
    $result = mysqli_query($mysqli, $sql);

    $resulti = $mysqli->query($sqli);    
//    $resultj = $mysqli->query($sqlj);
    
    printf("        <b>Edit Courses</b>\n");
    printf("        <table border=\"0\">\n");

            $row = mysqli_fetch_array($result);
            $weekdays = explode(",", $row['weekday']);

            $twelve_hours_from = explode(":", date("g:i:a", strtotime($row['from_time'])));
            $twelve_hours_to = explode(":", date("g:i:a", strtotime($row['to_time'])));

            $from_hour = $twelve_hours_from[0];
            $from_min = $twelve_hours_from[1];
            $from_mer = $twelve_hours_from[2];
            
            $to_hour = $twelve_hours_to[0];
            $to_min = $twelve_hours_to[1];
            $to_mer = $twelve_hours_to[2];
            
            
            printf("        <form name=\"course_dates\" method=\"post\" action=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("\">\n");
            printf("        <input type=\"hidden\" name=\"routine\" value=\"editcourse\">\n");
            printf("        <input type=\"hidden\" name=\"courseid\" value=\"%s\">\n", $courseid);
            printf("        <tr>\n          <td>Course Name: &nbsp;<input type=\"text\" name=\"program\" value=\"%s\">           </td>\n", $row['program']);
            printf("        <td>Course Description: &nbsp;<input type=\"text\" name=\"prog_id\" value=\"%s\">\n", $row['prog_id']);
            printf("        </tr>\n");
            printf("        <tr>\n");
            printf("        <td>\n");
            $date_to = $row['end_date_day'] . "/" . $row['end_date_month'] . "/" . $row['end_date_yr'];
            $date_fr = $row['start_date_day'] . "/" . $row['start_date_month'] . "/" . $row['start_date_yr'];
            printf("        <br>Date From: &nbsp; <input type=\"text\" name=\"from\" size=\"10\" value=\"$date_fr\">\n");
            printf("        <a href=\"javascript:void(0);\" NAME=\"Date From\" title=\" Date From \" onClick=window.open(\"calendar.php?form=from\",\"Ratting\",\"width=350,height=270,left=150,top=200,toolbar=1,status=1,\");>\n");
            printf("<img src=\"./images/iconCalendar.gif\">");
            printf("</a>\n");
            printf("        </td>\n");
            printf("        <td>\n");

            printf("        <br>Date To: &nbsp; <input type=\"text\" name=\"to\" size=\"10\" value=\"%s\">\n", $date_to);
            printf("        <a href=\"javascript:void(0);\" NAME=\"Date To\" title=\" Date To \" onClick=window.open(\"calendar.php?form=to\",\"Ratting\",\"width=350,height=270,left=150,top=200,toolbar=1,status=1,\");>\n");
            printf("<img src=\"./images/iconCalendar.gif\">");
            printf("</a>\n");
            printf("        </td>\n");
            printf("        </tr>\n");
            printf("        <tr>\n");
            printf("            <td colspan=\"2\">\n");
            printf("            <br>\n");
            printf("        <b>Days Course Runs:</b>\n");
            printf("        <br><br>\n");
/*
 * print checkboxes for days of the week, checking if a day
 * is assigned or not, if it is, make sure the checkbox is checked,
 * if not, leave it unchecked.
 */

              foreach($numbered_days as $value){
                  printf("            <input type=\"checkbox\" name=\"weekday[]\" value=\"%s\"", $value);
                  if(in_array($value, $weekdays)){
                  printf(" checked> %s\n",$days_of_week[$value-1]);
                  } else {
                      printf("> %s\n", $days_of_week[$value-1]);
                  }
              }

            printf("        </td>\n");
            printf("        <tr>\n");
            printf("        <tr>\n");
            printf("            <td>\n");
            printf("        <br>\n");
            printf("        <b>Class Time:</b>\n");
            printf("            </td>\n");
            printf("        </tr>\n");
            printf("        <tr>\n");
            printf("        <td colspan=\"2\">\n");
            printf("        <br>\n");
            printf("        <table border=\"0\ cellpadding=\"8\">\n");
            printf("            <tr>\n");
            printf("                <th><b>From:</b></th><th><b>To:</b></th>\n");
            printf("            </tr>\n");
            printf("            <tr>\n");
            printf("                <td>\n");
            printf("                <div id=\"data-box-two\">\n");
            printf("                <select name=\"fromtime_hour\">\n");

        for($x = 1; $x < 13; $x++){
            if($x == $from_hour){
        printf("                <option value=\"%s\" selected> %s</option>\n", $x, $x);
            }
            else {
                printf("                <option value=\"%d\"> %d</option>\n", $x, $x);
            }
    }
            printf("                </select>\n");
            printf("                <select name=\"from_min\">\n");

                    for($xy = 0; $xy < 10; $xy++){
                        if($minutes[$xy] == $from_min){
                        printf("                    <option value=\"%s\" selected> %s\n", $minutes[$xy], $minutes[$xy]);
                        }
                        else {
                            printf("                    <option value=\"%s\"> %s\n", $minutes[$xy], $minutes[$xy]);
                        }
                    }
            printf("                </select>\n");
            if($from_mer == 'am'){
            printf("                <input type=\"radio\" name=\"from_meridian\" value=\"am\" checked> A.M.\n");
            printf("                <input type=\"radio\" name=\"from_meridian\" value=\"pm\"> P.M.<br>\n");
            }
            else {
                printf("                <input type=\"radio\" name=\"from_meridian\" value=\"am\"> A.M.\n");
                printf("                <input type=\"radio\" name=\"from_meridian\" value=\"pm\" checked> P.M.<br>\n");
            }
            printf("                </div>\n");
            printf("                </td>\n");
            printf("                </select>\n");
            printf("            <td>\n");
            printf("                <div id=\"data-box-two\">\n");
            printf("                <select name=\"totime_hour\">\n");
        for($x = 1; $x < 13; $x++){
            if($x == $to_hour){
        printf("                <option value=\"%s\" selected> %s</option>\n", $x, $x);
            }
            else {
                printf("                <option value=\"%d\"> %d</option>\n", $x, $x);
            }
    }
            printf("                </select>\n");
            printf("                <select name=\"to_min\">\n");
                    for($xy = 0; $xy < 10; $xy++){
                        if($minutes[$xy] == $to_min){
                        printf("                    <option value=\"%s\" selected> %s\n", $minutes[$xy], $minutes[$xy]);
                        }
                        else {
                            printf("                    <option value=\"%s\"> %s\n", $minutes[$xy], $minutes[$xy]);
                        }
                    }
            printf("                </select>\n");
            if($to_mer == "am"){
            printf("                <input type=\"radio\" name=\"to_meridian\" value=\"am\" checked> A.M.\n");
            printf("                <input type=\"radio\" name=\"to_meridian\" value=\"pm\"> P.M.<br>\n");
            }
            else {
                printf("                <input type=\"radio\" name=\"to_meridian\" value=\"am\"> A.M.\n");
                printf("                <input type=\"radio\" name=\"to_meridian\" value=\"pm\" checked> P.M.<br>\n");
            }
            printf("                </div>\n");
            printf("            </td>\n");
            printf("        </table>\n");
            printf("        </tr>\n");
            printf("        <tr>\n");
            printf("        <td>\n          <br>          Instructor &nbsp;\n");
            printf("        <select name=\"instructor\">\n");
                    while($rows = $resulti->fetch_assoc()){
                    if($rows['U_index'] == $row['inst_index']){
                        printf("        <option value=\"%s\" selected>%s %s</option>\n", $rows["U_index"], $rows["U_F_name"],  $rows["U_L_name"]);
                    }
                    else{
                    printf("        <option value=\"%s\">%s %s</option>\n", $rows["U_index"], $rows["U_F_name"],  $rows["U_L_name"]);
                    }
                    }
            printf("            </td>\n");
            printf("            <td>\n      <br>");
/*            printf("        <select name=\"program_code\">\n");
                    while($rowsj = $resultj->fetch_assoc()){
                    if($rowsj['group_index'] == $row['group_index']){
                        printf("      <option value=\"%s\" selected> %s </option>\n", $rowsj['group_index'], $rowsj['group_name']);
                    } else {
                        printf("      <option value=\"%s\"> %s </option>\n", $rowsj['group_index'], $rowsj['group_name']);
                    }
                        
                    }
            printf("        </select>\n"); */
            printf("</td>\n");
            printf("            </tr>\n");
            printf("        <tr>\n");
            printf("        <td>\n");
            printf("        <br>\n");
            printf("                <div id=\"data-box-two\">\n");
            printf("                <b>Course Status</b>\n");
            if($row['course_status'] == 0){
            printf("                <input type=\"radio\" name=\"course_status\" value=\"0\" checked> Current\n");
            printf("                <input type=\"radio\" name=\"course_status\" value=\"1\"> Canceled\n");
            printf("                <input type=\"radio\" name=\"course_status\" value=\"3\"> Close\n");
            }
            elseif($row['course_status'] == 1){
                        printf("                <input type=\"radio\" name=\"course_status\" value=\"0\"> Current\n");
                        printf("                <input type=\"radio\" name=\"course_status\" value=\"1\" checked> Canceled\n");    
                        printf("                <input type=\"radio\" name=\"course_status\" value=\"3\"> Close\n");
            }
            else {
                        printf("                <input type=\"radio\" name=\"course_status\" value=\"0\"> Current\n");
                        printf("                <input type=\"radio\" name=\"course_status\" value=\"1\"> Canceled\n");    
                        printf("                <input type=\"radio\" name=\"course_status\" value=\"3\" checked> Close\n");
            }
            printf("                </div>\n");
            printf("        </td>\n");
            printf("        </tr>\n");
            printf("        <tr>\n");
            printf("        <td>\n");
            printf("        <br>\n");
            printf("        <input type=\"hidden\" name=\"routine\" value=\"updatecourse\">\n");
            printf("        <input type=\"submit\" name=\"updatecourse\" value=\"Update course\">\n");
            printf("        </td>\n");
            printf("        </form>\n");

            $purged = $row['purged'];
            $archived = $row['archived'];
            
            printf("        <td>\n");
            if($archived == '1' and $purged == '0'){
                printf("            <form name=\"purge\"  method=\"post\" action=\"");
                printf(htmlspecialchars($_SERVER['PHP_SELF']));
                printf("\">\n");
                printf("                    <input type=\"hidden\" name=\"routine\" value=\"purge\">\n");
                printf("                    <input type=\"hidden\" name=\"course_id\" value=\"%s\">\n", $courseid);
                printf("                    <input type=\"submit\" name=\"purge\" value=\"Purge Records\">\n");
                printf("            </form>\n");
            } else if (($archived == '1') and ($purged == '1')){
                printf("Archived + Purged.\n");                
            } else {
                printf("            Records Not Archived\n");
            }
            printf("        </td>\n");
            
            printf("        <td>\n");
            
            $sql_a = "SELECT * FROM attend, enrolled WHERE attend.course_index='" . $courseid . "' AND enrolled.prog_index = '" . $courseid . "'";
            $results_a = $mysqli->query($sql_a);
            $rows_a = $results_a->fetch_assoc();
                        

            
            if(($purged == '1') and ($archived == '1')){
                printf("            <form name=\"del_class\"  method=\"post\" action=\"");
                printf(htmlspecialchars($_SERVER['PHP_SELF']));
                printf("\">\n");
                printf("                    <input type=\"hidden\" name=\"routine\" value=\"del_class\">\n");
                printf("                    <input type=\"hidden\" name=\"course_id\" value=\"%s\">\n", $courseid);
                printf("                    <input type=\"submit\" name=\"delete_class\" value=\"Delete Class\">\n");
            } else if(mysqli_num_rows($results_a) != 0){
                printf("There are records  - cannot delete.");
            } else {
                printf("            <form name=\"del_class\"  method=\"post\" action=\"");
                printf(htmlspecialchars($_SERVER['PHP_SELF']));
                printf("\">\n");
                printf("                    <input type=\"hidden\" name=\"routine\" value=\"del_class\">\n");
                printf("                    <input type=\"hidden\" name=\"course_id\" value=\"%s\">\n", $courseid);
                printf("                    <input type=\"submit\" name=\"delete_class\" value=\"Delete Class\">\n");                
            }
            printf("        </td>\n");
            printf("        </tr>\n");
            
            
    printf("        </table>\n");
           
    mysqli_close($mysqli);  
    
    
}

function update_courses(){
    
/*------------------------------------------------------------------------------*/
/* Connect to mysql                                                             */
/*------------------------------------------------------------------------------*/

$mysqli= connect_me();


    
    $program = $_POST['program'];
    $course_from = $_POST['from'];
    $course_to = $_POST['to'];
    $inst_index = $_POST['instructor'];
    $prog_desc = $_POST['prog_id'];
    $prog_index = $_POST['courseid'];
    $twelve_hour_from = $_POST['fromtime_hour'] . ":" . $_POST['from_min'] . " " . $_POST['from_meridian'];
    $twele_hour_to = $_POST['totime_hour'] . ":" . $_POST['to_min'] . " " . $_POST['to_meridian'];
    $fromtime = date("H:i", strtotime($twelve_hour_from));
    $totime = date("H:i", strtotime($twele_hour_to));
    $coursestatus = $_POST['course_status'];
//    $program_code = $_POST['program_code'];
    //$weekdays = $_POST['dow'];
    
$sql_getarch = "SELECT archived, purged FROM classes WHERE prog_index = '" . $prog_index . "'";
$results_getarch = $mysqli->query($sql_getarch);
$rows_getarch = $results_getarch->fetch_assoc();
$archived = $rows_getarch['archived'];
$purged = $rows_getarch['purged'];

if($archived == '1'){
    $archived = '0';
    $purged = '0';
}
    
if(isset($_POST['weekday'])){
$weekdays = (is_array($_POST['weekday'])) ? implode(',', $_POST['weekday']) : '';
}

 else {
     $weekdays = 0;
}    
    /*
     * connect to database
     */
    
    $mysql = connect_me();
    
/*
 * Explode the from dates into seperate entities
 */

    $from_dates = explode("/", $course_from);

/*
 * Explode to dates into an array
 */

    $to_dates = explode("/", $course_to);    
 
/*    foreach ($_POST as $key => $value){
    printf("<br>\n<br>\n");
    printf("Key: %s ", $key);
    printf("&nbsp; &nbsp;Value: %s\n", $value);
    printf("<br>\n<br>\n");   
   }   
*/ 
    
    $sql = $mysql->prepare("UPDATE classes SET program=?, 
            inst_index=?,
            prog_id=?,
            start_date_day=?, 
            start_date_month=?,
            start_date_yr=?,
            end_date_day=?,
            end_date_month=?,
            end_date_yr=?,
            weekday=?,
            from_time=?,
            to_time=?,
            course_status=?,
            archived=?,
            purged=?
            WHERE prog_index=?");
    $sql->bind_param("ssssssssssssssss", 
            $program,
            $inst_index,
            $prog_desc,
            $from_dates[0], 
            $from_dates[1], 
            $from_dates[2],
            $to_dates[0], 
            $to_dates[1], 
            $to_dates[2], 
            $weekdays,
            $fromtime,
            $totime,
            $coursestatus,
            $archived,
            $purged,
            $prog_index);
    
/*
 * Execute the sql statement.
 */
    
       if($sql->execute()){
           printf("     <b>Successfuly updated: %s</b>\n", $program);
       }
       else {
           printf("         <b>Error: unsuccessfully attempted to update %s</b>\n", $program);
       }   
       unset($_POST);
       
       //$sql->close();
       mysqli_close($mysql);
       header('Refresh: 1;url=login_succeeded.php?routine=listprogs'); 
}

function add_sponsor_form(){
    printf("<b>Add a New Sponsor</b>\n<br>\n<br>\n");
printf("    <form name=\"addsponsor\" method=\"post\" action=\"");
printf(htmlspecialchars($_SERVER['PHP_SELF']));
printf("\">\n");
printf("        <input type=\"hidden\" name=\"routine\" value=\"add_sponsor\">\n");
printf("        Sponsor Name: &nbsp; <input type=\"text\" name=\"sponsor\" />\n");
printf("        Description: <input type=\"text\" name=\"description\" />\n<br>\n<br>\n");
printf("        <input type=\"submit\" name=\"sponsor-submit\" value=\"Add Sponsor\" />\n");
printf("    </form>\n");
}

function edit_sponsor(){
/*
 * Connect to Mysql
 */
    
$mysqli = connect_me();

$sponsor_id = $_POST['spons_id'];

$sql = "SELECT * FROM sponsor WHERE sponsor_index = '" . $sponsor_id . "'";
$results = $mysqli->query($sql);
$rows = $results->fetch_assoc();

printf("<b>Edit Sponsor</b>\n<br>\n<br>\n");
printf("    <form name=\"addsponsor\" method=\"post\" action=\"");
printf(htmlspecialchars($_SERVER['PHP_SELF']));
printf("\">\n");
printf("        <input type=\"hidden\" name=\"routine\" value=\"update_sponsor\">\n");
printf("        <input type=\"hidden\" name=\"spons_id\" value=\"%s\">\n", $sponsor_id);
printf("        Sponsor Name: &nbsp; <input type=\"text\" name=\"sponsor\" value=\"%s\">\n", $rows['sponsor']);
printf("        Description: <input type=\"text\" name=\"description\" value=\"%s\">\n", $rows['spons_descr']);
printf("        <br>\n          <br>\n");
printf("        <input type=\"submit\" name=\"sponsor-submit\" value=\"Update Sponsor\" />\n");
printf("    </form>\n");

$results->free();
mysqli_close($mysqli);
}

function update_sponsor(){
/*
 * Connect to Mysql
 */
    
    $mysqli = connect_me();
    
    $spons_id = $_POST['spons_id'];
    $spons_name = $_POST['sponsor'];
    $spons_descr = $_POST['description'];
    
    $sql = $mysqli->prepare("UPDATE sponsor SET sponsor=?, spons_descr=? WHERE sponsor_index=?");
    $sql->bind_param('sss', $spons_name, $spons_descr, $spons_id);
    
    if($sql->execute()){
        printf("<b>Sponsor: %s has been updated.<br>\n", $spons_name);
    } else {
        printf("Well, this is embarassing, an error has ocurred!<br>\n");
    }
    
    mysqli_close($mysqli);
    header('Refresh: 1;url=login_succeeded.php?routine=listspons');
}

function add_sponsor(){
    
/*
 * Take the data from the add sponsor form and add it to the database.
 */

$sponsor_name = $_POST['sponsor'];
$sponsor_description = $_POST['description'];
    
/*
 * Connect to database
 */

$mysqli = connect_me();

/*
 * Create the prepared sql statement, bind it.
 */
    
    $sql = $mysqli->prepare("INSERT INTO sponsor (sponsor, spons_descr) VALUES (?, ?)");
    $sql->bind_param("ss", $sponsor_name, $sponsor_description);

/*
 * Execute the sql statement.
 */
    
       if($sql->execute()) {
           printf("New sponsor: %s<br>\n", $sponsor);
       } else {
           printf("Well, this is embarassing sponsor: %s could not be added<br>\n", $sponsor);
       }
         
    
/*
 * Close the sql statement, close the database connection, unset post data,
 * redirect back to this page.
 */
    
       //$sql->close();
       mysqli_close($mysqli);
       unset($_POST);
       header("Location:login_succeeded.php?routine=listspons");

}

function list_sponsors(){
    
/*
 * Connect to database
 */
    
$mysqli = connect_me();
    
/* 
 * create the sql query
*/
//    $sql = "SELECT * FROM sponsor";
    
    $sql = <<<SQL
        SELECT *
        FROM sponsor
SQL;
   
/*
 * Pass the query to mysql and retrieve rows.
 */    
    
    $result = mysqli_query($mysqli, $sql);

printf("<b>List of Sponsors</b>\n<br>\n<br>\n");
    
if($result->num_rows > 0) {
    
    /* Display system users in a table */

    printf("<table class=\"standard\" cellpadding=\"8\" border=\"0\">\n");
    printf("    <tr>\n");
    printf("        <th>Sponsor </th>\n");
    printf("        <th>Description</th>\n");
    printf("    </tr>\n");
    
        while($row = mysqli_fetch_array($result)) {
            printf("        <tr>\n");
            printf("        <td>%s</td>\n", $row['sponsor']);
            printf("        <td>%s</td>\n", $row['spons_descr']);
                    printf("        <form name=\"delsponsor\" method=\"post\" action=\"");
                    printf(htmlspecialchars($_SERVER['PHP_SELF']));
                    printf("\">\n");
                    printf("        <input type=\"hidden\" name=\"sponsorid\" value=\"%s\">\n", $row['sponsor_index']);
                    printf("        <input type=\"hidden\" name=\"routine\" value=\"delsponsor\">\n");
                    printf("        <td>&nbsp;<input type=\"submit\" name=\"del_sponsor\" value=\"Delete sponsor\"></td>\n");
                    printf("        </form>\n");
                    printf("        <td>\n");
                    printf("            <form name=\"delsponsor\" method=\"post\" action=\"");
                    printf(htmlspecialchars($_SERVER['PHP_SELF']));
                    printf("\">\n");
                    printf("                <input type=\"hidden\" name=\"routine\" value=\"ed_spons\">\n");
                    printf("                <input type=\"hidden\" name=\"spons_id\" value=\"%s\">\n",  $row['sponsor_index']);
                    printf("                <input type=\"submit\" name=\"edit_spons\" value=\"Edit\">\n");
                    printf("            </form>\n");
                    printf("        </td>\n");
                    
            printf("        </tr>\n");
        }
    printf("</table>\n");
    
}
 else {
     printf("<b>No Sponsors Recorded!</b>\n<br>\n");
}
/*
 * Close the sql statement, close the database connection, unset post data,
 * redirect back to this page.
 */

        $result->free();

        $mysqli->close();
}

function del_sponsor(){
/*
 * Connect to database
 */
    
$mysqli = connect_me();
    
$sponsor_id = $_POST['sponsorid'];

echo "Userid = " . $userid;

$sql=$mysqli->prepare("DELETE FROM sponsor WHERE sponsor_index = ?");
$sql->bind_param("d", $sponsor_id);

/*
 * Execute the sql statement.
 */
    
       if($sql->execute()){
           printf("Sponsor deleted<br>\n");
       } else {
           printf("Well, this is embarrassing, an error has ocurred, sponsor not deleted<br>\n");
       }

/*
 * Close the sql statement, close the database connection, unset post data,
 * redirect back to this page.
 */
    
       
mysqli_close($mysqli);
       unset($_POST);
       header("Location:login_succeeded.php?routine=listspons");
       exit();
}

function delete_class_ask(){
/*------------------------------------------------------------------------------*/
/* Ask if the user really, really REALLy wants to delete a class, as the        */
/* operation is irreverseable.                                                  */
/*------------------------------------------------------------------------------*/

    $course_id = $_POST['course_id'];
    
    printf("<b>Delete Class</b>\n<br>\n<br>\n");
    printf("<b>Permanant Operation!</b>\n<br>\n<br>\n");
    
    printf("<table class=\"left\" cellpadding=\"2\" border=\"0\">\n");
    printf("    <tr>\n");
    printf("        <td>Do you really wish to proceed?</td></tr>\n");
    printf("        <tr><td>This will <b><u>permanantly remove</u></b> this class!</td></tr>\n");
    printf("    </tr>\n");
    printf("    <tr>\n");
    printf("        <form name=\"purge_em\" method=\"post\" action\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");
    printf("        <td>\n");
    printf("            <input type=\"hidden\" name=\"routine\" value=\"del_class_real\">\n");
    printf("            <input type=\"hidden\" name=\"course_id\" value=\"%s\">\n", $course_id);
    printf("            <input type=\"submit\" name=\"yes_delete\" value=\"Yes - Delete\">\n");
    printf("        </td>\n");
    printf("        </form>\n");
    printf("</table>\n");    
    
}

function delete_class(){
/*------------------------------------------------------------------------------*/
/* So, not only be there dragons here, but if you make it past the dragon, you  */
/* may just fall off the edge of the earth.                                     */
/*------------------------------------------------------------------------------*/
/* This section allows you to delete a course (after some safety checks, of     */
/* course)                                                                      */
/*------------------------------------------------------------------------------*/

/*
 * Connect to database
 */

$mysqli = connect_me();   

$course_id = $_POST['course_id'];

/*------------------------------------------------------------------------------*/
/* Sanity Check!                                                                */
/* This page should never be arrived at unless there are no records associted   */
/* with this class, as checks are performed BEFORE the delete button is even    */
/* available, but, you never know, can't be TOO careful!                        */
/*------------------------------------------------------------------------------*/

/*------------------------------------------------------------------------------*/
/* Create sql statement to retrieve data from specific tables using this class  */
/* ID as the key.                                                               */
/*------------------------------------------------------------------------------*/

$sql_a = "SELECT * FROM attend, enrolled WHERE attend.course_index = '" . $course_id . "' AND enrolled.prog_index = '" . $course_id . "'";
$results_a = $mysqli->query($sql_a);
$rows_a = $results_a->fetch_assoc();

if(mysqli_num_rows($results_a) != 0){
    printf("Cannot Delete Class!<br>Thee are still records associated<br>With it. Archive and Purge first!<br>");
    /*--------------------------------------------------------------------------*/
    /* Sanity Check Complete - failed                                           */
    /*--------------------------------------------------------------------------*/
    
    $results_a->free();
    header("Refresh: 3;url=login_succeeded.php?routine=listprogs");
} else {
    /*--------------------------------------------------------------------------*/
    /* The sanity check is completed and has passed the test, procced with      */
    /* class deletion                                                           */
    /*--------------------------------------------------------------------------*/

    printf("<b>Deleting Class From Programs:<b><br>\n");
    $sql_b = $mysqli->prepare("DELETE FROM programs WHERE course_index = ?");
    $sql_b->bind_param("s", $course_id);
    
    if(!$sql_b->execute()){
        printf("A drastic error has ocurred! %s<br>\n", mysqli_error($mysqli));
        $results_a->free();
        return;
    }    
    printf("Class deleted from programs<br>Now deleting class:<br>\n");
    
    printf("<b>Deleting Class:<b><br>\n");
    $sql_b = $mysqli->prepare("DELETE FROM classes WHERE prog_index = ?");
    $sql_b->bind_param("s", $course_id);
    
    if(!$sql_b->execute()){
        printf("A drastic error has ocurred! %s<br>\n", mysqli_error($mysqli));
        $results_a->free();
        return;
    }    
}

$results_a->free();
$mysqli->close();
header("Refresh: 5;url=login_succeeded.php?routine=listprogs");
}

function add_new_course_form(){
    
/*
 * Connect to database
 */

$mysqli = connect_me();

/*
 * Create prepared sql statement
 */
    
    $sql = "SELECT * FROM U_ops WHERE priv_level = '3'";
//    $sqla = "SELECT * FROM program_codes";

    /*
    * Pass the query to mysql and retrieve the requested row.
    */    
    
$result = $mysqli->query($sql);
//$resulta = $mysqli->query($sqla);

printf("<b>Add a New Course/Class</b>\n<br>\n<br>\n");

if($result->num_rows > 0) {    
    
printf("    <form name=\"course_dates\" method=\"post\" action=\"");
printf(htmlspecialchars($_SERVER['PHP_SELF']));
printf("\">\n");
printf("        Course Name: &nbsp; <input type=\"text\" name=\"course\" />\n");
printf("        Description: <input type=\"text\" name=\"description\" />\n<br>\n<br>\n");
printf("        Date From: &nbsp; <input type=\"text\" name=\"from\" size=\"8\">\n");
printf("<a href=\"javascript:void(0);\" NAME=\"Date From\" title=\" Date From \" onClick=window.open(\"calendar.php?form=from\",\"Ratting\",\"width=350,height=270,left=150,top=200,toolbar=1,status=1,\");>\n");
printf("<img src=\"images\iconCalendar.gif\">");
printf("</a>\n");
printf("        Date To: &nbsp; <input type=\"text\" name=\"to\" size=\"8\">\n");
printf("<a href=\"javascript:void(0);\" NAME=\"Date To\" title=\" Date To \" onClick=window.open(\"calendar.php?form=to\",\"Ratting\",\"width=350,height=270,left=150,top=200,toolbar=1,status=1,\");>\n");
printf("<img src=\"images\iconCalendar.gif\">");
printf("</a>\n");
printf("        <br><br>\n");
printf("        <b>Days Course Runs:</b>\n");
printf("        <br><br>\n");
printf("        <input type=\"checkbox\" name=\"weekday[]\" value=\"1\"> Sun\n");
printf("        <input type=\"checkbox\" name=\"weekday[]\" value=\"2\" checked> Mon\n");
printf("        <input type=\"checkbox\" name=\"weekday[]\" value=\"3\"> Tue\n");
printf("        <input type=\"checkbox\" name=\"weekday[]\" value=\"4\"> Wed\n");
printf("        <input type=\"checkbox\" name=\"weekday[]\" value=\"5\"> Thu\n");
printf("        <input type=\"checkbox\" name=\"weekday[]\" value=\"6\"> Fri\n");
printf("        <input type=\"checkbox\" name=\"weekday[]\" value=\"7\"> Sat\n");
printf("        <br><br>\n");
printf("        <b>Class Time:</b>\n");
printf("        <br><br>\n");
printf("        <table border=\"0\ cellpadding=\"8\">\n");
printf("            <tr>\n");
printf("                <th><b>From:</b></th><th><b>To:</b></th>\n");
printf("            </tr>\n");
printf("            <tr>\n");
printf("                <td>\n");
printf("                <div id=\"data-box-two\">\n");
printf("                <select name=\"fromtime_hour\">\n");
$x = 1;
while($x <= 12){
    printf("                <option value=\"%d\"> %d</option>\n", $x, $x);
    $x++;
}
printf("                </select>\n");
printf("                <select name=\"from_min\">\n");
printf("                    <option value=\"10\"> 00\n");
printf("                    <option value=\"05\"> 05\n");
printf("                    <option value=\"10\"> 10\n");
printf("                    <option value=\"15\"> 15\n");
printf("                    <option value=\"20\"> 20\n");
printf("                    <option value=\"25\"> 25\n");
printf("                    <option value=\"30\"> 30\n");
printf("                    <option value=\"45\"> 45\n");
printf("                </select>\n");
printf("                <input type=\"radio\" name=\"from_meridian\" value=\"AM\" checked> A.M.\n");
printf("                <input type=\"radio\" name=\"from_meridian\" value=\"PM\"> P.M.<br>\n");
printf("                </div>\n");
printf("                </td>\n");
printf("                </select>\n");
printf("            <td>\n");
printf("                <div id=\"data-box-two\">\n");
printf("                <select name=\"totime_hour\">\n");
$xx = 1;
while($xx <= 12){
    printf("                <option value=\"%d\"> %d</option>\n", $xx, $xx);
    $xx++;
}
printf("                </select>\n");
printf("                <select name=\"to_min\">\n");
printf("                    <option value=\"00\"> 00\n");
printf("                    <option value=\"05\"> 05\n");
printf("                    <option value=\"10\"> 10\n");
printf("                    <option value=\"15\"> 15\n");
printf("                    <option value=\"20\"> 20\n");
printf("                    <option value=\"25\"> 25\n");
printf("                    <option value=\"30\"> 30\n");
printf("                    <option value=\"30\"> 45\n");
printf("                </select>\n");
printf("                <input type=\"radio\" name=\"to_meridian\" value=\"AM\"> A.M.\n");
printf("                <input type=\"radio\" name=\"to_meridian\" value=\"PM\" checked> P.M.<br>\n");
printf("                </div>\n");
printf("            </td>\n");
printf("        </table>\n");
printf("        <br>\n<br>Instructor: &nbsp;\n      <select name=\"instructor\">\n");
while($row = $result->fetch_assoc())
{
    printf("        <option value=\"%s\">%s %s</option>\n", $row["U_index"], $row["U_F_name"],  $row["U_L_name"]);
}
printf("        </select>\n         <br>\n      <br>\n");

/*                    while($rowsa = $resulta->fetch_assoc()){
                            printf("      &nbsp;&nbsp; Select Program:<option value=\"%s\"> %s </option>\n", $rowsa['group_index'], $rowsa['group_name']);
                    }
                        
                   
            printf("        </select>\n         <br>\n      <br>\n"); */

printf("        <input type=\"hidden\" name=\"routine\" value=\"addcourse\">\n");
printf("        <input type=\"submit\" name=\"course-submit\" value=\"Add Course\" />\n");
printf("    </form>\n");
}
 else {
Printf("<b>Add an Instructor First!</b>\n");    
}

        $result->free();
        $mysqli->close();
}

function add_course(){
    
/*
 * Take the data from the add sponsor form and add it to the database.
 */

$course_name = $_POST['course'];
$course_description = $_POST['description'];
$course_from = $_POST['from'];
$course_to = $_POST['to'];
$course_instructor = $_POST['instructor'];
//$program_code = $_POST['program_code'];

$from_epoch = to_new_date($course_from, "dmy", "epoch", "/", "");
$to_epoch = to_new_date($course_to, "dmy", "epoch", "/", "");

//echo "Date from = " . $from_epoch . "<br>\n";
//echo "Date to = " . $to_epoch . "<br>\n";

$twelve_hour_from = $_POST['fromtime_hour'] . ":" . $_POST['from_min'] . " " . $_POST['from_meridian'];
$twele_hour_to = $_POST['totime_hour'] . ":" . $_POST['to_min'] . " " . $_POST['to_meridian'];
$fromtime = date("H:i", strtotime($twelve_hour_from));
$totime = date("H:i", strtotime($twele_hour_to));

if(isset($_POST['weekday'])){
$weekdays = (is_array($_POST['weekday'])) ? implode(',', $_POST['weekday']) : '';
}
 else {
     $weekdays = 0;
}

/*
 * Explode the from dates into seperate entities
 */

$from_dates = explode("/", $course_from);

/*
 * Explode to dates into an array
 */

$to_dates = explode("/", $course_to);

//echo "Query: " . $course_name . " - ". $course_description . " - " . $course_instructor . " - " . $to_dates[0] . " - " . $to_dates[1] . " - " . $to_dates[2] . " - " . $from_dates[0] . " - " . $from_dates[1] . " - " . $from_dates[2];


/*
 * Connect to database
 */

$mysqli = connect_me();

/*
 * Create the prepared sql statement, bind it.
 */
    $archived = "0";
    $purged = "0";
    $sql = $mysqli->prepare("INSERT INTO classes (program, prog_id, inst_index, start_date_day, start_date_month, start_date_yr, end_date_day, end_date_month, end_date_yr, from_date, to_date, weekday, from_time, to_time, archived, purged) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $sql->bind_param("ssssssssssssssss", $course_name, $course_description, $course_instructor, $from_dates[0], $from_dates[1], $from_dates[2], $to_dates[0], $to_dates[1], $to_dates[2], $from_epoch, $to_epoch, $weekdays, $fromtime, $totime, $archived, $purged);
    // Check why an error is occurring here.
    //printf("Error! Danger Will Robinson! %s\n<br>\n", mysqli_error($mysqli));
/*
 * Execute the sql statement.
 */
    
       if($sql->execute()){
           printf("Creation of new class: %s Succeeded<br>\n", $course_name);
       } else {
           printf("Well, this is embarassing; creation of: %s failed<br>%s\n", $course_name, mysqli_error($mysqli));
           // Check why an error is occurring here.
           //printf("Error! Danger Will Robinson! %s\n<br>\n", mysqli_error($mysqli));
       }
    
/*
 * Close the sql statement, close the database connection, unset post data,
 * redirect back to this page.
 */
    
       mysqli_close($mysqli);
       
/*       foreach($_POST as $key => $value){
           printf("<br>\n<br>\n");
           printf("Key: %s ", $key);
           printf("&nbsp; &nbsp;Value: %s\n", $value);
           printf("<br>\n<br>\n");
       } */
       
       
       //unset($_POST);
       //header('Refresh: 1;url=login_succeeded.php?routine=listprogs');


}

function change_pass_form(){
   /*
    * Connect to database
    */
    $mysqli = connect_me();

    $userid = $_POST['userid'];
    $sql = $mysqli->prepare("SELECT * FROM U_ops WHERE U_index = ?");
    $sql->bind_param("s", $userid);
    
    $sql->execute();

    $sql->bind_result($one, $two, $three, $four, $five, $six);
    $sql->fetch();




printf("<b>Change Password for:</b> %s %s\n", $four, $five);
printf("<br>\n<br>\n");
printf("<table class=\"noborder\" cellpadding=\"8\">\n");
printf("<tr>\n");
printf("    <form name=\"ch_pass\" method=\"post\" action=\"");
printf(htmlspecialchars($_SERVER['PHP_SELF']));
printf("\">\n");
printf("        <td>New Password: &nbsp; <input type=\"password\" name=\"password\" /></td>\n");
printf("        <input type=\"hidden\" name=\"userid\" value=\"%s\">\n", $one);
printf("        <input type=\"hidden\" name=\"routine\" value=\"mypass\">\n");
printf("        <td><input type=\"submit\" name=\"mypass\" value=\"Change Pass\" /></td>\n");
printf("</tr>\n");
printf("<tr>\n");
printf("        <td>Re-enter: <input type=\"password\" name=\"comp_pass\"></td>\n\n");
printf("</tr>\n");
printf("    </form>\n");
printf("</table>\n");
    

mysqli_close($mysqli);

}

function change_password(){
    
    if($_POST['password'] === "" Or $_POST['comp_pass'] === ""){
        printf("<b>Either the password or the re-enter line was blank: Please try again.<br>\n");
        printf("<br>\n<br>\n");
        printf("<form name=\"ch_pass\" method=\"post\" action=\"");
        printf(htmlspecialchars($_SERVER['PHP_SELF']));
        printf("\">\n");
        printf("    <input type=\"hidden\" name=\"routine\" value=\"chmypass\">\n");
        printf("    <input type=\"hidden\" name=\"userid\" value=\"%s\">\n", $_POST['userid']);
        printf("    <input type=\"submit\" name=\"back_to_change\" value=\"Back\">\n");
        printf("</form>\n");
        exit;
    }

$password = $_POST['password'];
$userid = $_POST['userid'];
$comp_pass = $_POST['comp_pass'];

if(compare_pass($password, $comp_pass) == 1) {

/* The following few lines connect to the mysql database
 * (or throw an error message if connection fails)
 */
    
$mysqli = connect_me();    
     




/*
 * prep to hash the password
 * This uses blowfish
 */
    
    $cost = 10;
    $salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
    $salt = sprintf("$2a$%02d$", $cost) . $salt;
    
/*
 * Hash the password
 */
    
    $hash = crypt($password, $salt);
    
//echo "Hash: " . $hash . "\n";
    
/*
 * Create the prepared sql statement, bind it.
 */
    
    $sql = $mysqli->prepare("UPDATE U_ops SET U_pass=? WHERE U_index=?");
    $sql->bind_param("sd", $hash, $userid);

   
/*
 * Execute the sql statement.
 */
    
       if($sql->execute()){
           printf("<b>Password Successfully updated</b>\n");
       }
       else {
           printf("<b>Password update failure</b>\n");
       }
    
/*
 * Close the sql statement, close the database connection, unset post data,
 * redirect back to this page.
 */
    
mysqli_close($mysqli);
} else {
    
    Printf("Passwords do not match! Try again!<br>\n");
    header('Refresh: 5;url=login_succeeded.php?routine=chmypass');
}
header('Refresh: 5;url=login_succeeded.php');       
}

function settings_form(){
    
/*
 * Connect to database
 */

$mysqli = connect_me();

/*
 * Create prepared sql statement
 */
    
    $sql = "SELECT * FROM settings";

    /*
    * Pass the query to mysql and retrieve the requested row.
    */    
    
$result = $mysqli->query($sql);

$row = $result->fetch_assoc();

if($result->num_rows > 0){
$organization = $row['org_name'];
$department = $row['department'];
$update_insert = 1;
}

else{
    $organization = "";
    $department = "";
    $update_insert = 0;
}

printf("            <form name=\"settings\" method=\"post\" action=\"");
printf(htmlspecialchars($_SERVER['PHP_SELF']));
printf("\">\n");
printf("            Organization Name: <input type=\"text\" name=\"organization\" value=\"%s\">\n", $organization);
printf("            &nbsp; &nbsp; Department: <input type=\"text\" name=\"department\" value=\"%s\">\n", $department);
printf("            <input type=\"hidden\" name=\"insert_update\" value=\"%s\">\n", $update_insert);
printf("            <br>\n<br>\n<input type=\"submit\" name=\"settings-submit\" value=\"Update Settings\">\n<br>\n<br>\n");
printf("            </form>\n");

$result->free();
mysqli_close($mysqli);

}

function settings(){

$insert_update = $_POST['insert_update'];
$organization_name = $_POST['organization'];
$department = $_POST['department'];
    
/*
 * Connect to database
 */

$mysqli = connect_me();



/*
 * Create prepared sql statement, make it either an insert or an
 * update depending on the $insert_update variable
 */
    if($insert_update == 0){
    $sql = $mysqli->prepare("INSERT INTO settings (org_name, department) values (?, ?)");
    $sql->bind_param("ss", $organization_name, $department);
    }
    else{
        $sql = $mysqli->prepare("UPDATE settings SET org_name=?, department=?");
        $sql->bind_param("ss", $organization_name, $department);
    }

/*
 * Execute the sql statement.
 */
    
       if($sql->execute()){
       printf("settings updated<br>\n");           
       } else {
           printf("Well, this is embarassing - update of seetings failed<br>\n");
       }
       
       
       mysqli_close($mysqli);
    
}

function base_report(){
    
/*
 * Connect to Mysql
 */    
    
    $mysqli = connect_me();
    
$sqla = "SELECT * FROM U_ops WHERE log_name = '" . $_SESSION['username'] . "'";
$resultsa = $mysqli->query($sqla);
$rowsa = $resultsa->fetch_assoc();

$priv_level = $rowsa['priv_level'];

    if($priv_level == "1"){
        $priv = 1;
    } elseif ($priv_level == "2") {
        $priv = 2;
    } else {
        $priv = 3;
    }

    if($priv == 3){
        $sqlb = "SELECT * FROM U_ops WHERE log_name = '" . $_SESSION['username'] . "'";
        $resultsb = $mysqli->query($sqlb);
        
        if($resultsb->num_rows > 0){
            $rowsb = $resultsb->fetch_assoc();
            $instructor_id = $rowsb['U_index'];
            $resultsb->free();
        }
        $sql = "SELECT * FROM classes WHERE inst_index = '" . $instructor_id . "' ORDER BY program";
    } else {
        $sql = "SELECT * FROM classes ORDER BY program";
    }
    
    //$sql = "SELECT * FROM classes ORDER BY program";
    $results = $mysqli->query($sql);
    
    printf("    <b>Choose Course and report data</b>\n");
    printf("        <br>\n");
    printf("        <hr>\n");
    
    printf("        <form name=\"choose_course_rep\" method=\"post\" action=\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");
    printf("        <input type=\"hidden\" name=\"routine\" value=\"to_file_format\">\n");
    printf("        <input type=\"hidden\" name=\"row_count\" value=\"%s\">\n", mysqli_num_rows($results));
    
    printf("        <table class=\"standard\" cellpadding=\"8\">\n");
    printf("            <tr>\n");
    printf("                <th>Course</th><th>Report Beginning Date</th><th>Form</th><th>Include in Report</th>\n");
    printf("            </tr>\n");
    printf("            <tr>\n");
    
    
    
    while($rows = $results->fetch_assoc()){
        
        //$from_date = $rows['start_date_month'] . "/" . $rows['start_date_day'] . "/" . $rows['start_date_yr'];
        $from_date = $rows['start_date_day'] . "/" . $rows['start_date_month'] . "/" . $rows['start_date_yr'];
        $to_date = $rows['end_date_month'] . "/" . $rows['end_date_day'] . "/" . $rows['end_date_yr'];
        
        $replaced_from = str_replace('/', '-', $from_date);
        $startingDate = date('Y-m-d', strtotime($replaced_from));
        $parseMe = beg_end_of_week(0, $startingDate);
        
        //$all_mondays = mondays_in_range(date('Y-m-d',strtotime($from_date)), date('Y-m-d', strtotime($to_date)));
        $all_mondays = mondays_in_range($parseMe[0], date('Y-m-d', strtotime($to_date)));
        
        printf("            <td>\n");
        printf("                %s\n", $rows['program']);
        printf("            </td>\n");
                
        printf("                <input type=\"hidden\" name=\"class_%s\" value=\"%s\">\n", $rows['prog_index'], $rows['prog_index']);
        printf("            <td>\n");
        printf("                <select name=\"course_date_%s\">\n", $rows['prog_index']);
        
        $x = 0;
            while($x < count($all_mondays)){
                $satsun = beg_end_of_week(0, $all_mondays[$x]);
                printf("                    <option value=\"%s\"> Week Beginning: %s </option>\n", $satsun[0], $satsun[0]);
            $x++;
        }
        printf("                </select>\n");        
        printf("            </td>\n");
        printf("            <td>\n");
        printf("                Long: <input type=\"radio\" name=\"course_form_%s\" value=\"1\" checked>\n", $rows['prog_index']);
        printf("                Short: <input type=\"radio\" name=\"course_form_%s\" value=\"2\">\n", $rows['prog_index']);
        printf("            </td>\n");
        printf("            <td>\n");
        printf("                Yes: <input type=\"radio\" name=\"course_include_%s\" value=\"1\" checked>\n", $rows['prog_index']);
        printf("                No: <input type=\"radio\" name=\"course_include_%s\" value=\"2\">\n", $rows['prog_index']);
        printf("            </td>\n");
        printf("        </tr>\n");
    }
        printf("        <tr>\n");
        printf("            <td>\n");
        printf("                <input type=\"submit\" name=\"do_file_format\" value=\"Next\">\n");
        printf("            </td>\n");
        printf("        </tr>\n");
        printf("    </table>\n");
    printf("        </form>\n");
    
    $results->free();
    $resultsa->free();
    //$resultsb->free();
    mysqli_close($mysqli);
}

function select_file_format(){
/*    foreach ($_POST as $key => $value){
    printf("<br>\n<br>\n");
    printf("Key: %s ", $key);
    printf("&nbsp; &nbsp;Value: %s\n", $value);
    printf("<br>\n<br>\n");
    } */ 
    
//echo "User = " . $_SESSION['username'] . "<br>\n";

    $mysqli = connect_me();
    $sql = "SELECT prog_index FROM classes";
    $results = $mysqli->query($sql);


$sqlb = "SELECT * FROM U_ops WHERE log_name = '" . $_SESSION['username'] . "'";

$resultsb = $mysqli->query($sqlb);
$rowsb = $resultsb->fetch_assoc();
$inst_number = $rowsb['U_index'];
$priv_level = $rowsb['priv_level'];

    if($priv_level == "1"){
        $priv = 1;
    } elseif ($priv_level == "2"){
        $priv = 2;
    } else {
        $priv = 3;
    }
    
    if($priv == 3){
        $sql = "SELECT prog_index FROM classes WHERE inst_index = '" . $inst_number . "'";
        $results = $mysqli->query($sql);
        $rows = $results->fetch_assoc();
        $instructor_id = $rows['prog_index'];
    } else {
        $sql = "SELECT prog_index FROM classes";
        $results = $mysqli->query($sql);
    }

mysqli_data_seek($results, 0);
    
while($rows = $results->fetch_assoc()){
    $session_variable = sprintf("query_fodder_%s", $rows['prog_index']);
    $_SESSION[$session_variable] = "";
}

mysqli_data_seek($results, 0);

    while($rows = $results->fetch_assoc()){
        $prog_ids[] = $rows['prog_index'];
        $query_1 = sprintf("course_date_%s", $rows['prog_index']);
        $query_2 = sprintf("course_form_%s", $rows['prog_index']);
        $query_3 = sprintf("course_include_%s", $rows['prog_index']);
        $query_4 = sprintf("class_%s", $rows['prog_index']);
        
        $session_variable = sprintf("query_fodder_%s", $rows['prog_index']);
        $_SESSION[$session_variable][] = $_POST[$query_1];
        $_SESSION[$session_variable][] = $_POST[$query_2];
        $_SESSION[$session_variable][] = $_POST[$query_3];
        $_SESSION[$session_variable][] = $_POST[$query_4];
    }
    
    
/*
 * Load all of the values submitted from the report request form into
 * a session variable, to be used in the report generating stage
 * this is (slightly) more secure than passing it in a hidden
 * input field.
 */    
    
$num_rows = mysqli_num_rows($results);
$x = 0;
// initialise the session array 'query_fodder to empty, just in case.
$_SESSION['query_fodder'] = "";

//$max = $_POST['row_count'];


$max = $num_rows;

/* while($x < $max){
    $query_fodder = sprintf("query_fodder_%s", $x);
    $to_print_1 = sprintf("course_date_%s", $prog_ids[$x]);
    echo "To Print 1 = " . $to_print_1 . "<br>\n";
    $date_to_query = $_POST[$to_print_1][0];
    echo "Date to query = " . $date_to_query . "<br>\n";
    $_SESSION['query_fodder'][] = $date_to_query;
    $to_print_2 = sprintf("course_form_%s", $prog_ids[$x]);
    $course_form = $_POST[$to_print_2][0];
    $_SESSION['query_fodder'][] = $course_form;
    $to_print_3 = sprintf("course_include_%s", $prog_ids[$x]);
    $include = $_POST[$to_print_3][0];
    $_SESSION['query_fodder'][] = $include;
    $to_print_4 = sprintf("class_%s", $prog_ids[$x]);
    $class = $_POST[$to_print_4][0];
    $_SESSION['query_fodder'][] = $class;
    echo "Class = " . $class . "<br>\n";
$x++;
}  */  
    
    printf("        <table class=\"noborder\" cellpadding=\"8\">\n");
    printf("            <tr>\n");
    printf("                <th>Select Format</th>\n");
    printf("            </tr>\n");

    printf("        <form name=\"choose_course_rep\" method=\"post\" action=\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");
    printf("        <input type=\"hidden\" name=\"routine\" value=\"last_stage\">\n");
    printf("        <input type=\"hidden\" name=\"row_count\" value=\"%s\">\n", $_POST['row_count']);
    printf("            <td>\n");
    printf("                <select name=\"file_format\">\n");
    printf("                    <option value=\"1\"> Docx </option>\n");
    //printf("                    <option value=\"2\"> Pdf </option>\n");
    printf("                    <option value=\"3\"> HTML </option>\n");
    printf("                </select>\n");
    printf("            </td>\n");
    printf("            <td>\n");
    printf("                <input type=\"submit\" name=\"last_stage\" value=\"Get Report\">\n");
    printf("            </td>\n");
    
    printf("        </form>\n");
    printf("        </table>\n"); 
    
/*foreach($_SESSION as $key => $value) 
{ 
     echo " " . $key . " = ";
     printf("<pre>\n");
     print_r($value);
     printf("</pre>\n");
}*/      

$results->free();
$resultsb->free();
mysqli_close($mysqli);

}

function send_reports(){
    //$query_fodder = array_chunk($_SESSION['query_fodder'], 4);
    
    //$_SESSION['query_fodder'] = $query_fodder;
    
    if($_POST['file_format'] == 1){
        $redirect = sprintf("location: doc_reports.php?row_count=%s", $_POST['row_count']);
        header($redirect);
    } elseif ($_POST['file_format'] == 2){
        $redirect = sprintf("location: pdf_reports.php?row_count=%s", $_POST['row_count']);
        header($redirect);
    } else {
        $redirect = sprintf("location: html_reports.php?row_count=%s", $_POST['row_count']);
        header($redirect);
    }
}

function report_by_sponsor(){

/* Connect to database */
    
    $mysqli = connect_me();

/*------------------------------------------------------------------------------*/
/* Grab all the class information from the classes table, this is really just   */
/* to grab the dates so we can make a drop down list in order to choose from    */
/* and to dates in order to get reports between two specific dates.             */
/*------------------------------------------------------------------------------*/
    
    $sqla = "SELECT * FROM sponsor";
    $resulta = mysqli_query($mysqli, $sqla);
    
/*-----------------------------------------------------------*/
/* Get SPonsor name and sponsor index to pass on to the      */
/* next page, which gets the desired dates to generate a     */
/* report for.                                               */
/*-----------------------------------------------------------*/
        printf("                 <form name=\"sel_spons\" method=\"post\" action=\"");
        printf(htmlspecialchars($_SERVER['PHP_SELF']));
        printf("\">\n");
        printf("                 <input type=\"hidden\" name=\"routine\" value=\"by_spons\">\n");
        printf("            <table class=\"leftnoborder\" cellpadding=\"3\">\n");
        printf("                       <tr>\n");
        printf("                            <td class=\"right\">Sponsor:</td>\n");
        printf("                                <td>\n");
        printf("                                    <select name=\"sponsor\">\n");
        
            while($rowsa = $resulta->fetch_assoc()){
        printf("                                    <option value=\"%s\">%s</option>\n", $rowsa['sponsor_index'], $rowsa['sponsor']);
            }
        printf("                                    </select>\n");
        printf("                                </td>\n");
        printf("                        </tr>\n");
        printf("            </table>\n");
        printf("                    <input type=\"submit\" name=\"by_spons\" value=\"next\">\n");
        printf("                    </form>\n");
        
    $resulta->free();
    mysqli_close($mysqli);
    
}

function report_by_sponsor_sel_dates(){

/* Connect to database */
 
// The following four lines is for bug shooting...    
//        foreach ($_GET as $key => $value) {
//        echo "Key = " . $key;
//        echo " Value = " . $value . "<br>\n";
//    }        
    
    
    $mysqli = connect_me();

    $username = $_SESSION['username'];
    
/*---------------------------------------------------*/
/* Generate sql query - fetch term dates -           */
/*---------------------------------------------------*/

    $sqla = "SELECT * FROM term";
    $resultsa = mysqli_query($mysqli, $sqla);

/*---------------------------------------------------*/
/* Generate sql query - fetch userid from username - */
/*---------------------------------------------------*/
    
    $sqlb = "SELECT * FROM U_ops WHERE log_name = '" . $username . "' ";
    $resultsb = $mysqli->query($sqlb);
    $rowsb = $resultsb->fetch_assoc(); 
    
    $userid = $rowsb['U_index'];
    
    $sponsor = $_POST['sponsor'];
    
        printf("                 <form name=\"sel_spon_term\" method=\"post\" action=\"");
        printf(htmlspecialchars($_SERVER['PHP_SELF']));
        printf("\">\n");
        printf("                 <input type=\"hidden\" name=\"routine\" value=\"by_spons_term\">\n");
        printf("                 <input type=\"hidden\" name=\"sponsor\" value=\"%s\">\n", $sponsor);
        printf("                <input type=\"hidden\" name=\"userid\" value=\"%s\">\n", $userid);
        printf("            <table class=\"leftnoborder\" cellpadding=\"3\">\n");
        printf("                       <tr>\n");
        printf("                            <td class=\"right\">Select Term for report:</td>\n");
        printf("                                <td>\n");
        printf("                                    <select name=\"spons_term\">\n");
        
            while($rowsa = $resultsa->fetch_assoc()){
        printf("                                    <option value=\"%s\">%s</option>\n", $rowsa['date_index'], $rowsa['term_start']);
            }
        printf("                                    </select>\n");
        printf("                                </td>\n");
        printf("                        </tr>\n");
        printf("            </table>\n");
        printf("                    <input type=\"submit\" name=\"by_spons_term\" value=\"next\">\n");
        printf("                    </form>\n");
        
    $resultsa->free();
    $resultsb->free();
    
    mysqli_close($mysqli);    
    
    
}

function report_by_sponsor_sel_weeks(){
    
// The following four lines is for bug shooting...    
//        foreach ($_GET as $key => $value) {
//        echo "Key = " . $key;
//        echo " Value = " . $value . "<br>\n";
//    }        

/* Connect to database */
    
    $mysqli = connect_me();

/*----------------------------------------------------------*/
/* Take posted variables and assign them to local variables */
/*----------------------------------------------------------*/
    
    $sponsor = $_POST['sponsor'];
    $userid = $_POST['userid'];
    $term_number = $_POST['spons_term'];

    $sqla = "SELECT * FROM term WHERE date_index = '" . $term_number . "'";
    $resultsa = mysqli_query($mysqli, $sqla);
    $rowsa = $resultsa->fetch_assoc();
    
/*----------------------------------------------------------*/
/* Retrieve term start date and term end date, convert      */
/* into epoch, store in variables to be used later.         */
/*----------------------------------------------------------*/

    $from_epoch = to_new_date($rowsa['term_start'], "dmy", "epoch", "/", "");
    $to_epoch = to_new_date($rowsa['term_end'], "dmy", "epoch", "/", "");
    
/*----------------------------------------------------------*/
/* Take start and end dates and through some black magic    */
/* wizardry, convert them into an array containing a list   */
/* list of all mondays in the term, then get the sunday of  */
/* of each week, displaying it as a selection...            */
/*----------------------------------------------------------*/
    
    $new_from = str_replace('/', '-', $rowsa['term_start']);
    $new_to = str_replace('/', '-', $rowsa['term_end']);
    
    $start_here = date('Y-m-d', strtotime($new_from));
    $beg_end_of_week = beg_end_of_week(0, $start_here);
    
    $all_modays = mondays_in_range($beg_end_of_week[0], date('Y-m-d', strtotime($new_to)));
    
/*----------------------------------------------*/
/* Take the selected term, divide it into weeks */
/* Then display the first Sunday of each week   */
/* in a select/option drop down.                */
/* Also display a select/option drop down for   */
/* for choosing report format.                  */
/*----------------------------------------------*/

printf("<form name=\"choose_date_and_format\" method=\"post\" action=\"");
printf(htmlspecialchars($_SERVER['PHP_SELF']));
printf("\">\n");    
printf("    <input type=\"hidden\" name=\"routine\" value=\"spons_report_format\">\n");
printf("    <input type=\"hidden\" name=\"sponsor\" value=\"%s\">\n", $sponsor);
printf("    <input type=\"hidden\" name=\"userid\" value=\"%s\">\n", $userid);
printf("    <input type=\"hidden\" name=\"termno\" value=\"%s\">\n", $term_number);
printf("        <table class=\"leftnoborder\" cellpadding=\"3\">\n");
printf("            <tr>\n");
printf("                <td>Choose Week:</td>\n");
printf("                <td>\n");
printf("                    <select name=\"date\">\n");
$x = 0;
    while($x < count($all_modays)){
        $sunday_now = date('Y-m-d', strtotime('last Sunday', to_new_date($all_modays[$x], "ymd", "epoch", "-", "")));
        //$satsun = beg_end_of_week(0, $all_modays[$x]);
        printf("                    <option value=\"%s\">Week Beginning: %s </option>\n", $sunday_now, $sunday_now);
        $x++;
    }
printf("                    </select>\n");
printf("                </td>\n");
printf("            </tr>\n");
printf("                <td>Report Format:</td>\n");
printf("                <td>\n");
printf("                    <select name=\"report_format\">\n");
printf("                        <option value=\"1\">Docx</option>\n");
printf("                        <option value=\"3\">HTML</option>\n");
printf("                    </select>\n");
printf("                </td>\n");
printf("            </tr>\n");
printf("            <tr>\n");
printf("                <td>\n");
printf("                    <input type=\"submit\" name=\"format\" value=\"Next\">\n");
printf("                </td>\n");
printf("            </tr>\n");
printf("        </table>\n");
    
$resultsa->free();
mysqli_close($mysqli);
    
}

function spons_send_reports(){
    
    if($_POST['report_format'] == 1){
        $redirect = sprintf("location: spons_doc_reports.php?date=%s&sponsor=%s&userid=%s&termno=%s", $_POST['date'], $_POST['sponsor'], $_POST['userid'], $_POST['termno']);
        header($redirect);
    } elseif ($_POST['report_format'] == 2){
        $redirect = sprintf("location: spons_pdf_reports.php?date=%s&sponsor=%s&userid=%s&termno=%s", $_POST['date'], $_POST['sponsor'], $_POST['userid'], $_POST['termno']);
        header($redirect);
    } else {
        $redirect = sprintf("location: report_by_sponsor.php?date=%s&sponsor=%s&userid=%s&termno=%s", $_POST['date'], $_POST['sponsor'], $_POST['userid'], $_POST['termno']);
        header($redirect);
    }
}

function logout(){
    session_start();
    session_unset();
    session_destroy();
    header("Refresh: 1;url=login_succeeded.php");
    exit();
}

function choose_attend_form(){
    
/*
 * Connect to database
 */
    
        foreach ($_POST as $key => $value) {
        echo "Key = " . $key;
        echo "Value = " . $value . "<br>\n";
    }
    
 $mysqli = connect_me();
 
 $sqla = "SELECT * FROM U_ops WHERE log_name = '" . $_SESSION['username'] . "'";
 $resultsa = $mysqli->query($sqla);
 $rowsa = $resultsa->fetch_assoc();
 
 $priv_lvel = $rowsa['priv_level'];
 
    if($priv_lvel == "1"){
        $priv = 1;
    }
    elseif ($priv_lvel == "2") {
        $priv = 2;    
    }
    else {
        $priv = 3;
    }
    if($priv == 3){
        $sql = "SELECT * FROM U_ops WHERE log_name = '" . $_SESSION['username'] . "'";
        $result = mysqli_query($mysqli, $sql);
        
    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        $instructor_id = $row['U_index'];
        }
        $sqli = "SELECT * FROM classes WHERE inst_index = " . $instructor_id . " ORDER BY program"; 
    } 
    else {
        $sqli = "SELECT * FROM classes ORDER BY program";
    }
    
    $resultsi = $mysqli->query($sqli);
    
    printf("        <b>Course Attendance</b>\n");
    printf("        <br>\n<br>\n");
    
    printf("            <table class=\"standard\" cellpadding=\"8\" cellspacing=\"0\">\n");
    printf("                <tr>\n");
    printf("                <th>Course</th><th>Course Description</th><th>Status</th><th></th>\n");
    if($resultsi->num_rows > 0){
        while($rowsi = mysqli_fetch_array($resultsi)){
            printf("         <tr>\n");
            printf("                <td>%s</td>\n", $rowsi['program']);
            printf("                <td>%s</td>\n", $rowsi['prog_id']);
            if($rowsi['course_status'] == 0){
                printf("                <td><img src=\"./images/checkmark.png\" width=\"32\" height=\"32\"></td>\n");
            }
            elseif($rowsi['course_status'] == 1){
                printf("                <td><img src=\"./images/stop.png\" width=\"32\" height=\"32\"></td>\n");
            }
            else {
                printf("                <td><img src=\"./images/completed.png\" width=\"32\" height=\"32\"></td>\n");
            }
            printf("                <td>\n");
            printf("                    <form name=\"updateattend\" method=\"post\" action=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("\">\n");
            printf("                    <input type=\"hidden\" name=\"routine\" value=\"attend\">\n");
            printf("                    <input type=\"hidden\" name=\"course_id\" value=\"%s\">\n", $rowsi['prog_index']);
            printf("                    <input type=\"submit\" name=\"updattend\" value=\"Enter Attendance\">\n");
            printf("                    </form>\n");
            printf("                </td>\n");
            printf("         </tr>\n");
        }
    }
    printf("        </table>\n");
    
}

function enter_attend_form(){

/*------------------------------------------------------------------------------*/
/* Uncomment the following lines of code to troubleshoot form submissions, they */
/* print out the key/value pairs above the form.                                */
/*------------------------------------------------------------------------------*/
    
/*        foreach ($_GET as $key => $value) {
        echo "Key = " . $key;
        echo " Value = " . $value . "<br>\n";
    }    

foreach ($_POST as $key => $value) {
        echo "Key = " . $key;
        echo " Value = " . $value . "<br>\n";
    } */   
/*------------------------------------------------------------------------------*/
/* End troubleshooting code                                                     */
/*------------------------------------------------------------------------------*/
    
/*
 * Connect to database
 */
    
 $mysqli = connect_me();   

/*------------------------------------------------------------------------------*/
/* Short and long weekday names for calendar display                            */
/* Stored as arrays.                                                            */
/*------------------------------------------------------------------------------*/
 
 $daysofweek = array( "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
 $days_oftheweek = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");

/*------------------------------------------------------------------------------*/
/* Check and see if the form data is GET (inline, comes from a week change.     */
/* or a post - comes when the form is initially entered from another menu.      */
/*------------------------------------------------------------------------------*/
 
 
 if(isset($_GET['course_id'])){
     $course_id = $_GET['course_id'];
 } else { 
    $course_id = $_POST['course_id'];
 }
 if(isset($_GET['weeks'])){
     $weeks = $_GET['weeks'];
 }

 
/*------------------------------------------------------------------------------*/
/* Get all the class data associated with the chosen class.                     */
/*------------------------------------------------------------------------------*/
 
$sql = "SELECT * FROM classes WHERE prog_index = " . $course_id . "";

    $result = mysqli_query($mysqli, $sql);
    $row = $result->fetch_assoc();
    $instructor_id = $row['inst_index'];
    $from_date = $row['start_date_day'] . "/" . $row['start_date_month'] . "/" . $row['start_date_yr'];
    $to_date = $row['end_date_day'] . "/" . $row['end_date_month'] . "/" . $row['end_date_yr'];
    $num_of_course_weeks = numofweeks($from_date, $to_date);

/*------------------------------------------------------------------------------*/
/* Retrieve and format the date when attendance records for this class were     */
/* last updated.                                                                */
/*------------------------------------------------------------------------------*/
    
    if($row['last_updated'] == 0){
        $lastUpdate = "N/C";
    } else {
        $lastUpdate = date('D, M j, Y - g:i a', $row['last_updated']);
    }
/*------------------------------------------------------------------------------*/
/* Get the name of the instructor for this course.                              */
/*------------------------------------------------------------------------------*/
    
$sqli = "SELECT * FROM U_ops WHERE U_index = " . $instructor_id . "";
    
    $resulti = mysqli_query($mysqli, $sqli);
    $rowsi = $resulti->fetch_assoc();

/*------------------------------------------------------------------------------*/
/* OK - this was fun - an inner join. Get enrollment records for this course    */
/* Student data for students enrolled in this class.                            */
/* Then retrieve the attendance codes                                           */
/*------------------------------------------------------------------------------*/
    
$sqlj = "SELECT * FROM enrolled INNER JOIN student ON (enrolled.stu_index = student.stu_index AND enrolled.prog_index = " . $course_id . " AND enrolled.status = 0) ORDER by student.Stu_L_name, student.Stu_F_name";
    $resultj = mysqli_query($mysqli, $sqlj);  
$sqlk = "SELECT * FROM attend_code";
    $resultk = mysqli_query($mysqli, $sqlk);

/*------------------------------------------------------------------------------*/
/* This is a convoluted mess, I will work on it once things have calmed down    */
/* a bit and clean this up.                                                     */
/* These lines of code take given dates and format them correctly, I need to    */
/* really figure this out so that it is easier to understand, I am also         */
/* sure there has to be a much better way of doing this.                        */
/*------------------------------------------------------------------------------*/
    
$running_time = $num_of_course_weeks+1;
$dated_from = str_replace('/', '-', $from_date);
$date_to_parse = date('Y-m-d', strtotime($dated_from));
$dated_to = str_replace('/', '-', $to_date);

$startingDate = date('Y-m-d', strtotime($date_to_parse));
$endingDate = date('Y-m-d', strtotime($dated_to));

/*------------------------------------------------------------------------------*/
/* The following lines are a convoluted mess which needs deperately to be       */
/* cleaned up.                                                                  */
/* These lines take the week the person entering data wishes to enter data      */
/* for and formats them correctly for our purposes.                             */
/*------------------------------------------------------------------------------*/

$parseMe = beg_end_of_week(0, $startingDate);
$allmondays = mondays_in_range($parseMe[0], $endingDate);

//echo "<pre>\n";
//print_r($allmondays);
//echo "</pre>\n";
//echo "Number of Mondays: " . count($allmondays) . "<br>\n";

//$xx = 0;
//for($xx = 0; $xx < count($allmondays);$xx++){
//    $sunday_now = date('Y-m-d', strtotime('last Sunday', to_new_date($allmondays[$xx], "ymd", "epoch", "-", "")));
//    echo "Monday = " . $allmondays[$xx] . " Sunday = " . $sunday_now . "<br>\n";
//}

if (isset($weeks)) {
    $from_sunday = date('d', strtotime($weeks));
    $posted_day = strtotime($weeks);
    $first = $weeks;
    $last_minus = beg_end_of_week(0, $weeks);
    //$last_minus = date('Y-m-d', strtotime('last Sunday', to_new_date($weeks, "ymd", "epoch", "-", "")));
    $last = $last_minus[1];
    $all_dates = date_range($first, $last_minus[1]); 
} elseif(is_null($_POST['weeks'])){
    $from_sunday = date('d', $parseMe[0]);
    $posted_day = strtotime($parseMe[0]);
    $first_minus = $parseMe[0];
    $first = $parseMe[0];
    $last_minus = $parseMe[0];
    $last = $parseMe[1];
    $from_saturday_one = beg_end_of_week(0, $allmondays[0]);
    $to_saturday = date('d', strtotime($from_saturday_one[1]));
    $all_dates = date_range($first, $last);
    $_POST['weeks'] = beg_end_of_week(0, $allmondays[0]);
} else {
    $from_sunday = date('d', strtotime($_GET['weeks']));
    $posted_day = strtotime($_GET['weeks']);
    $first = $_GET['weeks'];
    $last_minus = beg_end_of_week(0, $_POST['weeks']);
    $last = $last_minus[1];
    $all_dates = date_range($first, $last_minus[1]);
}

/*------------------------------------------------------------------------------*/
/* Start actually displaying the table and forms                                */
/*------------------------------------------------------------------------------*/

printf("            <table class=\"standard\" cellpadding=\"4\" cellspacing=\"2\" width=\"95%%\">\n");
printf("                <tr>\n");
printf("                <td colspan=\"15\">\n");
printf("                    <table class=\"noborder\" cellpadding=\"1\" cellspacing=\"1\" width=\"85%%\">\n");
printf("                    <tr>\n");

/*------------------------------------------------------------------------------*/
/*  Test if there is an instructor for this course, if no: Print out 'TBD'      */
/*  (To Be Decided), Else print their name                                      */
/*  We also print out the course name here                                      */ 
/*------------------------------------------------------------------------------*/

    if(is_null($rowsi)){
        printf("                    <td><b>%s</b></td><td><b><p align=\"center\">Instructor: TBD</p></b></td>\n", $row['program']);
    } else {
        printf("                    <td><b>%s</b></td><td><b><p align=\"center\">Instructor: %s %s</p></b></td>\n", $row['program'], $rowsi['U_F_name'], $rowsi['U_L_name']);
    }
printf("                    </tr>\n");
printf("                    </table>\n");
printf("                </td>\n");
printf("                </tr>\n");
printf("                <tr>\n");
printf("                    <td>");
printf("                    <form id=\"sel_week\" method=\"post\">\n");
printf("                    <input type=\"hidden\" name=\"routine\" value=\"attend\">\n");
printf("                    <input type=\"hidden\" name=\"course_id\" value=\"%s\">\n", $course_id);

/*------------------------------------------------------------------------------*/
/* Print the select/option fields which print out a choice of weeks to enter    */
/* attendance for.                                                              */
/* -------------------                                                          */
/* November 8/2016                                                              */
/* Now use onchange to automatically select the date chosen and reselect the    */
/* form with the selected start date - this seems to be a less confusing option */
/* for some instructors who couldn't figure out the "choose week" button        */
/*------------------------------------------------------------------------------*/

printf("<select name=\"weeks\" onchange=\"location.href='login_succeeded.php?routine=attend&course_id=%s&weeks=' + this.options[this.selectedIndex].value;", $course_id);
printf("\">\n");
$x = 0;
for($x = 0;$x < count($allmondays);$x++){
    $sunday_now = date('Y-m-d', strtotime('last Sunday', to_new_date($allmondays[$x], "ymd", "epoch", "-", "")));
        printf("                    <option value=\"%s\"", $sunday_now);
        if($sunday_now == $_POST['weeks']){                                      // If chosen via submit button
            printf(" selected>Week Beginning: %s</option>\n", $sunday_now); 
        } elseif($sunday_now == $_GET['weeks']) {                                // If chosen by onchange method
            printf(" selected>Week Beginning: %s</option>\n", $sunday_now);         
        } else {
            printf(">Week Beginning: %s</option>\n", $sunday_now);               // Default
        }
      
}

printf("                    </select>\n");
printf("                   </form>\n");
printf("                    </td>\n");
printf("                    <td colspan=\"7\"><b>Week Beginning: %s</b> </td>\n", date('D F d', strtotime($first)));
printf("                </tr>\n");
printf("                <tr>\n");
printf("                <td><b>Student</b></td>\n");

/*------------------------------------------------------------------------------*/
/* Print out sun - sat                                                          */
/*------------------------------------------------------------------------------*/

$x = 0;
    for($x == 0; $x <= 6; $x++){
        printf("                <td bgcolor=\"#cccccc\"><b>%s</b></td>\n", $daysofweek[$x]);
    }
printf("                </tr>\n");
printf("                <tr>\n");
printf("                    <td align=\"left\"><b>Last Updated:</b> %s</td>\n", $lastUpdate);

/*------------------------------------------------------------------------------*/
/* Print out the dates for the current week                                     */
/* In the right place and in the right order. :)                                */
/*------------------------------------------------------------------------------*/

$x = 0;
    for($x = 0;$x < 7;$x++){
        $printable_date = date('d', strtotime($all_dates[$x]));
        printf("                    <td><b>%s</b></td>\n", $printable_date);
    }

printf("                </tr>\n");
printf("                    <form name=\"updateat\" method=\"post\" action=\"");
printf(htmlspecialchars($_SERVER['PHP_SELF']));
printf("\">\n");
printf("                    <input type=\"hidden\" name=\"routine\" value=\"updateat\">\n");
printf("                    <input type=\"hidden\" name=\"course\" value=\"%s\">\n", $course_id);
printf("                    <input type=\"hidden\" name=\"commentDate\" value=\"%s\">\n", $posted_day);
    
printf("                    <input type=\"hidden\" name=\"dateUpdated\" value=\"%s\">\n", time());
printf("                    <input type=\"hidden\" name=\"instIndex\" value=\"%s\">\n", $row['inst_index']);

/*-------------------------------------------------------------------------------*/
/* This next while loop loops through all of the students,printing their         */
/* names IF they are enrolled in a given course, it then seeks attendance        */
/* Information and the days the given course runs, adding it all to the calendar */
/* for the given day.                                                            */
/* Rowsj/resultj is the results of the query for student name/enrollment data.   */
/* We then also add in a text area so the istructor can make comments on a       */
/* student, they are limeted to 500 characters per student per week.             */
/*-------------------------------------------------------------------------------*/

    while($rowsj = $resultj->fetch_assoc()){
        printf("                <tr>\n");
        
        /*------------------------------------------------------------------------------*/
        /* copy the current unix timestamp into a variable for use in dating comments,  */
        /* which are always dated the 1st day of the week (Sunday), but also have       */
        /* a last updated stamp (Which is taken from the $_POST['dateUpdated'] Variable.*/
        /*------------------------------------------------------------------------------*/
                 
        $posted_epoch = strtotime($parseMe[0]);
        $sqlo = "SELECT * FROM inst_comments WHERE class_index = '" . $course_id . "' AND stu_index = '" . $rowsj['stu_index'] . "' AND posted_epoch = '" . $posted_day ."'";
        $resultso = $mysqli->query($sqlo);
        $rowso = $resultso->fetch_assoc();
        
        /*------------------------------------------------------------------------------*/
        /* Provide default text if there is no comment for this individual for this     */
        /* week.                                                                        */
        /*------------------------------------------------------------------------------*/
        
        if(mysqli_num_rows($resultso) > 0){
            $inst_comments = $rowso['comment'];
        } else {
            $inst_comments = "";
        }
        
        /*-----------------------------------------------------------------------------*/
        /* Print comment field and student name                                        */
        /*-----------------------------------------------------------------------------*/
        
        printf("                    <td>\n");
        printf("                    <table class=\"leftnoborder\" cellpadding=\"3\">\n");
        printf("                        <tr>\n");
        printf("                            <td class=\"right\">Comments:</td>\n");
        printf("                            <td class=\"center\">\n");
        printf("                                <textarea name=\"instComment-%s\" rows=\"1\" cols=\"25\" maxlength=\"499\" spellcheck=\"true\">%s</textarea>\n", $rowsj['stu_index'], $inst_comments);
        printf("                            </td>\n");
            printf("                            <td class=\"right\">\n");
            printf("                                %s, %s %s\n", $rowsj['Stu_L_name'], $rowsj['Stu_F_name'], $rowsj['Stu_M_name']);
        
        printf("                            </td>\n");
        printf("                        </tr>\n");
        printf("                    </table>\n");
        printf("                    </td>\n");
        $x = 1;
            for($x = 0; $x < 7;$x++){
                $epoch = strtotime($all_dates[$x]);
                
                /*----------------------------------------------------------------------------------------------*/
                /* Still have to deal with existing attendence lists for a week, after all, if an
                 * instructor/admin/pa goes in to update an attendence record for a student for a week,
                 * they will want to see what that day is set to for that student.
                 *----------------------------------------------------------------------------------------------*/
                $sqll = "SELECT * FROM attend WHERE course_index = " . $course_id . " AND epoch = " . $epoch . " AND stu_index = " . $rowsj['stu_index'];
                //echo "sqll = " . $sqll . "<br>\n";   
                printf("                    <td>\n");
                
                
                /*----------------------------------------------------------------------------------------------
                 * The following short bit of code takes the days of the week from program table column weekdays
                 * explodes it into individual day numbers (1 through 7) then turns those day numbers into
                 * long day names (eg: Tuesday) then loads those day names into an array called $testarray.
                 *  *Note* See the $xz-1 in the $testarray[] = $days_oftheweek[$xz-1]; below?
                 *  If this -1 is not there, the days will always be one day later than they should be.
                 *----------------------------------------------------------------------------------------------*/      
                   
                $counted_days = explode(',', $row['weekday']);
                //print_r($counted_days);       // <- for bug shooting 
                $weekly = 8;
                $xz = 1;
                for($xz = 1;$xz < $weekly;$xz++){
                    foreach($counted_days as $values){
                    //printf("Days = %s<br>\n", $values);
                        if(strcmp($values, $xz) == 0){
                            //printf("XZ = %s - DOW = %s<br>\n", $xz, $days_oftheweek[$xz-1]); //<- bug shooting
                            $testarray[] = $days_oftheweek[$xz-1];
                        }
                    }                
                }
                //printf("<pre>\n");
                //print_r($testarray);         //<- bug shooting    
                //printf("</pre>\n");
                /*----------------------------------------------------------------------------------------------*/
                
                /*---------------------------------------------------------------------------------------------*
                 * Make up an array of dates containing the date of the first sunday ($first) in the given week 
                 * and the last Saturday ($last) of the same given week and feed it into the dateRange 
                 * subroutine, which loads $array_ofdates with a list of long form dates (Ex: 2016-01-01) for
                 * the whole week.
                 *---------------------------------------------------------------------------------------------*/
                $array_ofdates = dateRange($first, $last);
                
                //echo "First = " . $first . "\n";
                
                //printf("<pre>\n");
                //print_r($array_ofdates);         //<- bug shooting    
                //printf("</pre>\n");
                
                /*---------------------------------------------------------------------------------------------*/
                
                /*---------------------------------------------------------------------------------------------*/
                /* Take the loaded $array_ofdates and feed it into a filter using the array $days_oftheweek as
                 * the filter condition, this returns an array of dates and loads it into the array $dates.
                 * 
                 *---------------------------------------------------------------------------------------------*/
                //$testarray = array($days_oftheweek[2], $days_oftheweek[4]);       // <- this was just for testing purposes
                
                $dates = array_filter($array_ofdates, dateFilter($testarray));
                
                /*---------------------------------------------------------------------------------------------*/
                
                /*---------------------------------------------------------------------------------------------*/
                /* Now "There be dragons" as the old seafarer's maps used to say:
                 * I borrowed this bit of code from someone else, and I'm not exactly sure of the reasoning
                 * behind the need, but without it, you can't really deal with the returned values from
                 * the array_filter.
                 * OK, I THINK what is happening, is that through the magic of array_map() $dates is
                 * being passed to function within array_map() which formats the returned values from
                 * the array filter into Year-month-date then stuffs the results into the array called
                 * $closedDates.
                 *---------------------------------------------------------------------------------------------*/
                
                $closedDates = array_map(function ($date){
                    return $date->format('Y-m-d');                    
                    }, $dates);
                
                /*---------------------------------------------------------------------------------------------*/    
                    
                $testarray = "";    //Empty the array, otherwise it just keeps adding copies of the same data onto itself    
                
                
                /*-------------------------------------------------------------------------------------------*/
                /* Check and see if the table attend actually has anything in for the given student, course
                 * and epoch. If a record exists, write the attend_code into a variable for testing in the  
                 * following routine, if not, load a null into the variable
                 *-------------------------------------------------------------------------------------------*/
            
                
                    $resultl = mysqli_query($mysqli, $sqll);
                    if(mysqli_num_rows($resultl)>0){
                        $rowsl = $resultl->fetch_assoc();
                        $code_comp = $rowsl['attend_code'];
                    } else {
                        $code_comp = "";
                    }
                
                /*--------------------------------------------------------------------------------------*/
                /* Is this trip really neccesary?
                 * This next foreach loop tests the weekday against epoch, in order
                 * to print select ONLY in the cell needed
                 *--------------------------------------------------------------------------------------*/
                
                foreach($closedDates as $seldate){
                    $dows = strtotime($seldate);
                    if(strcmp($dows, $epoch) == 0){
                        if($rowsj['status'] !=2 ){
                        printf("                    <select name=\"attendence_%s_%s\">\n", $rowsj['stu_index'], $epoch);
                        } else {
                            printf("");
                        }
                    }
                }

/*-----------------------------------------------------------------------------------------------*/
/* The following routine deals with the contents of each cell for the days in replacement of the */
/* above routine, the new routine does basically the same thing, but also deals with stundents   */
/* who have withdrawn by printing a "w/d" instead of a drop down list.                           */
/*-----------------------------------------------------------------------------------------------*/


/*---------------------------------------------------------------------------------------------*/
/* Ok, it's getting a little convoluted... More nested loops.                                  */
/* Take the time stamps, convert them into epochs, compare them aginst the epoch for the       */
/* course date, if they match then print out an <option value> field, if not, just             */
/* print a blank into the table cell.                                                          */
/* We also take the $code_comp variable and check to see if it matches here, if it does;       */
/* print an option field with a selected in the option, if not; simply print the option field. */
/*---------------------------------------------------------------------------------------------*/

                
foreach($closedDates as $valued){
$dows = strtotime($valued);
if(strcmp($dows, $epoch) == 0){
    
        /*-----------------------------------------------------------------------*/
        /* Check and see if a student is enrolled or not, if they are, check for */
        /* existing attendance data, print the <option> field, with a selected   */
        /* if there already is data for that day, if no data, just print the     */
        /* <option field without a selected tag. If the student is withdrawn,    */
        /* don't print the <option> tag, print w/d instead.                      */
        /* If the course is not running on a given day just print a NULL in the  */
        /* <td> field.                                                           */
        /*-----------------------------------------------------------------------*/

	if($rowsj['status'] != 2){
            
                /*--------------------------------------------------------------*/
                /* actually run the query which fetches the codes for attendance*/
                /*--------------------------------------------------------------*/
                

		while($rowsk = $resultk->fetch_assoc()){

			if(strcmp($rowsk['attend_index'], $code_comp) == 0){
                
				printf("                        <option value=\"%s\" selected> %s </option>\n", $rowsk['attend_index'], $rowsk['attend_code']);
                                
                		} else {
                		printf("                        <option value=\"%s\"> %s </option>\n", $rowsk['attend_index'], $rowsk['attend_code']);
                		}
			}
                    } elseif($rowsj['status'] == 2) {
                        printf("                    w/d\n");
                    } else {
                        printf("");
                    }
}
}
                /*----------------------------------------------------------------------------------------------*/
                /* Again, is this trip really neccesary? I should be able to compress these three foreach loops
                 * into one, however, at this time it works, so I will leave it as is.
                 *----------------------------------------------------------------------------------------------*/ 
                    foreach ($closedDates as $end_date){
                        $closeit = strtotime($end_date);
                        if(strcmp($closeit, $epoch) == 0){
                            if($rowsj['status'] != 2){
                                printf("                    </select>\n");
                            }
                            else {
                                printf("");
                            }
                        }
                    }
                
                printf("                    </td>\n");
                
                mysqli_data_seek($resultk, 0);      // <- reset query to first record, else only the first cell has anything in it.
                mysqli_data_seek($resultl, 0);
            }
        printf("                </tr>\n");
    }

printf("                <tr>\n");
printf("                    <td colspan=\"8\">\n");
printf("                    <center><input type=\"submit\" name=\"updateat\" value=\"Submit\"></center>\n");
printf("                    </td>\n");
printf("                </tr>\n");
printf("                    </form>\n");

printf("            </table>\n");

mysqli_close($mysqli);

}

function update_attend(){
    
/*
 * Connect to database
 */
    
 $mysqli = connect_me();    
    
/*------------------------------------------------------------------------------*/
/* The following bit of kit (which is left commented out unless needed) is      */
/* used to troubleshoot data passing into the routine, it takes all of the      */
/* key => value pairs from the $_POST array and prints them out neatly so       */
/* issues involving data being passed around can be spotted.                    */
/*------------------------------------------------------------------------------*/
 
/*    foreach ($_POST as $key => $value){
    printf("<br>\n<br>\n");
    printf("Key: %s ", $key);
    printf("&nbsp; &nbsp;Value: %s\n", $value);
    printf("<br>\n<br>\n");
    }
*/
    $date_entered = $_POST['dateUpdated'];
    $inst_index = $_POST['instIndex'];
    $course = $_POST['course'];

/*------------------------------------------------------------------------------*/
/* Wow, another regex! this is is getting serious!;) Added in after the         */
/* regex below it, this one extracs the student index from the comment variable */
/* which is named "instComment-## - the routine explodes the name, giving us    */
/* access to the number at the end.                                             */
/* probably an easier, better and simpler way to do this, I will come up with   */
/* something during code cleanup and re-writes.                                 */
/*------------------------------------------------------------------------------*/
    
    $comm = '/^instComment-(\d*)$/';
    $vals = Array();
    $comment = Array();
    foreach($_POST as $key => $valu){
        $matches = Array();
        $comment = explode('-', $key);
            if(preg_match($comm, $key, $matches)){
                //printf("<pre>\n");
                $stu_index = $comment[1];
                //printf("Stu_id = %s\n", $comment[1]);
                //printf("</pre>\n");

            $posted_day = $_POST['commentDate'];
            
/*------------------------------------------------------------------------------*/
/* Either insert a new comment or update an old one, in either case, the        */
/* date updated field is also changed, showing when the last update to this     */
/* particular record was.                                                       */
/*------------------------------------------------------------------------------*/            
            
            $sqle = "SELECT * FROM inst_comments WHERE class_index = '" . $course . "' AND stu_index = '" . $stu_index . "' AND posted_epoch = '" . $posted_day . "'";
            $resultse = $mysqli->query($sqle);
            if(mysqli_num_rows($resultse) > 0){
                $rowse = $resultse->fetch_assoc();
                $comment_index = $rowse['comment_index'];
            } else {
                $sqlf = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'attend_pdt' AND TABLE_NAME = 'inst_comments'";
                $resultsf = $mysqli->query($sqlf);
                $rowsf = $resultsf->fetch_assoc();
                $comment_index = $rowsf['AUTO_INCREMENT'];
            }
            
                    $comment_var = sprintf("instComment-%s", $comment[1]);
                    $comment = $_POST[$comment_var];
        
                    $sqld = $mysqli->prepare("INSERT INTO inst_comments (comment_index, class_index, instructor_index, stu_index, posted_epoch, date_updated, comment)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        date_updated = IF(comment<>VALUES(comment), VALUES(date_updated), date_updated),
                        comment = IF(comment<>VALUES(comment), VALUES(comment), comment)              
                        ");

                            
                            $sqld->bind_param("sssssss", $comment_index, $course, $inst_index,  $stu_index, $posted_day, $date_entered, $comment);
                            $test_return = $sqld->execute();
        
                            if(!FALSE === $test_return){
                            //Nothing good to report, carry on.
                            } else {
                                printf("Oops, unable to update student comments - tell admin\n");
                            }            
                    }
            
    }
    
    /*
     * Ok. now comes another fun part </sarc>
     * attendance records.
     *
     * Argh! Yet another regex! 
     * Again, I hate doing regex....
     * Oh well, overly complicated, I think, but it worked.
     * For my own future reference:
     * '/^attendence_(\d*)$/ means:
     * /^attendence_ = match only that input with the word "attendence_" in it (from start of line)
     * (\d*) = match any digits which follow attendence_
     * $/ = match to end of line
     * 
     * Thie following routine uses the above regex to extract the key of attendence_##
     * from the key => value pairs, then implode them into an enrollment code:course number
     * then into a complete line to be updated in the student's record via sql query.
     */    
    
    //printf("Course ID = %s<br>\n", $_POST['course']);
    
    $counted_updates = 0;
    
    $exp = '/^attendence_(\d*)$/';
    $values = Array();
    $enrolled = Array();
    foreach($_POST as $key => $val){
        $match = Array();
        $enrolled = explode('_', $key);
        if( preg_match($exp, $key, $match)){
            $values[] = $val;
            $base[] = $enrolled[1];

            
        }
  
/*------------------------------------------------------------------------------*/
/* When it comes to sql query, the idea for this one might be to use            */
/* INSERT ... ON DUPLICATE KEY UPDATE, which means I will need a key            */
/* for update, insert wont need it.                                             */
/*------------------------------------------------------------------------------*/
/* This is what I wound up doing - it works well. Also added some conditional   */
/* statements into the queries, now, they only update if there is actually a    */    
/* change - Be nice if this reduced a bit of hit on the mysql server, not that  */
/* there will be much of a hit.                                                 */
/*------------------------------------------------------------------------------*/
            
        
        if(!empty($enrolled[1])){
            
            
        /*---------------------------------------------------------------------------------------
         * If there is a record already, retrieve it, load the attended_index key in the 
         * variable $attended key, this will be used in the query.
         * If there is no matching record, grab the next auto_increment key
         * from the table and load it into $attended_index instead.
         *---------------------------------------------------------------------------------------*/
            
        $sqla = sprintf("Select * FROM attend WHERE stu_index = %s AND epoch = %s AND course_index = %s", $enrolled[1], $enrolled[2], $_POST['course']);
        $resulta = mysqli_query($mysqli, $sqla);
        if(mysqli_num_rows($resulta) > 0){
        $rowsa = $resulta->fetch_assoc();
        $attended_index = $rowsa['attended_index'];
        
        //printf("(a) Attended Index = %s<br>\n", $attended_index);
        }  else { 
            /*----------------------------------------------------------------------------------------*/
            /* At some point in time, I will figure this query stuff out to the point whee I will
             * be able to construct one monolithic query which does everything I need, however,
             * until then....
             * The next query grabs the auto increment value for the attend table, which I
             * can use if the following query, which inserts or updates a record, needs to
             * insert as opposed to update an existing record, this gives me a row number
             * to use in the insert record (which, oddly enough, will be completely ignored
             * if I am updating an existing record, oh well)
             *----------------------------------------------------------------------------------------*/
        
                //$sqlb = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'pdt_attend' AND TABLE_NAME = 'attend'";
            $sqlb = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'attend_pdt' AND TABLE_NAME = 'attend'";
                $resultb = mysqli_query($mysqli, $sqlb);
                $rowsb = $resultb->fetch_assoc();
                $attended_index = $rowsb['AUTO_INCREMENT'];   
                //printf("(b) Attended Index = %s<br>\n", $attended_index);
                
            } 
            

        
        /*-------------------------------------------------------------------------------------------*/
        /* here comes the INSERT ON DUPLICATE UPDATE query, this was fun (sarcasm there) to
         * get working properly. If there is no record which matches, insert a new one,
         * in which case, the attended_index is not used, if, however, there is a record
         * match, then update it, no insert is done.
         *-------------------------------------------------------------------------------------------*/
        /*----------------------------------------------------------------------*/
        /* added some if statements into the ON DUPLICATE KEY UPDATE section    */
        /* this will update both the attend code AND last updated field ONLY if */
        /* the value for attend code has changed.                               */
        /*----------------------------------------------------------------------*/
            
        $sqlc = $mysqli->prepare("
            INSERT INTO attend (attended_index, attend_code, stu_index, epoch, course_index, last_updated)
            VALUES (?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                   last_updated = IF(attend_code<>VALUES(attend_code), VALUES(last_updated), last_updated),
                   attend_code = IF(attend_code<>VALUES(attend_code), VALUES(attend_code), attend_code)
            ");
        
        //$course = $_POST['course'];
        $stu_index = $enrolled[1];
        $inst_comment = sprintf("instComment-%s", $stu_index);
        $epoch = $enrolled[2];
        $sqlc->bind_param("ssssss", $attended_index, $val, $stu_index, $epoch, $course, $date_entered);

/*------------------------------------------------------------------------------*/
/* assign the sql execute statement return message to the variable $test_return */
/* if not false (the if(!FALSE === $test_return) bit) then increment a counter  */
/* which right now is not used, I may take it out later. if the return IS       */
/* false, then pront an error message, close the connection and bow out         */
/* gracefully.                                                                  */
/*------------------------------------------------------------------------------*/
        
        $test_return = $sqlc->execute();
        if(!FALSE === $test_return){
            //printf("Record(s) updated<br>\n");
            $counted_updates++; 
        } else {
            Printf("Well, this is embarrasing: an error occurred...<br>\n");
            } 
        }
    }    
    if($counted_updates > 0){
    printf("Record(s) updated<br>\n");
    }
    
    $sqlg = $mysqli->prepare("UPDATE classes SET last_updated = ? WHERE prog_index = ?");
    $sqlg->bind_param('ss', $date_entered, $course);
    $test_return = $sqlg->execute();
    if(!FALSE === $test_return){
        //Success!
    } else {
        printf("Oops, failed to update course: %s<br>\n", $course);
    }
    
    mysqli_close($mysqli);
    $redirect = sprintf("Refresh: 1;url=login_succeeded.php?routine=enterattendad");
    header($redirect);
}

function programs(){
/*
 * Connect to databse
 */    
    
    $mysqli = connect_me();
    
    printf("<b>Programs</b>\n<br>\n");
    
    $sql = "SELECT * FROM program_codes";
    $result = $mysqli->query($sql);
    
    $sqlb = "SELECT * FROM term";
    $resulsb = $mysqli->query($sqlb);
    
    printf("        <form name=\"new_program\" method=\"post\" action=\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");
    printf("        <input type=\"hidden\" name=\"routine\" value=\"add_program\">\n");
    
    printf("<table border=\"0\" cellpadding=\"8\">\n");
    printf("    <tr>\n");
    printf("        <td>\n");
    printf("            Program Code: <input type=\"text\" name=\"prog_code\" />\n");
    printf("        </td>\n");
    printf("        <td>\n");
    printf("            Program Description: <input type=\"text\" name=\"prog_descr\" />\n");
    printf("        </td>\n");
    printf("        <td>\n");
    printf("            <input type=\"submit\" name=\"new_program\" value=\"Add Program\">\n");
    printf("        </td>\n");
    printf("    </tr>\n");
    printf("    <tr>\n");
    printf("        <td>\n");
    printf("            Select Term:");
    printf("            <select name=\"term_code\">\n");
    while($rowsb = $resulsb->fetch_assoc()){
            $sqlc = "SELECT * FROM term_codes WHERE term_index = '" . $rowsb['term_type'] . "'";
            $resultsc = $mysqli->query($sqlc);
            $rowsc = $resultsc->fetch_assoc();
            printf("                <option value=\"%s\">%s: %s - %s to %s</option>\n", $rowsb['date_index'], $rowsc['term_type'], $rowsb['term_number'], $rowsb['term_start'], $rowsb['term_end']);
    }
    printf("            </select>\n");
    printf("        </td>\n");
    printf("    </tr>\n");
    printf("</table>\n");
    printf("        </form>\n");
    printf("<br>\n");

    printf("        <form name=\"edit_prog\" method=\"post\" action=\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");
    printf("<hr>\n<br>\n");
    
    printf("<table class=\"left\" border=\"1\" cellpadding=\"8\">\n");
    printf("<tr>\n");
    printf("<th><center><b>Program ID</b></b></th>\n");
    printf("<th><b>Term</b></th>\n");
    printf("<th><b>Program Description</b></th>\n<th> </th>\n");
    
    while($rows = $result->fetch_assoc()){
        $sqld = "SELECT * FROM term INNER JOIN term_codes ON term.term_type = term_codes.term_index WHERE term.term_number = '" . $rows['term_code'] . "'";
        $resultsd = $mysqli->query($sqld);
        $rowsd = $resultsd->fetch_assoc();
        printf("        <form name=\"upd_prog\" method=\"post\" action=\"");
        printf(htmlspecialchars($_SERVER['PHP_SELF']));
        printf("\">\n");
        printf("             <input type=\"hidden\" name=\"prog_id\" value=\"%s\">\n", $rows['group_index']);
        printf("             <input type=\"hidden\" name=\"routine\" value=\"upd_prog\">\n");
        printf("         <tr>\n");
        printf("              <td class=\"right\"><b>%s</b>\n</td>\n<td class=\"left\">%s: %s - %s</td>\n<td class=\"right\">%s</td>\n", $rows['group_name'], $rowsd['term_number'], $rowsd['term_type'], $rowsd['term_start'], $rows['group_description']);
        printf("         <td class=\"center\"><input type=\"submit\" name=\"upd_prog\" value=\"Edit\">\n</td>\n");
        printf("    </tr>\n");
        printf("        </form>\n");
    }
    printf("</table>\n");
    
    $result->free();
    mysqli_close($mysqli);
    
}

function purge_records_ask_first() {
/*------------------------------------------------------------------------------*/
/* This function is pretty basic, just warn the user what they want to do       */
/* will permanantly delete records from the database. They can be restored, but */
/* it takes some effort. Then show a button giving the user the option to       */
/* continue with the purge.                                                     */
/*------------------------------------------------------------------------------*/    

    $course_id = $_POST['course_id'];
    
    printf("<b>Purge Records</b>\n<br>\n<br>\n");
    printf("<b>Here Be Dragons!</b>\n<br>\n<br>\n");
    
    printf("<table class=\"left\" cellpadding=\"2\" border=\"0\">\n");
    printf("    <tr>\n");
    printf("        <td>Do you really wish to proceed?</td></tr>\n");
    printf("        <tr><td>This will <b><u>permanantly remove</u></b> all records</td></tr>\n");
    printf("        <tr><td>From this class for this term - they have</td></tr>\n");
    printf("        <tr><td>been archived, but you have been warned!</td></tr>\n");
    printf("    </tr>\n");
    printf("    <tr>\n");
    printf("        <form name=\"purge_em\" method=\"post\" action\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");
    printf("        <td>\n");
    printf("            <input type=\"hidden\" name=\"routine\" value=\"purge_for_real\">\n");
    printf("            <input type=\"hidden\" name=\"course_id\" value=\"%s\">\n", $course_id);
    printf("            <input type=\"submit\" name=\"Yes Purge\" value=\"Yes - Purge\">\n");
    printf("        </td>\n");
    printf("        </form>\n");
    printf("</table>\n");
    
}

function purge_records() {
/*------------------------------------------------------------------------------*/
/* Here be dragons. This is the routine which will actually purge the           */
/* records from the database. Additionally, this will set off a chain reaction, */ 
/* Nukes will go off in North Korea, the Kremlin will sink into the soil,       */
/* the Donald's hair will finally turn grey and blow off, while                 */
/* Hillary Clinton, is, at long last, incarcerated for her crimes against       */
/* humanity.                                                                    */
/* We need to purge the records from attendance, enrolled and                   */
/* inst_comments tables. this will then set a flag in the classes table         */
/* indicating that the class records have been purged, this will allow a class  */
/* to be deleted.                                                               */
/*------------------------------------------------------------------------------*/
 
/*--------------------------------*/
/* Connect to database            */
/*--------------------------------*/    
    
$mysqli = connect_me();

$course_id = $_POST['course_id'];

/*------------------------------------------------------------------------------*/
/* Sanity check! Let's make sure the course actually has been archived before   */
/* we allow the purge to proceed. This is the equivolent of the microsoft       */
/* "Are You Sure You Want to Delete" popup which annoys us all, but serves a    */
/* purpose, however, in this case, there will be no option to proceed, the      */
/* user WILL HAVE to go back and archive first (technically, they shouldn't     */
/* get this far if the class is not archived, but, you never know)              */
/*------------------------------------------------------------------------------*/

$sql_a = "SELECT archived FROM classes WHERE prog_index = '" . $course_id . "'";

$results_a = $mysqli->query($sql_a);
$rows_a = $results_a->fetch_assoc();
$archive_bit = $rows_a['archived'];

if($archive_bit == "0"){
    printf("<b>Sorry! You cannot purge without having archived the class first!</b>\n");
           printf("<a href=\"");
           printf(htmlspecialchars($_SERVER['PHP_SELF']));
           printf("?routine=listprogs\">Return To Class List</a>\n");
} else {
    printf("<b>Purging Class Attendance Records:<b><br>\n");
    $sql_b = $mysqli->prepare("DELETE FROM attend WHERE course_index = ?");
    $sql_b->bind_param("s", $course_id);
    
    if(!$sql_b->execute()){
        printf("A drastic error has ocurred! %s<br>\n", mysqli_error($mysqli));
        $results_a->free();
        return;
    }
        printf("Attendence records for class successfully purged<br>\n");
        printf("Purging Class Enrollment Records<br>\n");

    $sql_b = $mysqli->prepare("DELETE FROM enrolled WHERE prog_index = ?");
    $sql_b->bind_param("s", $course_id);
    
    if(!$sql_b->execute()){
        printf("A drastic error has ocurred! %s<br>\n", mysqli_error($mysqli));
        $results_a->free();
        return;
    }
        printf("Enrollment records for class successfully purged<br>\n");
        printf("Purging Instructor Comments for class.<br>\n");
        
    $sql_b = $mysqli->prepare("DELETE FROM inst_comments WHERE class_index = ?");
    $sql_b->bind_param("s", $course_id);
    
    if(!$sql_b->execute()){
        printf("A drastic error has ocurred! %s<br>\n", mysqli_error($mysqli));
        $results_a->free();
        return;
    }
    
    printf("Instructor comments successfully purged.<br>\n");
    printf("Now setting class record to 'purged'<br>\n");
    
    $sql_b = $mysqli->prepare("UPDATE classes SET purged='1' WHERE prog_index=? ");
    $sql_b->bind_param("s", $course_id);
    
    if(!$sql_b->execute()){
        printf("A drastic error has ocurred! %s<br>\n", mysqli_error($mysqli));
        $results_a->free();
        return;
    }    
}

$results_a->free();
mysqli_close($mysqli);
header("Refresh: 5;url=login_succeeded.php?routine=listprogs");
}

function add_program() {

    
$prog_name = $_POST['prog_code'];
$prog_desc = $_POST['prog_descr'];
$term_code = $_POST['term_code'];

/*    foreach ($_POST as $key => $value){
    printf("<br>\n<br>\n");
    printf("Key: %s ", $key);
    printf("&nbsp; &nbsp;Value: %s\n", $value);
    printf("<br>\n<br>\n");
    }
*/
/*
 * Connect to mysql
 */
$mysqli = connect_me();

$sql = $mysqli->prepare("INSERT INTO program_codes (group_name, group_description, term_code) VALUES (?, ?, ?)");
//printf("Error Will Robinson! %s\n", mysqli_error($mysqli));
$sql->bind_param("sss", $prog_name, $prog_desc, $term_code);
//printf("Error Will Robinson! %s\n", mysqli_error($mysqli));
if($sql->execute()){
            printf("Record Added<br>\n");
        } else {
            Printf("Well, this is embarrasing: an error occurred...<br>\n");
        } 
 
    mysqli_close($mysqli);
    $redirect = sprintf("Refresh: 1;url=login_succeeded.php?routine=programs");
    header($redirect);
}

function update_program (){
    
/*
 * Connect to databse
 */    
    
    $mysqli = connect_me();
    
    $prog_id = $_POST['prog_id'];
    
    $sql = "SELECT * FROM program_codes WHERE group_index = '" . $prog_id . "'";

    $result = $mysqli->query($sql);
    $rows = $result->fetch_assoc();
    
    $sqla = "SELECT * FROM programs WHERE program_code = '" . $prog_id . "'";
    $resulta = $mysqli->query($sqla);
    
        
    $sqlc = "SELECT * FROM term";
    $resulsc = $mysqli->query($sqlc);
    
    $group_name = $rows['group_name'];
    $group_descr = $rows['group_description'];

    printf("<b>Program:</b> %s\n<br>\n", $rows['group_name']);
    
    printf("        <form name=\"new_program\" method=\"post\" action=\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");
    printf("        <input type=\"hidden\" name=\"prog_id\" value=\"%s\">\n", $prog_id);
    printf("        <input type=\"hidden\" name=\"routine\" value=\"upd_program\">\n");

    printf("            <table class=\"noborder\" cellpadding=\"8\">\n");
    printf("                <tr>\n");
    printf("                    <td>\n");
    printf("                    <input type=\"text\" name=\"prog_name\" value=\"%s\">\n", $group_name);
    printf("                    </td>\n");
    printf("                    <td>\n");
    printf("                    <input type=\"text\" name=\"prog_desc\" value=\"%s\">\n", $group_descr);
    printf("                    </td>\n");
    printf("                    <td>\n");
    printf("                    <input type=\"submit\" name=\"upd_prog\" value=\"Update\">\n");
    printf("                    </td>\n");
    printf("                </tr>\n");
    printf("                <tr>\n");
    printf("                    <td>\n");
    printf("                        Select Term: ");
    printf("<select name=\"term_code\">\n");
    while($rowsc = $resulsc->fetch_assoc()){
            $sqld = "SELECT * FROM term_codes WHERE term_index = '" . $rowsc['term_type'] . "'";
            $resultsd = $mysqli->query($sqld);
            $rowsd = $resultsd->fetch_assoc();
            printf("                        <option value=\"%s\"", $rowsc['date_index']);
            if($rowsc['date_index'] == $rows['term_code']){
            printf(" selected>%s: %s - %s To %s</option>\n", $rowsd['term_type'], $rowsc['term_number'], $rowsc['term_start'], $rowsc['term_end']);
            } else {
                printf(">%s: %s - %s To %s</option>\n", $rowsd['term_type'], $rowsc['term_number'], $rowsc['term_start'], $rowsc['term_end']);
            }
    }
    printf("                        </select>\n");
    printf("                    </td>\n");
    printf("                </tr>\n");
    printf("            </table>\n");
    printf("        </form>\n");
    
    printf("        <hr>\n");
    
    printf("        <table class=\"standard\" cellpadding=\"8\">\n");
    printf("            <tr>\n");
    if(mysqli_num_rows($resulta) > 0){
        printf("            <th>Course</th>\n");
        printf("        </tr>\n");
        while($rowsa = $resulta->fetch_assoc()){
            printf("    <tr>\n");
            //printf("                <td>\n");
            $sqlb = "SELECT program FROM classes WHERE prog_index = '" . $rowsa['course_index'] . "'";
            $resultb = $mysqli->query($sqlb);
            $rowsb = $resultb->fetch_assoc();
            printf("                <td>%s</td>\n", $rowsb['program']);
            printf("                    <form name=\"remove_prog\" method=\"post\" action=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("\">\n");            
            printf("                <input type=\"hidden\" name=\"routine\" value=\"del_from_prog\">\n");
            printf("                <input type=\"hidden\" name=\"prog_code\" value=\"%s\">\n", $rowsa['program_index']);
            printf("                <td><input type=\"submit\" name=\"remove_from_prog\" value=\"Remove\"></td>\n");
            printf("        </form>\n");
            printf("    </tr>\n");
        }
        mysqli_data_seek($resultb, 0);
    } else {
        printf("        <td>No courses in this program yet</td>\n");
        printf("        </tr>\n");
    }

    $sqlb = "SELECT * FROM classes";
    $resultb = $mysqli->query($sqlb);
    printf("            <tr>\n");
    printf("            <td>\n");
            printf("        <form name=\"add_prog_to_course\" method=\"post\" action=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("\">\n");
            printf("            <input type=\"hidden\" name=\"program_id\" value=\"%s\">\n", $prog_id);
            printf("            <input type=\"hidden\" name=\"routine\" value=\"add_class_to_prog\">\n");
            printf("            <select name=\"add_prog_to_course\">\n");
                while($rowsb = $resultb->fetch_assoc()){
                    printf("                <option value=\"%s\"> %s </option>\n", $rowsb['prog_index'], $rowsb['program']);
        
            }
            printf("            </td>\n");
            printf("            <td>\n");
            printf("            <input type=\"submit\" name=\"add_course_to_prog\" value=\"Add\">\n");
            printf("            </td>\n");
            printf("        </form>\n");
    printf("        </table>\n");
    
    mysqli_close($mysqli);
}

function add_class_to_program(){
/*
 * Connect to Mysql
 */    
    
    $mysqli = connect_me();
    
    $program_id = $_POST['program_id'];
    $class_code = $_POST['add_prog_to_course'];
    
    $sqla = sprintf("SELECT * FROM programs WHERE program_code = %s AND course_index = %s", $program_id, $class_code);
    $resulta = $mysqli->query($sqla);
    if(mysqli_num_rows($resulta) > 0){
        $rowsa = $resulta->fetch_assoc();
        $program_index = $rowsa['program_index'];
    } else {
        //$sqlb = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'pdt_attend' AND TABLE_NAME = 'programs'";
        $sqlb = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'attend_pdt' AND TABLE_NAME = 'programs'";
        $resultb = $mysqli->query($sqlb);
        $rowsb = $resultb->fetch_assoc();
        $program_index = $rowsb['AUTO_INCREMENT'];
    }
    
    $sql = $mysqli->prepare("
            INSERT INTO programs (program_index, program_code, course_index)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                program_code = VALUES(program_code),
                course_index = VALUES(course_index)
            ");
    //echo "Error = " . mysqli_error($mysqli);
    $sql->bind_param('iii', $program_index, $program_id, $class_code);
    
    if($sql->execute()){
        printf("<b>Record added</b>\n<br>\n");
    } else {
        Printf("Well, this is embarrasing: an error occurred...<br>\n");
    }
    
    mysqli_close($mysqli);

    $redirect = sprintf("Refresh: 1;url=login_succeeded.php?routine=programs");
    header($redirect);
}

function upd_program(){
    
/*    foreach ($_POST as $key => $value){
    printf("<br>\n<br>\n");
    printf("Key: %s ", $key);
    printf("&nbsp; &nbsp;Value: %s\n", $value);
    printf("<br>\n<br>\n");
    }
*/    
    
/*
 * Connect to databse
 */    
    
    $mysqli = connect_me();
    
    $program_name = $_POST['prog_name'];
    $program_description = $_POST['prog_desc'];
    $prog_id = $_POST['prog_id'];
    $term_code = $_POST['term_code'];

    $sql = $mysqli->prepare("UPDATE program_codes SET group_name=?,
            group_description=?,
            term_code=?
            WHERE group_index=?");
    $sql->bind_param('ssss', $program_name, $program_description, $term_code, $prog_id);
    if($sql->execute()){
            printf("Program Updated<br>\n");
        } else {
            Printf("Well, this is embarrasing: an error occurred...<br>\n");
        } 
 
    mysqli_close($mysqli);
    $redirect = sprintf("Refresh: 1;url=login_succeeded.php?routine=programs");
    header($redirect);
}

function set_term(){
    
/*
 * Connect to Mysql
 */    

    $mysqli = connect_me();
    
    // Create a counter for term number
    
    $x = 0;
    
    // Get all listings from term dates table (if any)
    
    $sql = "SELECT * FROM term ORDER BY term_type";
    $result = $mysqli->query($sql);
    
    $sqla = "SELECT * FROM term_codes";
    $resulta = $mysqli->query($sqla);

    printf("    <form name=\"course_dates\" method=\"post\" action=\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");
    printf("    <input type=\"hidden\" name=\"routine\" value=\"term_dates\">\n");
    
    printf("    <table class=\"noborder\" cellpadding=\"8\">\n");
    printf("        <tr>\n");
    printf("            <th>Term</th>\n");
    printf("            <th>From Date</th>\n");
    printf("            <th>To Date</th>\n");
    printf("            <th>Type</th>\n");
    printf("        </tr>\n");
    
    printf("        <tr>\n");
    printf("            <td>\n");
    printf("            <select name=\"sem_number\">\n");
    
    for($x = 0;$x < 5;$x++){
        printf("            <option value=\"%s\"> %s </option>\n", $x+1, $x+1);
    }
    
    //Reset counter to 0
    
    $x = 0;
    
    printf("            </select>\n");
    
    printf("            <td>\n");
    printf("            <input type=\"text\" name=\"from\" size=\"10\">\n");
    printf("                <a href=\"javascript:void(0);\" NAME=\"Date From\" title=\" Date From \" onClick=window.open(\"calendar.php?form=from\",\"Ratting\",\"width=350,height=270,left=150,top=200,toolbar=1,status=1,\");>\n");
    printf("                <img src=\"./images/iconCalendar.gif\">");
    printf("</a>\n");
    printf("            </td>\n");
    printf("            <td>\n");
    printf("            <input type=\"text\" name=\"to\" size=\"10\">\n");
    printf("                <a href=\"javascript:void(0);\" NAME=\"Date To\" title=\" Date To \" onClick=window.open(\"calendar.php?form=to\",\"Ratting\",\"width=350,height=270,left=150,top=200,toolbar=1,status=1,\");>\n");
    printf("                <img src=\"./images/iconCalendar.gif\">");
    printf("</a>\n");
    printf("            </td>\n");
    
    printf("            <td>\n");
    printf("                <select name=\"sem_type\">\n");
    
    while($rowsa = $resulta->fetch_assoc()){
        printf("                <option value=\"%s\"> %s </option>\n", $rowsa['term_index'], $rowsa['term_type']);    
    }
    printf("                </select>\n");
    printf("            </td>\n");
    printf("            <td>\n");
    printf("                <input type=\"submit\" name=\"sub_term\" value=\"Add Date\">\n");
    printf("            </td>\n");
    printf("        </tr>\n");
    printf("    </table>\n");
    printf("    </form>\n");
    
    printf("    <hr>\n");
    printf("    <br>\n");

    
    printf("    <table class=\"standard\" cellpadding=\"8\">\n");
    printf("        <tr>\n");
    printf("            <th>Term</th>\n");
    printf("            <th>From Date</th>\n");
    printf("            <th>To Date</th>\n");
    printf("            <th>Type</th>\n");
    printf("        </tr>\n");
    
    if(mysqli_num_rows($result) > 0){
        while($row = $result->fetch_assoc()){
            $sqlc = "SELECT * FROM term_codes WHERE term_index = " . $row['term_type'] . "";
            $resultc = $mysqli->query($sqlc);
            $rowsc = $resultc->fetch_assoc();
            printf("        <tr>\n");
            printf("            <form name=\"term_dates\" method=\"post\" action=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("\">\n");
            printf("            <td>\n");
            printf("                <input type=\"hidden\" name=\"routine\" value=\"edit_term\">\n");
            printf("                <input type=\"hidden\" name=\"date_index\" value=\"%s\">\n", $row['date_index']);
            printf("                %s\n", $row['term_number']);
            printf("            </td>\n");
            printf("            <td>\n");
            
            $temp_term = explode('/', $row['term_start']);
            $term_temp = $temp_term[1] . "/" . $temp_term[0] . "/" . $temp_term[2];
            
            
            
            printf("                %s\n", date('D M d Y', strtotime($term_temp)));
            printf("            </td>\n");
            printf("            <td>\n");
            
            $temp_term = explode('/', $row['term_end']);
            $term_temp = $temp_term[1] . "/" . $temp_term[0] . "/" . $temp_term[2];            
                        
            printf("                %s\n", date('D M d Y', strtotime($term_temp)));
            printf("            </td>\n");
            printf("            <td>\n");
            printf("                %s\n", $rowsc['term_type']);
            printf("            </td>\n");
            printf("            <td>\n");
            printf("                <input type=\"submit\" name=\"term_edit\" value=\"edit\">\n");
            printf("            </td>\n");
            printf("        </tr>\n");
            printf("    </form>\n");
        }
    } else {
        printf("        <tr>\n");
        printf("            <td colspan=\"4\">\n");
        printf("            <center>No Terms have been defined yet</center>\n");
        printf("            </td>\n");
        printf("        </tr>\n");
    }
    
    printf("    </table>\n");
    
    
    // Free memory then close the connection to the database
    $result->free();
    $resulta->free();
    $resultc->free();
    mysqli_close($mysqli);
    

}

function save_term(){
    
/*
 * Connect to Mysql
 */    
    
    $mysqli = connect_me();
    
    $sqla = "SELECT * FROM term";
    $resulta = $mysqli->query($sqla);
    $rowsa = $resulta->fetch_assoc();
    
    $term_number = $_POST['sem_number'];
    $term_from = $_POST['from'];
    $term_to = $_POST['to'];
    $term_type = $_POST['sem_type'];
    
        if(mysqli_num_rows($resulta) > 0){
        $rowsa = $resulta->fetch_assoc();
        if($_POST['date_index'] == NULL){
            $date_index = $rowsa['date_index'];
        } else {
            $date_index = $_POST['date_index'];
        }
    } else {
        //$sqlb = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'pdt_attend' AND TABLE_NAME = 'term'";
        $sqlb = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'attend_pdt' AND TABLE_NAME = 'term'";
        $resultb = $mysqli->query($sqlb);
        $rowsb = $resultb->fetch_assoc();
        $date_index = $rowsb['AUTO_INCREMENT'];
        $resultb->free();        
    }
    
    $sql = $mysqli->prepare("
            INSERT INTO term (date_index, term_number, term_start, term_end, term_type)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                term_number = VALUES(term_number),
                term_start = VALUES(term_start),
                term_end = VALUES(term_end),
                term_type = VALUES(term_type)
                ");
    $date_index = $_POST['index'];
    $sql->bind_param('iissi', $date_index, $term_number, $term_from, $term_to, $term_type);
    //echo "Error = " . mysqli_error($mysqli);
    if($sql->execute()){
        printf("<b>term Dates Updated</b><br>\n");
    } else {
        Printf("Well, this is embarrasing: an error occurred...<br>\n");
    }
    
    $resulta->free();
    mysqli_close($mysqli);
    $redirect = sprintf("Refresh: 1;url=login_succeeded.php?routine=term");
    header($redirect);    
}

function edit_term(){
/*
 * Connect to Mysql
 */  
    
    $mysqli = connect_me();
    
    $sql = "SELECT * FROM term WHERE date_index = " . $_POST['date_index'] . "";
    $result = $mysqli->query($sql);
    $row = $result->fetch_assoc();
    
    $date_from = $row['term_start'];
    $date_to = $row['term_end'];
    $term_number = $row['term_number'];
    $term_type = $row['term_type'];
    $date_index = $_POST['date_index'];
    
    $sqla = "SELECT * FROM term_codes";
    $resulta = $mysqli->query($sqla);
            
    
    printf("    <form name=\"course_dates\" method=\"post\" action=\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");
    printf("        <input type=\"hidden\" name=\"routine\" value=\"update_term\">\n");
    printf("        <input type=\"hidden\" name=\"date_index\" value=\"%s\">\n", $date_index);
    
    printf("    <table class=\"noborder\" cellpadding=\"8\">\n");
    printf("        <tr>\n");
    printf("            <th>Term</th>\n");
    printf("            <th>From Date</th>\n");
    printf("            <th>To Date</th>\n");
    printf("            <th>Type</th>\n");
    printf("        </tr>\n");
    
    printf("        <tr>\n");
    printf("            <td>\n");
    printf("            <select name=\"term_number\">\n");
    
    for($x = 0;$x < 5;$x++){
        if($term_number == $x+1){
            printf("            <option value=\"%s\" selected> %s </option>\n", $x+1, $x+1);
        } else {
            printf("            <option value=\"%s\"> %s </option>\n", $x+1, $x+1);
        }
    }
    
    //Reset counter to 0
    
    $x = 0;
    
    printf("            </select>\n");
    
    printf("            <td>\n");
    printf("            <input type=\"text\" name=\"from\" size=\"10\" value=\"%s\">\n", $date_from);
    printf("                <a href=\"javascript:void(0);\" NAME=\"Date From\" title=\" Date From \" onClick=window.open(\"calendar.php?form=from\",\"Ratting\",\"width=350,height=270,left=150,top=200,toolbar=1,status=1,\");>\n");
    printf("                <img src=\"./images/iconCalendar.gif\">");
    printf("</a>\n");
    printf("            </td>\n");
    printf("            <td>\n");
    printf("            <input type=\"text\" name=\"to\" size=\"10\" value=\"%s\">\n", $date_to);
    printf("                <a href=\"javascript:void(0);\" NAME=\"Date To\" title=\" Date To \" onClick=window.open(\"calendar.php?form=to\",\"Ratting\",\"width=350,height=270,left=150,top=200,toolbar=1,status=1,\");>\n");
    printf("                <img src=\"./images/iconCalendar.gif\">");
    printf("</a>\n");
    printf("            </td>\n");
    
    printf("            <td>\n");
    printf("                <select name=\"term_type\">\n");
    
    while($rowsa = $resulta->fetch_assoc()){
        if($term_type == $rowsa['term_index']){
            printf("                <option value=\"%s\" selected> %s </option>\n", $rowsa['term_index'], $rowsa['term_type']);    
        } else {
            printf("                <option value=\"%s\"> %s </option>\n", $rowsa['term_index'], $rowsa['term_type']);    
        }
    }
    printf("                </select>\n");
    printf("            </td>\n");
    printf("            <td>\n");
    printf("                <input type=\"submit\" name=\"sub_term\" value=\"Upate\">\n");
    printf("            </td>\n");
    printf("        </tr>\n");
    printf("    </table>\n");
    printf("    </form>\n");

    $result->free();
    $resulta->free();
    mysqli_close($mysqli);
    $redirect = sprintf("Refresh: 1;url=login_succeeded.php?routine=term");
    header($redirect);
}

function update_term(){
    
/*
 * Connect to Mysql
 */    
    $mysqli = connect_me();
    
    $date_index = $_POST['date_index'];
    $term_number = $_POST['term_number'];
    $term_start = $_POST['from'];
    $term_end = $_POST['to'];
    $term_type = $_POST['term_type'];
    
    $sql = $mysqli->prepare("UPDATE term SET term_number=?, term_start=?, term_end=?, term_type=? WHERE date_index=?");
    $sql->bind_param('issii', $term_number, $term_start, $term_end, $term_type, $date_index);

    if($sql->execute()){
        printf("<b>Term Updated</b><br>\n");
    } else {
        Printf("Well, this is embarrasing: an error occurred...<br>\n");
    }
    
    mysqli_close($mysqli);
    $redirect = sprintf("Refresh: 1;url=login_succeeded.php?routine=term");
    header($redirect);    
}

function student_list(){
    
/*------------------------------------------------------------------------------*/
/* Print out a list of students, with a hyperlink to a routine which gives      */
/* list of all classes the student is registered in and their attendance        */
/* records.                                                                     */
/*------------------------------------------------------------------------------*/

/* Connect to database */    
    
    $mysqli = connect_me();

$sql_a = "SELECT * FROM student ORDER BY Stu_L_name";
$results_a = $mysqli->query($sql_a);

    
printf("    <table class=\"standard\" cellpadding=\"8\" border=\"0\">\n");
printf("        <tr>\n");
printf("            <th>Student Name</th><th>Sponsor</th>\n");
printf("        </tr>\n");

while($rows_a = $results_a->fetch_assoc()){
    printf("    <tr>\n");
    printf("        <td>\n");
    
   $student_name = $rows_a['Stu_L_name'] . ", " . $rows_a['Stu_F_name'];
   
   if($rows_a['Stu_M_name'] == ""){
       $student_name = $student_name;
    } else {
        $student_name = $student_name . " " . $rows_a['Stu_M_name'];
    }
    
    printf("            <form name=\"rep_by_stu\" method=\"post\" action=\"report_by_student.php\">\n");
    
    
    
    printf("            %s\n", $student_name);
    printf("        </td>\n");
    
    $sql_b = "SELECT * FROM sponsor WHERE sponsor_index = '" . $rows_a['sponsor'] . "'";
    $results_b = $mysqli->query($sql_b);
    $rows_b = $results_b->fetch_assoc();
    
    printf("        <td>%s</td>\n", $rows_b['sponsor']);
    
    printf("        <td>\n");
    printf("            <input type=\"hidden\" name=\"routine\" value=\"rep_by_stu\">\n");
    printf("            <input type=\"hidden\" name=\"sponsor\" value=\"%s\">\n", $rows_b['sponsor']);
    printf("            <input type=\"hidden\" name=\"stu_id\" value=\"%s\">\n", $rows_a['stu_index']);
    printf("            <input type=\"submit\" name=\"rep_by_stu\" value=\"Report\">\n");
    printf("            </form>\n");
    printf("        </td>\n");
    
    printf("    </tr>\n");
}

printf("    </table>\n");

$results_a->free();
$results_b->free();
mysqli_close($mysqli);
}

function archive_class(){
/*------------------------------------------------------------------------------*/
/* The data for classes can get out of hand at times, making dealing with both  */
/* old and new classes very confusing. Currently, classes cannot be deleted. .  */
/* data hangs around forever in the same database tables, making them pretty    */
/* monolythic.                                                                  */
/* So let's archive the class, then ask if the user wants to purge the course   */
/*------------------------------------------------------------------------------*/
/* Connect to database */    
    
    $mysqli = connect_me();
    
    $username = $_SESSION['username'];
    

/*---------------------*/
/* Assign variables    */
/*---------------------*/

$from_index = $_POST['from'];
$to_index = $_POST['to'];
$course_index = $_POST['id'];

/*------------------------------------------------------------------------------*/
/* So, why are the following couple of queries out of sequence? Because I had   */ 
/* themat the bottom of the subroutine originally, that's why! :)               */
/*------------------------------------------------------------------------------*/
$sql_e = "SELECT * FROM  classes WHERE prog_index = '" . $course_index . "'";
    $results_e = $mysqli->query($sql_e);
    $rows_e = $results_e->fetch_assoc();
    $instructor_id = $rows_e['inst_index'];
/*------------------------------------------------------------------------------*/
/* The next couple of lines strip any spaces or weird characters out of         */
/* The class name in order to prevent Mysql from borking, an example of         */
/* characters which will cause mysql to bork is the hyphen, which represents    */
/* the minus symbol to mysql, which has special meaning to mysql which          */
/* means in order to use a hyphen (minus symbol) in a table name or             */
/* field name, you have to escape it, which I am too lazy to do, so             */
/* let's just replace any spaces or hyphens etc with an underscore, which mysql */
/* is ok with as is.                                                            */
/*------------------------------------------------------------------------------*/
    
    $class_name_no_spaces = str_replace(" ", "", $rows_e['program']);
    $class_name = preg_replace('/[^A-Za-z0-9]/', '', $class_name_no_spaces);

/*------------------------------------------------------------------------------*/

    $year_start = $rows_e['start_date_yr'];
    $year_end = $rows_e['end_date_yr'];
    $start_epoch = $rows_e['from_date'];
    $end_epoch = $rows_e['to_date'];

$sql_d = "SELECT * FROM U_ops WHERE U_index = '" . $instructor_id . "'";
    $results_d = $mysqli->query($sql_d);
    $rows_d = $results_d->fetch_assoc();
    $instructor_name = $rows_d['U_F_name'] . " " . $rows_d['U_L_name'];

$sql_b = "SELECT * FROM attend WHERE epoch BETWEEN '" . $from_index . "' AND '" . $to_index . "' AND course_index = '" . $course_index . "'";
$results_b = $mysqli->query($sql_b);

//Bug shooting - just displays the completed query.
//echo "SQL = " . $sql_b . "<br>\n"; 

/*------------------------------------------------------------------------------*/
/* Retreive the program name based upon $course_index                           */
/*------------------------------------------------------------------------------*/

$sql_c = "SELECT program FROM classes WHERE prog_index = '" . $course_index . "'";
$results_c = $mysqli->query($sql_c);
$rows_c = $results_c->fetch_assoc();


/*------------------------------------------------------------------------------*/
/* Create a table to hold the archived copy of the records for this particular  */
/* course.                                                                      */
/*------------------------------------------------------------------------------*/

// Create a variable to hold the current time (@ which the query is run)

$timer = time();

// Create the query

$sql_f = "CREATE TABLE Archive_" . $class_name . "_" . $course_index . "_" . $timer . " (archive_id INT(6) AUTO_INCREMENT PRIMARY KEY, historical_prog_id INT(6), program VARCHAR(256), instructor_name VARCHAR(256), from_epoch INT(10), to_epoch INT(10), day_of_record_epoch INT(10), historical_attended_index INT(6), attend_code VARCHAR(64), historical_sponsor_index INT(6), stu_sponsor VARCHAR(255), historical_stu_index INT(6), student_name VARCHAR(255))";

/*------------------------------------------------------------------------------*/
/* Run the query.                                                               */
/* Check if the query was successful.                                           */
/* If not, cleanly close all the queries, then return                           */
/*------------------------------------------------------------------------------*/

printf("            Attempting to Archive Records<br>\n");

    if(!mysqli_query($mysqli, $sql_f))
    {
        printf("Error! Query failed with error message: %s\n<br>\n", mysqli_error($mysqli));
        $results_d->free();
        $results_e->free();
        mysqli_close($mysqli);
        return;
    }
        else
        /*----------------------------------------------------------------------*/
        /* Query was successful, proceed.                                       */
        /*----------------------------------------------------------------------*/
        {
            
/*------------------------------------------------------------------------------*/
/* Create dynamic file name based on class, term and date                       */
/*------------------------------------------------------------------------------*/
/* Use the program name (str_replace(' ', '_', $rows_c['program']) takes the    */
/* Program name returned above, strips any spaces and replaces them with        */
/* underscores.                                                                 */
/* date("Y-m-d") inserts today's date formated with hyphens between numbers     */
/* and give the file name an extension of .csv                                  */
/*------------------------------------------------------------------------------*/

            $file_name = "./archive/class_archive_" . str_replace(' ', '_', $rows_c['program']) . "-" . date("Y-m-d") . ".csv";

/*--------------------------------------*/
/* Open csv file for writing            */
/*--------------------------------------*/

            $file = fopen($file_name, 'w');
            $first = true;      // Boolean for checking if a job has been done or not
            
            printf("        Archive Table created successfully.<br>\n");
            printf("        Now attempting to archive class<br>\n");
            
                $archive_table_name = "Archive_" . $class_name . "_" . $course_index . "_" . $timer;
                
                while($rows_b = $results_b->fetch_assoc()){

/*------------------------------------------------------------------------------*/
/* CSV stuf                                                                     */
/*------------------------------------------------------------------------------*/

    /*----------------------------------*/
    /* Put column headings              */
    /* we check if $first is true       */
    /* if true; print all the*          */
    /* column headings to the csv.      */
    /* if false, assume we have done    */
    /* done it already and move on      */
    /* to dumping the data.             */
    /*----------------------------------*/
    
    if($first){
        fputcsv($file, array_keys($rows_b));
        $first = false;
    }
    /*--------------------------*/
    /* Put the column data      */
    /*--------------------------*/
    
    fputcsv($file, $rows_b);

/*------------------------------------------------------------------------------*/    
                    
                    
                    $hist_prog_id = $rows_e['prog_index'];
                    $program = $rows_e['program'];
                    $day_of_record = $rows_b['epoch'];
                    $hist_att_index = $rows_b['attended_index'];
                    
                    $sql_h = "SELECT * FROM student WHERE stu_index = '" . $rows_b['stu_index'] . "'";
                    $results_h = $mysqli->query($sql_h);
                    $rows_h = $results_h->fetch_assoc();
                    
                    $hist_stu_index = $rows_b['stu_index'];
                    
                    $stu_name = $rows_h['Stu_L_name'] . " " . $rows_h['Stu_F_name'];
                    if($rows_h['Stu_M_name'] == ""){
                        $stu_name = $stu_name;
                    } else {
                        $stu_name = $stu_name . ", " . $rows_h['Stu_M_name'];
                    }
                    
                    $hist_spons_index = $rows_h['sponsor'];
                    
                        $sql_i = "SELECT * FROM sponsor WHERE sponsor_index = '" . $hist_spons_index . "'";
                        $results_i = $mysqli->query($sql_i);
                        $rows_i = $results_i->fetch_assoc();
                        
                        $stu_spons = $rows_i['sponsor'];
                        
                        $results_i->free();
                    
                    switch ($rows_b['attend_code']) {
                        case 1:
                            $att_code = "Present";
                            break;
                        case 2:
                            $att_code = "Late";
                            break;
                        case 3:
                            $att_code = "Absent";
                            break;
                        case 4:
                            $att_code = "Sick";
                            break;
                        case 5:
                            $att_code = "Left Early";
                            break;
                        case 6:
                            $att_code = "-";
                            break;
                    }

		$sql_g = $mysqli->prepare("INSERT INTO `{$archive_table_name}` (historical_prog_id, program, instructor_name, from_epoch, to_epoch, day_of_record_epoch, historical_attended_index, attend_code,  historical_sponsor_index, stu_sponsor, historical_stu_index, student_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$sql_g->bind_param("ssssssssssss", $hist_prog_id, $program, $instructor_name, $start_epoch, $end_epoch, $day_of_record, $hist_att_index, $att_code, $hist_spons_index, $stu_spons, $hist_stu_index, $stu_name);
               
		if($sql_g->execute())
			{
			} else {
			printf("Error! %s\n<br>\n", mysqli_error($mysqli));
			}

                $results_h->free();
            }
            
        printf("Setting Archive bit in class record.<br>\n");
        $sql_j = "UPDATE classes SET archived='1' WHERE prog_index='" . $rows_e['prog_index'] . "'";
        //echo "SQL_J = " . $sql_j . "<br>\n";
        //$sql_j->bind_param("s", $hist_prog_id); 
        //if($sql_j->execute()){
        if($mysqli->query($sql_j)){
                printf("Archive Bit Successfully set<br>\n");
            } else {
                printf("Error! Archive Bit Could Not Be Set! %s\n<br>\n", mysqli_error($mysqli));    
            }
        }

/*------------------------------------------------------------------------------*/
/* Repeat the query which recovers attendance records for a given class, but    */
/* This query groups by student index so that we only return one record for     */
/* a student, in other words, if there are 30 attendance records, and 3         */
/* students registered for this class in this term, return 3 students once      */
/*------------------------------------------------------------------------------*/

$sql_c = "SELECT * FROM attend WHERE epoch BETWEEN '" . $from_index . "' AND '" . $to_index . "' AND course_index = '" . $course_index ."' GROUP BY stu_index";
$results_c = $mysqli->query($sql_c);

/*------------------------------------------------------------------------------*/
/* Write students who attended this course                                      */
/*------------------------------------------------------------------------------*/

$first = true;      // Boolean used to make sure column headings are printed only once

while($rows_c = $results_c->fetch_assoc()){
    
    /*------------------------------*/
    /* select students records      */
    /* based on student index in    */
    /* attend table                 */
    /*------------------------------*/
    
    $sql_d = "SELECT * FROM student WHERE stu_index = '" . $rows_c['stu_index'] . "'";

    $results_d = $mysqli->query($sql_d);
    /*----------------------------------*/
    /* Put column headings              */
    /* we check if $first is true       */
    /* false, if true; print all the*   */
    /* column headings to the csv       */
    /* if flase, assume we have done    */
    /* done it already and move on      */
    /* to dumping the data.             */
    /*----------------------------------*/
    while($rows_d = $results_d->fetch_assoc()){
    if($first){
        fputcsv($file, array_keys($rows_d));
        $first = false;
    }
    /*------------------------------*/
    /* Write out the student data   */
    /*------------------------------*/
    
    fputcsv($file, $rows_d);
    }
}

$first = true;

    $sql_d = "SELECT * FROM sponsor";
    $results_d = $mysqli->query($sql_d);
    
    while($rows_d = $results_d->fetch_assoc()){
    if($first){
        fputcsv($file, array_keys($rows_d));
        $first = false;
        }
    fputcsv($file, $rows_d);
    }

fclose($file);    



$results_b->free();
$results_c->free();
$results_d->free();
$results_e->free();
mysqli_close($mysqli);

clearstatcache();
if(filesize($file_name)){
    printf("Archive Created - File:%s Has a non-zero value.\n", $file_name);
} else {
    printf("Archive fail: Filename:<b>%s Has a 0 value.</b><br>Records were either empty or there was an error<br>\n", $file_name);
}

header("Refresh: 5;url=login_succeeded.php?routine=listprogs");

}

function compare_pass($password, $comp_pass){
    if(strcmp($password, $comp_pass) == 0){
            $pass_match = 1;
        } else {
            $pass_match = 0;
    }
    //exit;
    return $pass_match;
}

?>

<html>
    <head>
        <meta charset="UTF-8">
        <title>OSATT</title>
        <link rel="stylesheet" type="text/css" href="style-sign.css">
    </head>
    <body>

    <?php

 
$mysqli = connect_me();

 /* 
  * Pass the session username this routine
  * in order to retrieve the data for a user.
  */
    $name = $_SESSION['username'];

/* 
 * create the sql query which retrieves all of the data for all of the system users
 */
    
    $sql = "SELECT * FROM U_ops WHERE log_name = '" . $name . "'";
    
    $sqli = "SELECT * FROM settings";
    
/*
 * Pass the query to mysql and retrieve the requested row.
 */    
    
    //$result = mysqli_query($mysqli, $sql);
    $result = $mysqli->query($sql);
    $row = $result->fetch_assoc();
    //$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    
    $resulti = mysqli_query($mysqli, $sqli);
    $rowi = mysqli_fetch_array($resulti, MYSQLI_ASSOC);    

/*
 * Print the data to the web.
 */
    
    printf("<div id=\"top-box\">\n");
    printf("    <b>%s<br>\n<br>\n %s Attendance Reporting</b>\n", $rowi['org_name'], $rowi['department']);
    printf("    <BR>\n");
    printf("    <b>Hello</b> %s %s\n", $row['U_F_name'], $row['U_L_name']);
    printf("</div>\n");
    printf("<div id=\"data-box\">\n");      
    printf("<div id=\"data-box-two\">\n");

    switch ($row['priv_level']){
        case "1";
            admin_menu();
            break;
        case "2";
            pa_menus();
            break;
        case "3";
            instruct_menus();
            break;
    }
    
//printf("    &nbsp; &nbsp;\n");
            
    //        printf("<a href=\"");
    //        printf(htmlspecialchars($_SERVER['PHP_SELF']));
    //        printf("?routine=chmypass\">Change My Password</a>\n");
    printf("                    <br>\n<br>\n");
    printf("                    <form name=\"chmypass\" method=\"post\" action=\"");
    printf(htmlspecialchars($_SERVER['PHP_SELF']));
    printf("\">\n");                    
    printf("                    <input type=\"hidden\" name=\"userid\" value=\"%s\">\n", $row['U_index']);
    printf("                    <input type=\"hidden\" name=\"routine\" value=\"chpass\">\n");
    printf("                    <input type=\"submit\" name=\"adchpass\" value=\"Change my Password\">\n");
    printf("                    </form>\n");
    //printf("                </td>\n");
    //printf("            </tr>\n");
            
printf("    &nbsp; &nbsp;\n");
            
            printf("<br>\n<br>\n<a href=\"");
            printf(htmlspecialchars($_SERVER['PHP_SELF']));
            printf("?routine=logout\">Logout</a>\n");
printf("    &nbsp; &nbsp;\n");

    printf("</div>\n");
    printf("<div id=\"data-box-two\">\n");
    if(isset($_POST['routine'])){
        $gotvalue = $_POST['routine'];
    }
    else {
        $gotvalue = $_GET['routine'];           
    }

    switch ($gotvalue){
        case "adduser";
            add_new_users_form();
            break;
        case "add_student";
            add_new_student();
            break;
        case "new-user-submit";
            adduser();
            break;
        case "listusers";
            list_users();
            break;
        case "addspons";
            add_sponsor_form();
            break;
        case "add_sponsor";
            add_sponsor();
            break;
        case "listspons";
            list_sponsors();
            break;
        case "liststu";
            list_students();
            break;
        case "addstudent";
            add_new_student_form();
            break;
        case "listprogs";
            list_courses();
            break;
        case "addprog";
            add_new_course_form();
            break;
        case "addcourse";
            add_course();
            break;
        case "edit_student";
            edit_student_form();
            break;
        case "chsettings";
            settings_form();
            break;
        case "chmypass";
            change_pass_form();
            break;
        case "chpass";
            change_pass_form();
            break;
        case "edituser";
            edit_user_form();
            break;
        case "updateuser";
            updateuser();
            break;
        case "mypass";
            change_password();
            break;
        case "deluser";
            del_user();
            break;
        case "delsponsor";
            del_sponsor();
            break;
        case "edcourse";
            edit_course_form();
            break;
        case "updatecourse";
            update_courses();
            break;
        case "updatestudent";
            update_student();
            break;
        case "genreports";
            base_report();
            break;
        case "to_file_format";
            select_file_format();
            break;
        case "last_stage";
            send_reports();
            break;
        case "enterattendad";
            choose_attend_form();
            break;
        case "attend";
            enter_attend_form();
            break;
        case "update_weeks";
            enter_attend_form();
            break;
        case "updateat";
            update_attend();
            break;
        case "logout";
            logout();
            break;
        case "programs";
            programs();
            break;
        case "add_program";
            add_program();
            break;
        case "upd_prog";
            update_program();
            break;
        case "upd_program";
            upd_program();
            break;
        case "base_report";
            report_select_program();
            break;
        case "add_class_to_prog";
            add_class_to_program();
            break;
        case "term";
            set_term();
            break;
        case "term_dates";
            save_term();
            break;
        case "edit_term";
            edit_term();
            break;
        case "update_term";
            update_term();
            break;
        case "ed_spons";
            edit_sponsor();
            break;
        case "update_sponsor";
            update_sponsor();
            break;
        case "repbyspons";
            report_by_sponsor();
            break;
        case "by_spons";
            report_by_sponsor_sel_dates();
            break;
        case "by_spons_term";
            report_by_sponsor_sel_weeks();
            break;
        case "spons_report_format";
            spons_send_reports();
            break;
        case "arch_class";
            archive_class();
            break;
        case "purge";
            purge_records_ask_first();
            break;
        case "purge_for_real";
            purge_records();
            break;
        case "del_class";
            delete_class_ask();
            break;
        case "del_class_real";
            delete_class();
            break;
        case "repbystude";
            student_list();
            break;
        default;
            choose_attend_form();
    }
    printf("</div>\n");
    printf("<div id=\"data-box-three\">\n");
    printf("<div class=\"footer\">\n");
    printf("<address>    Php OSATT</address>\n");
    printf("</div>\n");
    printf("</div>\n");
printf("</div>\n");
        ?>
    </body>
</html>
