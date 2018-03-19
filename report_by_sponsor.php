<?php

/*
 * Attendance Recording
 * 
 * OSATT - Open Source Attendance
 * 
 * report_by_sponsor.php
 * Copyright (C) 2002, 2003, 2016 Ken Plumbly <frotusroom@gmail.com>
 *
 * report_by_sponsor.php is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * report_by_sponsor.php is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along
 * with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*------------------------------------------------------------------------------*/
/* report_by_sponsor.php generates a html file categorizing attendance by       */
/* sponsor, course and student, as some reports are for specific agencies       */
/* who obviously are not going to be interested in reading attendance records   */
/* for students they did not pay for.                                           */
/*------------------------------------------------------------------------------*/
/* This set of routines shares a lot in common with html_reports.php, so this   */
/* presented an opportunity for some code cleanup, which will eventually make   */
/* its way back into html_reports.php                                           */
/*------------------------------------------------------------------------------*/

session_start();
if(!isset($_SESSION['username'])){

header("location:index.php");
}


/*------------------------------------------------------------------------------*/
/* Break out of php, output the html header, then re-enter php.                 */
/*------------------------------------------------------------------------------*/

?>


<html>
    <head>
        <meta charset="UTF-8">
        <title>Attendance Records</title>
        <link rel="stylesheet" type="text/css" href="style-sign.css">
    </head>
    <body>

<?php

/*
 *  include the config file
 */

include 'nextdex.php';

/*
 * Include global subroutines
 */

include 'include.php';

