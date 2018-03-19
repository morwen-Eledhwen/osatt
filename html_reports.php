<?php

/*
 * Attendance Report generation
 * 
 * html_reports.php
 * Copyright (C) 2002, 2003, 2016 Ken Plumbly <frotusroom@gmail.com>
 *
 * html_reports.php is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * html_reports.php is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along
 * with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * This file generates reports in html format
 * 
 * 
 * 
 */

session_start();
if(!isset($_SESSION['username'])){

header("location:index.php");
}
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
 * Include subroutines
 */

include 'include.php';

$daysofweek = array( "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");

/*
 * Connect to Mysql
 */    
    $mysqli = connect_me();
    
/*------------------------------------------------------------------------------*/
    $session_q = "SELECT prog_index FROM classes";
    $results_q = $mysqli->query($session_q);


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
/* Ok, some things, can be simple, but then, there was this :(                  */
/* Take the classes which the person generating reports has access to, take     */
/* their course indexes and write them into an array.                           */
/* this array will be used to generate the $_SESSION[$query_fodder_#] variable  */
/* later on, which will allow the reporting routines to access the data sent in */
/* the session variable, query the database with the correct info and print the */
/* results.                                                                     */
/*------------------------------------------------------------------------------*/
    
mysqli_data_seek($results_qb, 0);

    while($rows_qb = $results_qb->fetch_assoc()){
        $query_session = sprintf("query_fodder_%s", $rows_qb['prog_index']);
        $class_ids[] = $rows_qb['prog_index'];
    } 

