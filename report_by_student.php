<?php

/*
 * report_by_student.php
 * Copyright (C) 2016 Ken Plumbly <frotusroom@gmail.com>
 *
 * report_by_student.php is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * report_by_student.php is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along
 * with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/*----*/

/*------------------------------------------------------------------------------*/
/* Generate attendance reports by student as opposed to by sponsor or date.     */
/*------------------------------------------------------------------------------*/

session_start();
if(!isset($_SESSION['username'])){

header("location:index.php");
}
?>

<html>
    <head>
        <meta charset="UTF-8">
        <title>Attendance Records: Report by Student</title>
        <link rel="stylesheet" type="text/css" href="style-sign.css">
    </head>
    <body>

<?php

/*
 *  include the config file
 */

include 'nextdex.php';

/*
 * Include subroutines
 */

include 'include.php';



    
 /* 
  * Pass the session username this routine
  * in order to retrieve the data for a user.
  */
    $name = $_SESSION['username'];

$daysofweek = array( "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");

function choose_dates_term(){

/*
 * Connect to Mysql
 */    
    $mysqli = connect_me();
    $student_id = $_POST['stu_id'];
    $sql_a = "SELECT * FROM term";
    $results_a = $mysqli->query($sql_a);
    
        printf("    <table class=\"standard\" cellpadding=\"8\" border=\"0\">\n");
        printf("        <tr>\n");
        printf("            <th>Select Term</th>\n");
        printf("        </tr>\n");
        printf("        <tr>\n");
        
        printf("        <td>\n");
        
        printf("    <form name=\"select_term\" method=\"post\" action=\"");
        printf(htmlspecialchars($_SERVER['PHP_SELF']));
        printf("\">\n");
        printf("        <input type=\"hidden\" name=\"routine\" value=\"choose_week\">\n");
        printf("        <input type=\"hidden\" name=\"stu_id\" value=\"%s\">\n", $student_id);
        printf("        <input type=\"hidden\" name=\"sponsor\" value=\"%s\">\n", $_POST['sponsor']);
        
        printf("            <select name=\"term\">\n");
        while($rows_a = $results_a->fetch_assoc()){
            printf("            <option value=\"%s\">%s To %s</option>\n", $rows_a['date_index'], $rows_a['term_start'], $rows_a['term_end']);
        }
        printf("            </select>\n");
        
        
            
            printf("        </td>\n");
            printf("        </tr>\n");
            printf("        <tr>\n");
            printf("        <td>\n");
            printf("            <input type=\"submit\" name=\"gen_report\" value=\"Generate Report\">\n");
            printf("        </td>\n");
            printf("        </tr>\n");
            
            printf("        </form>\n");
            
            printf("</table>\n");
    
}