$daysofweek = array( "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
$num_of_days_in_week = 7;

/*
 * Connect to Mysql
 */    

$mysqli = connect_me();
    
    
/*------------------------------------------------------------------------------*/
/* Do we REALLY need these next two lines? If not, we will remove them.         */
/*------------------------------------------------------------------------------*/
    
    $session_q = "SELECT prog_index FROM classes";
    $results_q = $mysqli->query($session_q);

/*------------------------------------------------------------------------------*/
/* Let's check and see if our user is an admin or and instructor...             */
/* If they are an admin, they get all the reports for all the classes for       */
/* for the selected sponsor, else they get only the reports for the classes     */
/* they instruct, right now this is a moot exercise as only admins and pa's     */
/* have the menu option to generate reports by sponsor, but I decided to        */
/* place this in here anyway, in case we need it in the future.                 */
/*------------------------------------------------------------------------------*/
    
$session_qa = "SELECT * FROM U_ops WHERE log_name = '" . $_SESSION['username'] . "'";

$results_qa = $mysqli->query($session_qa);
$rows_qa = $results_qa->fetch_assoc();
$inst_numberq = $rows_qa['U_index'];
$priv_levelq = $rows_qa['priv_level'];

    if($priv_levelq == "1"){
        $priv = 1;
    } elseif ($priv_levelq == "2"){
        $priv = 2;
    } else {
        $priv = 3;
    }    
    
    if($priv == 3){
        $session_qb = "SELECT prog_index FROM classes WHERE inst_index = '" . $inst_numberq . "'";
        $results_qb = $mysqli->query($session_qb);
        $rows_qb = $results_qb->fetch_assoc();
        $instructor_id = $rows_qb['prog_index'];
    } else {
        $session_qb = "SELECT prog_index FROM classes";
        $results_qb = $mysqli->query($session_qb);
    }
    
/*------------------------------------------------------------------------------*/
/* Get all of definitions for the attendance codes.                             */
/* then display them in a stand-alone table at the top of the page.             */
/*------------------------------------------------------------------------------*/    
    
$att_sql = "SELECT * FROM attend_code";
$att_results = $mysqli->query($att_sql);

        printf("        <table class=\"standard\" cellpadding=\"8\">\n");
        printf("            <tr>\n");
  
            while($att_rows = $att_results->fetch_assoc()){
                printf("                <td>%s = %s</td>\n", $att_rows['attend_code'], $att_rows['attend_desc']);
            }
        printf("            </tr>\n");
        printf("        </table>\n");
        printf("    <br>\n");
        printf("    <br>\n");
        $att_results->free();  
        
/*------------------------------------------------------------------------------*/
/* Since this is a report by sponsor; let's get the sponsor name and            */
/* display it in a small table at the top of the page right under the           */
/* attendence code definition table.                                            */
/*------------------------------------------------------------------------------*/        
        
$spons_id = $_GET['sponsor']; 
$first_date = $_GET['date'];
   
$spons_sql = "SELECT * FROM sponsor WHERE sponsor_index = '" . $spons_id . "'";

$spons_results = $mysqli->query($spons_sql);
$spons_rows = $spons_results->fetch_assoc();

printf("            <table class=\"standard\" cellpadding=\"8\">\n");
printf("                <tr>\n");
printf("                    <td><b>Sponsor: </b></td><td> %s</td>\n", $spons_rows['sponsor']);
printf("                </tr>\n");
printf("            </table>\n");
printf("         <br>\n");
printf("         <br>\n");

$spons_results->free();

/*------------------------------------------------------------------------------*/
/* Move the pointer for class ids back to 0                                     */
/*------------------------------------------------------------------------------*/
      
mysqli_data_seek($results_qb, 0);

while($rows_qb = $results_qb->fetch_assoc()){
    $class_ids[] = $rows_qb['prog_index'];    
}     

$min = 0;
$min_week_2 = 0;
$max = count($class_ids);

for($min = 0;$min < $max;$min++){

        /*----------------------------------------------------------------------*/
        /* Using my new function "to_new_date" in order to cut down on cruft.   */
        /* Need to convert other functions to use this instead of all the       */
        /* function calls upon function calls used previously.                  */
        /* 4 lines of code as opposed to 15 lines of code.                      */
        /*----------------------------------------------------------------------*/
    
        $epoch_1_start = to_new_date($first_date, "ymd", "epoch", "-", "");     // Give me the epoch for the first Sunday of week 1
        $epoch_1_end = strtotime("next Saturday", $epoch_1_start);              // Give me the epoch for the first Saturday of week 1
        $epoch_2_start = strtotime("next Sunday", $epoch_1_end);                // Give me the epoch for the first Sunday of week 2
        $epoch_2_end = strtotime("next Saturday", $epoch_1_end);                // Give me the epoch for the first Saturday of week 2
        
        /*----------------------------------------------------------------------*/
        /* Return an array of dates for week 1 & 2                              */
        /* this should be a bit tidier than the same spot in html_reports.php   */
        /* if this works, it will be time to clean that up as well.             */
        /*----------------------------------------------------------------------*/
        
        $week_one_dates = date_range(date("Y-m-d", $epoch_1_start), date("Y-m-d", $epoch_1_end));
        $week_two_dates = date_range(date("Y-m-d", $epoch_2_start), date("Y-m-d", $epoch_2_end));
   
    /*--------------------------------------------------------------------------*/
    /* OK let's do a query to get the students associated with this             */
    /* sponsor and class, and their attendance records for the related          */
    /* dates and display it all in a nice organized table                       */
    /* (Not using joins here, probably should, this is the old way to do it     */
    /* and supposedly; joins are better, but they are somewhat confusing, I     */
    /* should update the queries at some point into joins instead.)             */
    /*--------------------------------------------------------------------------*/
        
    $sql_a = "SELECT * FROM student, attend, enrolled WHERE student.sponsor = '" . $spons_id . "' AND attend.course_index = '" . $class_ids[$min] . "' AND enrolled.prog_index = '" . $class_ids[$min] . "' AND enrolled.stu_index = student.stu_index AND enrolled.status = '0' AND attend.epoch BETWEEN " . $epoch_1_start . " AND " . $epoch_1_end;
    //echo "Query = " . $sql_a . "<br>\n";
    $results_a = $mysqli->query($sql_a);
    $rows_a = $results_a->fetch_assoc();    
    
    $sql_b = "SELECT * FROM classes WHERE prog_index = '" . $class_ids[$min] . "'";
    $results_b = $mysqli->query($sql_b);
    $rows_b = $results_b->fetch_assoc();
    
    $sql_c = "SELECT * FROM U_ops WHERE U_index = '" . $rows_b['inst_index'] . "'";
    $results_c = $mysqli->query($sql_c);
    $rows_c = $results_c->fetch_assoc();
    
    if($rows_a != NULL){
        mysqli_data_seek($results_a,0);                                         // Make sure the record is reset to the start of the row. Else records get chopped off
        //mysqli_data_seek($results_a, 0);
        
        /*----------------------------------------------------------------------*/
        /* Print outside table which contains two weeks side by side            */
        /*----------------------------------------------------------------------*/
        
        printf("                <table class=\"standard\" cellpadding=\"0\" cellspacing=\"0\">\n");
        printf("                    <tr>\n");
        printf("                        <td>\n");
        
        
        /*----------------------------------------------------------------------*/
        /* Print a table for week 1 *IF* there is any attendance records for    */
        /* That sponsor and class for that week.                                */
        /*----------------------------------------------------------------------*/
        printf("                <table class=\"standard\" cellpadding=\"8\" cellspacing=\"8\">\n");
        printf("                    <tr>\n");
        printf("                        <td colspan=\"8\">\n");
        printf("                            <table class=\"noborder\" cellpadding=\"8\" cellspacing=\"8\">\n");
        printf("                                <tr>\n");
        
        /*----------------------------------------------------------------------*/
        /* Display the name of the course                                       */
        /* Display the instructor name                                          */
        /*----------------------------------------------------------------------*/
        
        printf("                                    <td halign=\"center\">\n");
        printf("                                        <b>%s</b></td>\n", $rows_b['program']);
        printf("                                    <td halign=\"center\">%s %s</td>\n", $rows_c['U_F_name'], $rows_c['U_L_name']);
        
        /*----------------------------------------------------------------------*/
        
        printf("                                </tr>\n");
        printf("                            </table>\n");
        printf("                        </td>\n");
        printf("                    </tr>\n");
        printf("                    <tr>\n");
        printf("                        <td colspan=\"8\">\n");
        printf("                            <table class=\"noborder\" cellpadding=\"8\" cellspacing=\"8\">\n");
        printf("                                <tr>\n");
        
        /*----------------------------------------------------------------------*/
        /* Print out the day and date for the first day of this week            */
        /*----------------------------------------------------------------------*/
        
        printf("                                    <td valign=\"top\"><b>Week Beginning: </b></td>\n");
        printf("                                    <td valign=\"top\">%s</td>\n", date(" D M d, Y", $epoch_1_start));
        
        /*----------------------------------------------------------------------*/
        
        printf("                                </tr>\n");
        printf("                            </table>\n");
        printf("                        </td>\n");
        printf("                    </tr>\n");
        
        printf("                    <tr>\n");
        printf("                        <td>Student Name</td>\n");
        
        /*----------------------------------------------------------------------*/
        /* Print out the three letter abbreviations for Sunday -> Saturday      */
        /* On one line.                                                         */
        /*----------------------------------------------------------------------*/
        
        $x = 0;
        for($x = 0;$x < $num_of_days_in_week;$x++){
            printf("                        <td bgcolor=\"#cccccc\"><b>%s</b></td>\n", $daysofweek[$x]);
        }
        printf("                    </tr>\n");
        
        /*----------------------------------------------------------------------*/
        
        printf("                    <tr>\n");
        printf("                        <td> </td>\n");
        
        /*----------------------------------------------------------------------*/
        /* OK, print the actual numeric days of the week under the 3 letter     */
        /* abbreviations for the days of the week, need to do this alteration   */
        /* in html_reports, in which this identical spot has 10 lines of code   */
        /* where this is much nicer, more compact.                              */
        /*----------------------------------------------------------------------*/
        
        $x = 0;
        for($x = 0;$x < 7;$x++){            
            printf("                        <td bgcolor=\"#f2f2f2\">%s</td>\n", date("d", strtotime(str_replace("/", "-", $week_one_dates[$x]))));
            
        }
        
        /*----------------------------------------------------------------------*/
        
        printf("                    </tr>\n");
        
        /*----------------------------------------------------------------------*/
        /* Now for some meat!                                                   */
        /* Print student name and attendance records                            */
        /* Making sure that the right record is on the right day                */
        /*                                                                      */
        /* Orc: Yeah! Why can't we 'ave some meat?                              */
        /*      'ere! Wha' abou' them? They're fresh!                           */
        /*----------------------------------------------------------------------*/
        
        $sql_d = "SELECT * FROM enrolled INNER JOIN student ON (enrolled.stu_index = student.stu_index AND enrolled.prog_index = '" . $class_ids[$min] . "' AND enrolled.status = 0 AND student.sponsor = '" . $spons_id . "') ORDER BY student.Stu_L_name, student.Stu_F_name";
        $results_d = $mysqli->query($sql_d);
        
        while($rows_d = $results_d->fetch_assoc()){
        printf("                    <tr>\n");
        printf("                        <td>%s, %s %s</td>\n", $rows_d['Stu_L_name'], $rows_d['Stu_F_name'], $rows_d['Stu_M_name'] );
        
        $x = 1;
            for($x = 0; $x < 7;$x++){
        
        /*----------------------------------------------------------------------*/
        /* Take the array $week_one_dates (produced by date range) and turn it  */
        /* into an epoch in order to see if there is an attendance record for   */
        /* a particular day - if there is a match; print the attendance info    */
        /* for that day, if there is nothing for that day; print a space        */
        /* character in the table box instead.                                  */
        /*----------------------------------------------------------------------*/
                
        $epoch = strtotime(str_replace('/', '-', $week_one_dates[$x]));
        
        /*----------------------------------------------------------------------*/
        /* get the attendance records for a course and student                  */
        /*----------------------------------------------------------------------*/
        
        $sql_e = "SELECT * FROM attend WHERE course_index = " . $class_ids[$min] . " AND epoch = " . $epoch . " AND stu_index = " . $rows_d['stu_index'];
        $results_e = $mysqli->query($sql_e);
        $rows_e = $results_e->fetch_assoc();
            if(mysqli_num_rows($results_e) > 0){
                $sql_f ="SELECT * FROM attend_code WHERE attend_index = '" . $rows_e['attend_code'] . "'";
                $result_f = $mysqli->query($sql_f);
                $row_f = $result_f->fetch_assoc();
                printf("                <td>%s</td>\n", $row_f['attend_code']);
                $result_f->free();
                } else {
                    printf("                <td bgcolor=\"#cccccc\"></td>\n");
                }
        
            }
        
        printf("                    </tr>\n");
        }
        /*----------------------------------------------------------------------*/
        
        //printf("                    <tr>\n");
        printf("                    </td>\n");
        printf("                </tr>\n");
        printf("            </table>\n");
        /*----------------------------------------------------------------------*/
        /* End of left table cell                                               */
        /*----------------------------------------------------------------------*/
        
        printf("                    </td>\n");
        
        /*----------------------------------------------------------------------*/
        /* Start second table cell in which is the right table.                 */
        /*----------------------------------------------------------------------*/
        
        printf("                    <td>\n");
        
        /*----------------------------------------------------------------------*/
        /* Print a table for week 2 *IF* there is any attendance records for    */
        /* That sponsor and class for week 1.                                   */
        /*----------------------------------------------------------------------*/
        
        printf("            <table class=\"standard\" cellpadding=\"8\" cellspacing=\"8\">\n");
        printf("                <tr>\n");
        printf("                    <td colspan=\"7\">\n");
        printf("                        <table class=\"noborder\" cellpadding=\"8\" cellspacing=\"8\">\n");
        printf("                            <tr>\n");
        
        /*----------------------------------------------------------------------*/
        /* instead of instructor name and course name, print out the date the   */
        /* record was last updated                                              */
        /*----------------------------------------------------------------------*/
        
        printf("                                <td>Last Updated </td>\n");
        printf("                                <td> %s </td>\n", date("D M d Y", $rows_a['last_updated']));
        
        /*----------------------------------------------------------------------*/
        
        printf("                            </tr>\n");
        printf("                        </table>\n");
        printf("                    </td>\n");
        printf("                </tr>\n");
        printf("                <tr>\n");
        printf("                    <td colspan=\"7\">\n");
        printf("                        <table class=\"noborder\" cellpadding=\"8\" cellspacing=\"8\">\n");
        printf("                            <tr>\n");
        
        /*----------------------------------------------------------------------*/
        /* Print out the day and date for the first day of this week            */
        /*----------------------------------------------------------------------*/
        
        printf("                                <td><b>Week Beginning: </b></td>\n");
        printf("                                <td> %s</td>\n", date("D M d Y", $epoch_2_start));
        printf("                            </tr>\n");
        printf("                        </table>\n");
        printf("                    </td>");
        printf("                </tr>\n");
        printf("                <tr>\n");
        
        $x = 0;
        for($x = 0;$x < $num_of_days_in_week;$x++){
            printf("                    <td bgcolor=\"#cccccc\"><b>%s</b></td>\n", $daysofweek[$x]);
        }
        printf("                </tr>\n");
        printf("                <tr>\n");
        
        /*----------------------------------------------------------------------*/
        /* OK, print the actual numeric days of the week under the 3 letter     */
        /* abbreviations for the days of the week, need to do this alteration   */
        /* in html_reports, in which this identical spot has 10 lines of code   */
        /* where this is much nicer, more compact.                              */
        /*----------------------------------------------------------------------*/
        
        $x = 0;
        for($x = 0;$x < 7;$x++){            
            printf("                        <td bgcolor=\"#f2f2f2\">%s</td>\n", date("d", strtotime(str_replace("/", "-", $week_two_dates[$x]))));
            
        }   
        printf("                </tr>\n");
        
        /*----------------------------------------------------------------------*/
        /* Now for some more meat!                                              */
        /* Print student name and attendance records                            */
        /* Making sure that the right record is on the right day                */
        /*                                                                      */
        /* Urukhai: They, are not, for eating!                                  */
        /*          Why? Do they give good sport?                               */
        /*----------------------------------------------------------------------*/
        
        $sql_g = "SELECT * FROM enrolled INNER JOIN student ON (enrolled.stu_index = student.stu_index AND enrolled.prog_index = '" . $class_ids[$min] . "' AND enrolled.status = 0 AND student.sponsor = '" . $spons_id . "') ORDER BY student.Stu_L_name, student.Stu_F_name";
        $results_g = $mysqli->query($sql_g);
        
        while($rows_g = $results_g->fetch_assoc()){

        printf("                <tr>\n");
            
        $x = 1;
            for($x = 0; $x < 7;$x++){
        
        /*----------------------------------------------------------------------*/
        /* Take the array $week_one_dates (produced by date range) and turn it  */
        /* into an epoch in order to see if there is an attendance record for   */
        /* a particular day - if there is a match; print the attendance info    */
        /* for that day, if there is nothing for that day; print a space        */
        /* character in the table box instead.                                  */
        /*----------------------------------------------------------------------*/
    
                
        $epoch = strtotime(str_replace('/', '-', $week_two_dates[$x]));
        
        /*----------------------------------------------------------------------*/
        /* get the attendance records for a course and student for week 2       */
        /*----------------------------------------------------------------------*/
  
        
        $sql_h = "SELECT * FROM attend WHERE course_index = " . $class_ids[$min] . " AND epoch = " . $epoch . " AND stu_index = " . $rows_g['stu_index'];
        $results_h = $mysqli->query($sql_h);
        $rows_h = $results_h->fetch_assoc();
            if(mysqli_num_rows($results_h) > 0){
                $sql_h ="SELECT * FROM attend_code WHERE attend_index = '" . $rows_e['attend_code'] . "'";
                $result_h = $mysqli->query($sql_f);
                $row_h = $result_h->fetch_assoc();
                printf("                <td>%s</td>\n", $row_h['attend_code']);
                $result_h->free();
                } else {
                    printf("                <td bgcolor=\"#cccccc\">&nbsp;</td>\n");
                }
        
            }
        
        printf("                    </tr>\n");
        }        
                
        printf("            </table>");
        
        /*----------------------------------------------------------------------*/
        /* end right table cell                                                 */
        /* and end table which contains both tables                             */
        /*----------------------------------------------------------------------*/
        
        printf("                    </td>\n");
        printf("                </tr>\n");
        printf("            </table>\n");
        
        /*----------------------------------------------------------------------*/
        /* This is here to print a space between tables, if there are multiple  */
        /* tables to display.                                                   */
        /*----------------------------------------------------------------------*/
        
        printf("<br>\n");

    }
      
}

mysqli_close($mysqli);
    
printf("    </body>\n");
printf("</html>"); 

?>