/*------------------------------------------------------------------------------*/    

        printf("        <table class=\"standard\" cellpadding=\"8\">\n");
        printf("            <tr>\n");
        $sqlb = "SELECT * FROM attend_code";
        $resultb = $mysqli->query($sqlb);
        while($rowsb = $resultb->fetch_assoc()){
            printf("                <td>%s = %s</td>\n", $rowsb['attend_code'], $rowsb['attend_desc']);
        }
        printf("            </tr>\n");
        printf("        </table>\n");
        printf("    <br>\n");
        printf("    <br>\n");
        $resultb->free();    
    
    $min = 0;
    $min_week_2 = 0;
    $max = count($class_ids);
   
    while($min < $max){
        
        $new_id = sprintf("query_fodder_%s", $class_ids[$min]);
        $query_fodder = $_SESSION[$new_id];
        
    printf("            <table class=\"standard\" cellpadding=\"0\" cellspacing=\"0\">\n");
    printf("                <tr>\n");
    printf("                    <td valign=\"top\">\n");

            $rough_date = beg_end_of_week(0, $query_fodder[0]);
            $epoch_1 = strtotime($query_fodder[0]);
            $epoch_2 = strtotime($rough_date[1]);
        
            $sqla = "SELECT * FROM classes, attend WHERE attend.course_index = classes.prog_index AND classes.prog_index = " . $query_fodder[3] . " AND attend.epoch BETWEEN " . $epoch_1 . " AND " . $epoch_2 . "";
            $results = $mysqli->query($sqla);
            $rowsa = $results->fetch_assoc();  
              
            $from_date = date('d/m/Y', $epoch_1);
            $interval = strtotime($query_fodder[0]);
            $to_date =  date('d/m/Y', strtotime('next Saturday', $epoch_1));
            $num_of_course_weeks = numofweeks($from_date, $to_date);
            
            $odd_date_1 = str_replace('/', '-', $to_date);
            $odd_date_3 = date('Y-m-d', strtotime($odd_date_1));
            $odd_date_2 = str_replace('/', '-', $from_date); 
            $odd_date_4 = date('Y-m-d', strtotime($odd_date_2));
           
            $allmondays = mondays_in_range($odd_date_4, $odd_date_3);
            $week_one_dates = date_range($odd_date_4, $odd_date_3);

            // Week two date stuff
            
            $begin_week_two = date('d/m/Y', strtotime('next Sunday', $epoch_1)); //Week two version of "$from"
            $end_weeK_2 = date('d/m/Y', strtotime('next Saturday', $epoch_2));   //Week two version of $to"
                  
            $epoch_3 = strtotime('next Sunday', $epoch_1);
            $epoch_4 = strtotime('next Saturday', $epoch_2);
            $parse_1 = str_replace('/', '-', $end_weeK_2);
            //$parsed_1 = date('Y-m-d', strtotime($parse_1));
            $parse_2 = str_replace('/', '-', $begin_week_two);
            $parsed_2 = date('Y-m-d', strtotime($parse_2));
            
            // Kludge alert!
            // for some odd reason, if the chosen date is November 11/2016
            // the to date chosen is the previous Saturday, which causes
            // the script to completely implode, this corrects that.
            
            if($parse_1 === "12-11-2016"){
                $parsed_1 = "2016-11-19";
            } else {
                $parsed_1 = date('Y-m-d', strtotime($parse_1));
            }
             
            $week_two_dates = date_range($parsed_2, $parsed_1);
            
        if(mysqli_num_rows($results) === 0){
            $min++;
        } else {         
             
            $sqlc = "SELECT * FROM U_ops WHERE U_index = '" . $rowsa['inst_index'] . "'";
            $resultsc = $mysqli->query($sqlc);
            $rowsc = $resultsc->fetch_assoc();
            
            $sqlj = "SELECT last_updated FROM classes WHERE prog_index = '" . $query_fodder[3] . "'";
            $resultsj = $mysqli->query($sqlj);
            $rowsj = $resultsj->fetch_assoc();
            
            if($rowsj['last_updated'] == 0){
                $lastUpdated = "N/C";
            } else {
                $lastUpdated = date('D, M j, Y - g:i a', $rowsj['last_updated']);
                
            }
            
            printf("            <table class=\"standard\" cellpadding=\"8\">\n");
            printf("                <tr>\n");
            printf("                    <td colspan=\"8\" align=\"left\" style=\"height:60px\">\n");
            printf("                        <table class=\"noborder\" cellpadding=\"1\" cellspacing=\"1\" width=\"99%%\">\n");
            printf("                            <tr>\n");
            printf("                                <td align=\"left\"><b>%s</b></td><td><b><p align=\"right\">Instructor: %s %s</p></b></td>\n", $rowsa['program'], $rowsc['U_F_name'], $rowsc['U_L_name']);
            printf("                            </tr>\n");            
            printf("                        </table>\n");
            printf("                    </td>\n");
            printf("                </tr>\n");
            printf("                <tr>\n");
            printf("                    <td>\n");
            printf("                    </td>\n");
            printf("                    <td colspan=\"7\">\n");
            printf("                        <b>Week Beginning:</b> %s\n", date('D F d', $epoch_1));
            printf("                    </td>\n");
            printf("                </tr>\n");
            printf("                <tr>\n");
            printf("                    <td>\n");
            printf("                        Student Name\n");
            printf("                    </td>\n");
           
            $w_counter = 0;
                for($w_counter = 0;$w_counter < 7;$w_counter++){
                    printf("                        <td bgcolor=\"#cccccc\"><b>%s</b></td>\n", $daysofweek[$w_counter]);
                }
                
            printf("            </tr>\n");
            printf("            <tr>\n");
            printf("                <td>\n");
            printf("                </td>\n");
            
            $dow_count = 0;
            while($dow_count < 7){
                
                $printable_date = $week_one_dates[$dow_count];
                $printable_date = str_replace('/', '-', $printable_date); 
                $epoch = strtotime($printable_date);
                $printable_date = date('d', strtotime($printable_date));

                
                printf("                <td bgcolor=\"#f2f2f2\">\n");
                printf("                    <b>%s</b>\n", $printable_date);
                printf("                </td>\n");
                $dow_count++;
            }
            printf("            </tr>\n");
          
            mysqli_data_seek($results, 0);
            
            $sqld = "SELECT * FROM enrolled INNER JOIN student ON (enrolled.stu_index = student.stu_index AND enrolled.prog_index = '" . $query_fodder[3] . "' AND enrolled.status = 0) OR (enrolled.stu_index = student.stu_index AND enrolled.prog_index = '" . $query_fodder[3] . "' AND enrolled.status = 2) ORDER BY student.Stu_L_name, student.Stu_F_name";

            $resultsd = $mysqli->query($sqld);
            while($rowsd = $resultsd->fetch_assoc()){
                printf("        <tr>\n");
                printf("            <td>%s, %s %s</td>\n", $rowsd['Stu_L_name'], $rowsd['Stu_F_name'], $rowsd['Stu_M_name']);
            
                $x = 1;
                    for($x = 0; $x < 7;$x++){
                        $dates = str_replace('/', '-', $week_one_dates[$x]);
                        $epoch = strtotime($dates);
                        
                $sqlb = "SELECT * FROM attend WHERE course_index = " . $query_fodder[3] . " AND epoch = " . $epoch . " AND stu_index = " . $rowsd['stu_index'];

                 
                $resultsb = $mysqli->query($sqlb);
                $rowsb = $resultsb->fetch_assoc();
                if(mysqli_num_rows($resultsb) > 0){
                    $sql ="SELECT * FROM attend_code WHERE attend_index = '" . $rowsb['attend_code'] . "'";
                    $result = $mysqli->query($sql);
                    $row = $result->fetch_assoc();
                    printf("                <td>%s</td>\n", $row['attend_code']);
                    $result->free();
                } else {
                    printf("                <td bgcolor=\"#cccccc\"></td>\n");
                    }
                
                }
            printf("        </tr>\n");
            }
            printf("        </table>\n"); 
            
    $min++;
/*------------------------------------------------------------------------------*/
/* Second week                                                                  */
/*------------------------------------------------------------------------------*/
    
    printf("                    </td>\n");
    printf("                    <td valign=\"top\">\n");
    
        printf("            <table class=\"standard\" cellpadding=\"8\">\n");
        printf("                <tr>\n");
        printf("                    <td colspan=\"7\" align=\"left\" style=\"height:60px\">\n");
        printf("                        <table class=\"noborder\" cellpadding=\"8\" cellspacing=\"1\" width=\"100%%\">\n");
        printf("                            <tr>\n");
        printf("                                <td align=\"left\">Last Updated:</td><td>%s</td>\n", $lastUpdated);
        printf("                            </tr>\n");            
        printf("                        </table>\n");
        printf("                    </td>\n");
        printf("                </tr>\n");
        printf("                <tr>\n");
        printf("                    <td colspan=\"7\">\n");
        printf("                        <b>Week Beginning:</b> %s\n", date('D F d', $epoch_3));
        printf("                    </td>\n");
        printf("                </tr>\n");
            
            $w_counter = 0;
                for($w_counter = 0;$w_counter < 7;$w_counter++){
                    printf("                    <td bgcolor=\"#cccccc\"><b>%s</b></td>\n", $daysofweek[$w_counter]);
                }
        printf("                </tr>\n");
        printf("                <tr>\n");
        
        $dow_count = 0;
            while($dow_count < 7){
                
                $printable_date = $week_two_dates[$dow_count];
                $printable_date = str_replace('/', '-', $printable_date); 
                $epoch = strtotime($printable_date);
                $printable_date = date('d', strtotime($printable_date));

                
                printf("                <td bgcolor=\"#f2f2f2\">\n");
                printf("                    <b>%s</b>\n", $printable_date);
                printf("                </td>\n");
                $dow_count++;
            }
            printf("            </tr>\n");
            
            $sqld_week_2 = "SELECT * FROM enrolled INNER JOIN student ON (enrolled.stu_index = student.stu_index AND enrolled.prog_index = '" . $query_fodder[3] . "' AND enrolled.status = 0) OR (enrolled.stu_index = student.stu_index AND enrolled.prog_index = '" . $query_fodder[3] . "' AND enrolled.status = 2) ORDER BY student.Stu_L_name, student.Stu_F_name";

            $resultsd_week_2 = $mysqli->query($sqld_week_2);

            while($rowsd_week_2 = $resultsd_week_2->fetch_assoc()){
                $x = 0;
                    while($x < 7){
                        $dates = str_replace('/', '-', $week_two_dates[$x]);
                        $epoch = strtotime($dates);
                        //echo "Sunday = " . $parsed_2 . "<br>\n";
                        //echo "Saturday = " . $parse_1 . "<br>\n";
                        //echo "Epoch 1 = " . $epoch_1 . "<br>\n";
                        //echo "Epoch 2 = " . $epoch_2 . "<br>\n";
                        
                $sqlb_week_2 = "SELECT * FROM attend WHERE course_index = " . $query_fodder[3] . " AND epoch = " . $epoch . " AND stu_index = " . $rowsd_week_2['stu_index'];
                    
                 
                $resultsb_week_2 = $mysqli->query($sqlb_week_2);
                $rowsb_week_2 = $resultsb_week_2->fetch_assoc();
                //echo "rowsb_week_2 = " . $rowsb_week_2 . "<br>\n";
                if(mysqli_num_rows($resultsb_week_2) > 0){
                    $sql_week_2 ="SELECT * FROM attend_code WHERE attend_index = '" . $rowsb_week_2['attend_code'] . "'";
                    $result_week_2 = $mysqli->query($sql_week_2);
                    $row_week_2 = $result_week_2->fetch_assoc();
                   printf("                <td>%s</td>\n", $row_week_2['attend_code']);
                    $result_week_2->free();
                } else {
                    printf("                <td bgcolor=\"#cccccc\">&nbsp;</td>\n");
                    }
                $x++;
                }
            printf("        </tr>\n");
            }
        
        printf("            </table>\n");
    $min_week_2++;   
    printf("                    </td>\n");    
                
    printf("                    </tr>\n");
    printf("            </table>\n");
    printf("        <br>\n");
        } 
}
 
    $results->free();
    $resultsb->free();
    $resultsb_week_2->free();
    $resultsc->free();
    $resultsd->free();
    $resultsd_week_2->free();
    $results_q->free();
    $results_qa->free();
    $results_qb->free();
    mysqli_close($mysqli);
    
printf("    </body>\n");
printf("</html>");


?>