function choose_dates_week(){
/*
 * Connect to Mysql
 */    
    $mysqli = connect_me();
    
$stu_id = $_POST['stu_id'];
$stu_sponsor = $_POST['sponsor'];
$term = $_POST['term'];

    $sql_a = "SELECT * FROM term WHERE date_index = '" . $term . "'";
    $results_a = $mysqli->query($sql_a);
    $rows_a = $results_a->fetch_assoc();


    $from_date = $rows_a['term_start'];
    $to_date = $rows_a['term_end'];

    $from_epoch = to_new_date($from_date, 'dmy', 'epoch', '/', NULL);
    $to_epoch = to_new_date($to_date, 'dmy', 'epoch', '/', NULL);

/*----------------------------------------------------------*/
/* Take start and end dates and through some black magic    */
/* wizardry, convert them into an array containing a list   */
/* of all mondays in the term, then get the sunday of each  */
/* week, displaying it as a selection...                    */
/*----------------------------------------------------------*/
    
    $new_from = str_replace('/', '-', $rows_a['term_start']);
    $new_to = str_replace('/', '-', $rows_a['term_end']);
    
    $start_here = date('Y-m-d', strtotime($new_from));
    $beg_end_of_week = beg_end_of_week(0, $start_here);
    
    $all_mondays = mondays_in_range($beg_end_of_week[0], date('Y-m-d', strtotime($new_to)));   
    
    
/*----------------------------------------------*/
/* Take the selected term, divide it into weeks */
/* Then display the first Sunday of each week   */
/* in a select/option drop down.                */
/* This routine is almost identical to the      */
/* routine from report by sponsor, but it adds  */
/* in "all" in order to generate a report which */
/* contains all classes and all weeks for this  */
/* student and also adds in "
/* Also display a select/option drop down for   */
/* for choosing report format.                  */
/*----------------------------------------------*/

printf("<form name=\"choose_date_and_format\" method=\"post\" action=\"");
printf(htmlspecialchars($_SERVER['PHP_SELF']));
printf("\">\n");    
printf("    <input type=\"hidden\" name=\"routine\" value=\"gen_report\">\n");
printf("    <input type=\"hidden\" name=\"stu_sponsor\" value=\"%s\">\n", $stu_sponsor);
printf("    <input type=\"hidden\" name=\"stu_id\" value=\"%s\">\n", $stu_id);
printf("    <input type=\"hidden\" name=\"term\" value=\"%s\">\n", $term);
printf("        <table class=\"left\" cellpadding=\"3\">\n");
printf("            <tr>\n");
printf("                <td>Choose Week:</td>\n");
printf("                <td>\n");
printf("                    <select name=\"date\">\n");
printf("                    <option value=\"cumulative\">Cumulative</option>\n");
$x = 0;
    while($x < count($all_mondays)){
        $sunday_now = date('Y-m-d', strtotime('last Sunday', to_new_date($all_mondays[$x], "ymd", "epoch", "-", "")));
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
    
$results_a->free();
mysqli_close($mysqli); 
    
}

function display_attendance_html(){

/*
 * Connect to Mysql
 */    
    $mysqli = connect_me();

$student_id = $_POST['stu_id'];
$sponsor = $_POST['stu_sponsor'];
$term = $_POST['term'];
$date = $_POST['date'];

    $sql_a = "SELECT * FROM student WHERE stu_index = '" . $student_id . "'";
    $results_a = $mysqli->query($sql_a);
    $rows_a = $results_a->fetch_assoc();
    
   $student_name = $rows_a['Stu_L_name'] . ", " . $rows_a['Stu_F_name'];
   
   if($rows_a['Stu_M_name'] == ""){
       $student_name = $student_name;
    } else {
        $student_name = $student_name . " " . $rows_a['Stu_M_name'];
    }    
printf("    <table class=\"standard\" cellpadding=\"8\" border=\"0\">\n");
printf("        <tr>\n");
printf("            <th>Student Name</th><th>Sponsor</th>\n");
printf("        </tr>\n");    
printf("        <tr>\n");
printf("            <td>%s</td>\n", $student_name);
printf("            <td>%s</td>\n", $sponsor);
printf("        </tr>\n");
printf("</table>\n");
printf("<br>\n");

$sql_b = "SELECT * FROM enrolled WHERE stu_index = '" . $student_id ."' AND status = '0'";
$results_b = $mysqli->query($sql_b);

/*------------------------------------------------------------------------------*/
/* Begin first loop in routine which displays data - this first loop gets       */
/* a list of courses a student is enrolled in - we'll call this loop #1         */
/*------------------------------------------------------------------------------*/

while($rows_b = $results_b->fetch_assoc()){

/*------------------------------------------------------------------------------*/
/* Get class name/instructor name from database and display it                  */
/*------------------------------------------------------------------------------*/

$sql_c = "SELECT * FROM classes WHERE prog_index = '" . $rows_b['prog_index'] . "'";
$results_c = $mysqli->query($sql_c);
$rows_c = $results_c->fetch_assoc();

$sql_d = "SELECT * FROM U_ops WHERE U_index = '" . $rows_c['inst_index'] . "'";
$results_d = $mysqli->query($sql_d);
$rows_d = $results_d->fetch_assoc();

    printf("<table class=\"standard\" cellpadding=\"8\" border=\"0\">\n");
    printf("    <tr>\n");
    printf("        <th>Class</th>\n");
    printf("        <th>Class Description</th>\n");
    printf("        <th>Instructor</th>\n");
    printf("    </tr>\n");
    printf("    <tr>\n");
    printf("        <td>%s</td><td>%s</td><td>%s %s</td>", $rows_c['program'], $rows_c['prog_id'], $rows_d['U_F_name'], $rows_d['U_L_name']);
    printf("    </tr>\n");
//    printf("</table>\n");

    printf("    <tr>\n");
    printf("        <th colspan=\"2\">Date</th><th>Attendance</th>\n");
    printf("    </tr>\n");
    
/*------------------------------------------------------------------------------*/
/* Query which does the heavy lifting: get the attendance record for this       */
/* student, for each class they are enrolled in and display in order            */
/* along with attendance by date.                                               */
/*------------------------------------------------------------------------------*/

/*------------------------------------------------------*/
/* Set up dates to parse                                */
/*------------------------------------------------------*/
   
    if($date == "cumulative"){
        $sql_time_date = "SELECT term_start FROM term WHERE date_index = '" . $term ."'";
        $results_time_date = $mysqli->query($sql_time_date);
        $rows_time_date = $results_time_date->fetch_assoc();
        $from_date = to_new_date($rows_time_date['term_start'], 'dmy', 'epoch', '/', NULL);
        $to_date = time();
        $results_time_date->free();
    } else {
        $from_date = to_new_date($date, 'ymd', 'epoch', '-', NULL);
        $to_date = strtotime('next saturday', $from_date);
    }
    
    
$sql_e = "SELECT * FROM attend WHERE stu_index = '" . $student_id . "' AND course_index = '" . $rows_b['prog_index'] . "' AND epoch BETWEEN '" . $from_date . "' AND '" . $to_date . "'";
$results_e = $mysqli->query($sql_e);

/*------------------------------------------------------------------------------*/
/* Begin second loop.                                                           */
/* This loop is the one which actually gets the attendance and displays it      */
/* We'll call this (are you ready?) Loop #2! :P                                 */
/*------------------------------------------------------------------------------*/ 

//printf("<table class=\"standard\" cellpadding=\"8\">\n");
    
while($rows_e = $results_e->fetch_assoc()){
    
    /*--------------------------------------------------------------------------*/
    /* OK - yet one more query - this one retrieves the attendance code         */
    /*--------------------------------------------------------------------------*/
    
    $sql_f = "SELECT * FROM attend_code WHERE attend_index = '" . $rows_e['attend_code'] . "'";
    $results_f = $mysqli->query($sql_f);
    $rows_f = $results_f->fetch_assoc();

    /*--------------------------------------------------------------------------*/
    /* OK, Let's display the actual attendance record now                       */
    /*--------------------------------------------------------------------------*/

    printf("    <tr>\n");
    printf("        <td colspan=\"2\">\n");
    printf("            %s", date('l-M-d-Y', $rows_e['epoch']));
    printf("        </td>\n");
    printf("        <td>%s</td>", $rows_f['attend_code']);
    printf("    </tr>\n");
}    
    printf("</table>\n");
    printf("<br>\n");
}
/*------------------------------------------------------------------------------*/
/* End of loop #1                                                               */
/*------------------------------------------------------------------------------*/



$results_a->free();
mysqli_close($mysqli);

}

function display_attendance_docx(){
    printf("Sorry, no workee yet - Just HTML reports are working.<br>\n");
}

    if(isset($_POST['routine'])){
        $gotvalue = $_POST['routine'];
    }
    else {
        $gotvalue = $_GET['routine'];           
    }
    
    if($gotvalue == NULL){
        $gotvalue = "";
    }

    if($_POST['report_format'] == NULL){
        $gotvalue = $gotvalue;
    } else if ($_POST['report_format'] == "1"){
        $gotvalue = "gen_report_docx";
    } else if ($_POST['report_format'] == "3"){
        $gotvalue = "gen_report_html";
    }
    
switch($gotvalue){

    case "gen_report_docx";
        display_attendance_docx();
        break;
    case "gen_report_html";
        display_attendance_html();
        break;
    case "choose_week";
        choose_dates_week();
        break;
    default;
        choose_dates_term();
        
}    
    
?>

        
    </body>
</html>