<?php

/*
 * Attendance Recording
 * 
 * doc_reports.php
 * Copyright (C) 2002, 2003, 2016 Ken Plumbly <frotusroom@gmail.com>
 *
 * doc_reports.php is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * doc_reports.php is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along
 * with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * This file generates reports in Microsoft Word .docx format
 * 
 * 
 * 
 */

session_start();
if(!isset($_SESSION['username'])){

header("location:index.php");
}

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
    
/*
 * Deal with making sure the $_SESSION['query_fodder'] array is
 * in the correct format
 */
    
/*    $counter_1 = count($_SESSION['query_fodder']);
    $query_fodder = array_chunk($_SESSION['query_fodder'], 4);

    $query_fodder = array_values($query_fodder[0]);
    //unset($query_fodder[3]);
    $query_fodder = array_values($query_fodder);
    
    $date_one = sprintf("%s", $query_fodder[0][0]);
*/
    
    
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
    
    
    
/*------------------------------------------------------------------------------*/
/* OK - this project is using a GPL php library called PHPWord in order to      */
/* Generate docx files - this library can also generate ODT, html (though       */
/* I have coded the html by hand - it's so much easier than using phpword)      */
/* and rtf files.                                                               */
/*------------------------------------------------------------------------------*/
/* Initialise PHPWord instance.                                                 */
/*------------------------------------------------------------------------------*/
    
require_once 'phpword/bootstrap.php';
$PHPWord = new \PhpOffice\PhpWord\PhpWord();

/*
 * Set up some styles for the page
 */



/*------------------------------------------------------------------------------*/
/* Set page format: letter, landscape orientation.                              */
/*------------------------------------------------------------------------------*/

$section = $PHPWord->addSection(array('paperSize' => 'Letter',
                                      'orientation' => 'landscape',
                                      'gutter' => 200,
                                      'headerHeight' => 1400,
                                      'marginLeft' => 800,
                                      'marginRight' => 1000,
                                      'marginTop' => 1440,
                                      'marginBottom' => 1000));
$header = array('size' => 16, 'bold' => true);

$styleTable = array('borderSize' => 6, 'borderColor' => '000000', 'cellMarginTop' => 80, 'cellMarginLeft' => 10);
$styleTable2 = array('borderSize' => 0, 'borderColor' => 'ffffff', 'cellMarginTop' => 80);
$styleFirstRow = array('borderBottomSize' => 6, 'borderTopColor' => '000000', 'borderBottomColor' => '000000', 'bgColor' => 'ffffff');
$styleCell = array('valign' => 'center');
$styleCellBTLR = array('valign' => 'center', 'textDirection' => \PhpOffice\PhpWord\Style\Cell::TEXT_DIR_BTLR);
$fontStyle = array('bold' => true, 'align' => 'center');

$table_colspan_8 = array('gridPspan' => 8, 'valign' => 'center');
$table_colspan_6 = array('gridPspan' => 6, 'valign' => 'center');
$table_colspan_2 = array('gridPspan' => 2, 'valign' => 'center');

$PHPWord->addFontStyle('bolden', array('bold' => true));
$PHPWord->addFontStyle('noBold', array('bold' => false));
$PHPWord->addParagraphStyle('boldRight', array('align'=>'right', 'spaceAfter' => 0));
$PHPWord->addParagraphStyle('boldLeft', array('align'=>'left', 'spaceAfter' => 0));
$PHPWord->addParagraphStyle('centered', array('align'=>'center', 'spaceAfter' => 0));

$cellHCentered = array('align' => 'center');
$cellVCentered = array('valign' => 'center'); 


//$PHPWord->addTableStyle('Fancy Table', $styleTable, $styleFirstRow);
$PHPWord->addTableStyle('Fancy Table', $styleTable);
$PHPWord->addTableStyle('NoBorder', $styleTable2);

/*------------------------------------------------------------------------------*/
/* Define sql queries which have no need of external variables                  */
/* Define the variables holding the qury and connection                         */
/*------------------------------------------------------------------------------*/

$sql = "SELECT * FROM attend_code";
    $results = $mysqli->query($sql);


/*------------------------------------------------------------------------------*/
/* Build the table which holds the Attendance legends                           */
/*------------------------------------------------------------------------------*/

$table = $section->addTable('Fancy Table', $styleTable, $styleFirstRow);
$table->addRow();
while($rows = $results->fetch_assoc()){
    $contents = sprintf("%s = %s", $rows['attend_code'], $rows['attend_desc']);
    $table->addCell(2000)->addText($contents, 'noBold', 'centered');
}    
    

/*
 * Set some default variables and their values
 */    
    
$min = 0;    
$min_week_2 = 0;
//$max = $_GET['row_count'];
$max = count($class_ids);

for($min = 0;$min < $max;$min++){
/*------------------------------------------------------------------------------*/
/* Date and time variables, once time allows; this MUST be cleaned up it is     */
/* unbearably ugly, but it was quick and dirty and works for now.               */
/*------------------------------------------------------------------------------*/  
    
        $new_id = sprintf("query_fodder_%s", $class_ids[$min]);
        $query_fodder = $_SESSION[$new_id];
    
    
            $rough_date = beg_end_of_week(0, $query_fodder[0]);
            $epoch_1 = strtotime($query_fodder[0]);
            $epoch_2 = strtotime($rough_date[1]);
        
            $sqla = "SELECT * FROM classes, attend WHERE attend.course_index = classes.prog_index AND classes.prog_index = " . $query_fodder[3] . " AND attend.epoch BETWEEN " . $epoch_1 . " AND " . $epoch_2 . "";
            $resultsa = $mysqli->query($sqla);
            $rowsa = $resultsa->fetch_assoc();
            
        /*----------------------------------------------------------------------*/
        /* Ok, assign some database out put to variable names - this is         */
        /* Different from the html_reports.php file, where we could use printf  */
        /* here we are using library routines which are a bit different.        */
        /*----------------------------------------------------------------------*/
            
            $course_name = $rowsa['program'];
            
            
            // Week one date stuff    
            
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

            // This is a kludge to keep the program from crashing, for some funky reason,
            // if the the date chosen is Nov 11/2016, the dates are flipped, with the
            // ending date being the previous Saturday, which causes the script
            // to implode...
            
            if($parse_1 === "12-11-2016"){
                $parsed_1 = "2016-11-19";
            } else {
                $parsed_1 = date('Y-m-d', strtotime($parse_1));
            }
            
            $week_two_dates = date_range($parsed_2, $parsed_1);
            
            if(mysqli_num_rows($results) === 0){
                $min++;
            } else {
                
            if(is_null(mysqli_num_rows($resultsa))){
            $sqla = "SELECT * FROM classes WHERE prog_index = '" . $query_fodder[3] . "'";
            $resultsa = $mysqli->query($sqla);
            $rowsa = $resultsa->fetch_assoc();            
            $course_name = $rowsa['program'];
            if($rowsa['last_updated'] == 0){
                $last_updated = "N/C";
            } else {
                $last_updated = date('D, M j, Y - g:i a', $rowsa['last_updated']);
                
                }
            }
                
                /*--------------------------------------------------------------*/
                /* Get the associated instructor name                           */
                /*--------------------------------------------------------------*/
                
                    $sqlc = "SELECT * FROM U_ops WHERE U_index = '" . $rowsa['inst_index'] . "'";
                    $resultsc = $mysqli->query($sqlc);
                    $rowsc = $resultsc->fetch_assoc();
                    
                    $instructor_name = sprintf("%s %s", $rowsc['U_F_name'], $rowsc['U_L_name']);
                    
                    $week_beginning_1 = date('D F d', $epoch_1);
                
                /*--------------------------------------------------------------*/
                /* Meat and potatos - start adding the tables in                */
                /*--------------------------------------------------------------*/

                    //$section->addPageBreak();

                /*------------------------------------------------------------------------------*/
                /* outerTable has one row divided into two cells, each cell contains            */
                /* a table, inside of which, in the top row, is another table, the left         */
                /* table contains the course name and instructor ID, the right does not         */
                /* the second row of each inner table contains another table with 3 cells and   */
                /* one row, this contains the starting day Always Sunday) and the date.         */
                /*------------------------------------------------------------------------------*/

                    $section->addTextBreak();
                    
                    $outerTable = $section->addTable();
                    $outerCell_left = $outerTable->addRow()->addCell(7000);
                    $left_inner_table = $outerCell_left->addTable('Fancy Table');

                    $left_name_table = $left_inner_table->addRow(800)->addCell(6000, array('valign'=>'center', 'gridspan' => 8))->addTable();
                    $left_name_table->addRow();
                    $left_name_table->addCell(3000, array('valign'=>'center'))->addText($course_name, 'noBold', 'boldLeft');
                    $left_name_table->addCell(1300, array('valign'=>'center'))->addText('instructor:', 'bolden','boldRight');
                    $left_name_table->addCell(2390, array('valign'=>'center'))->addText($instructor_name, 'noBold', 'boldLeft');

                    $left_week_from_table = $left_inner_table->addRow(500)->addCell(3000, array('valign' => 'center', 'gridspan' => 8))->addTable('noBorder');
                    $left_week_from_table->addRow();
                    $left_week_from_table->addCell(1800, array('valign'=>'center'))->addText(' ', 'noBold', 'boldLeft');
                    $left_week_from_table->addCell(2700, array('valign'=>'center'))->addText('Week Beginning:', 'bolden', 'boldRight');
                    $left_week_from_table->addCell(2100, array('valign'=>'center'))->addText($week_beginning_1, 'noBold', 'boldLeft');
                    $left_inner_table->addRow();
                    $left_inner_table->addCell(2000)->addText('Student Name', 'bolden', 'boldLeft');

                /*------------------------------------------------------------------------------*/
                /* Print Sun - Sat in 7 cells                                                   */
                /*------------------------------------------------------------------------------*/

                    $days_counter = 0;
                    for($days_counter = 0;$days_counter < 7;$days_counter++){
                        $left_inner_table->addCell(695, array('bgcolor'=>'cccccc'))->addText($daysofweek[$days_counter], 'bolden', 'centered');
                    }
                    $left_inner_table->addRow();
                /*------------------------------------------------------------------------------*/
                /* Add blank cell - helps to make apperance between different file formats      */
                /* appear the same.                                                             */
                /*------------------------------------------------------------------------------*/
                    $left_inner_table->addCell(2000)->addText(' ', 'noBold', 'centered');
                
                /*--------------------------------------------------------------*/
                /* Why it can never be straight-forward I don't know, but, the  */
                /* following bit of code takes a bunch of dates given in an     */
                /* array, one - by -one reformats them so that strtotime can    */
                /* turn them into an epoch (unix time stamp) which date() can   */
                /* process and spit out as the correct day. This is the days    */
                /* across the top of the weekly report.                         */
                /*--------------------------------------------------------------*/
                
                    
                    $dow_count = 0;
                        while($dow_count < 7){
                
                            $printable_date = $week_one_dates[$dow_count];
                            $printable_date = str_replace('/', '-', $printable_date); 
                            $epoch = strtotime($printable_date);
                            $printable_date = date('d', strtotime($printable_date));
                            $left_inner_table->addCell(695, array('bgcolor'=>'f2f2f2'))->addText($printable_date, 'bolden', 'centered');
                            $dow_count++;
                        }

                        mysqli_data_seek($resultsa, 0);
                        
                /*--------------------------------------------------------------*/
                /* The next query retrieves student names associated with this  */
                /* course and enrollemnt info.                                  */
                /* Then the first loop prints out the students in a loop until  */
                /* the students for this course are all printed. As each        */
                /* student is printed, a query is executed in a loop 7 times    */
                /* (inneficient, I know, I'll get round to fixing it)           */
                /* checking if there is data for a particular day, if there is, */
                /* the attendance record for that student for that day is       */
                /* printed in a table cell.                                     */
                /*--------------------------------------------------------------*/
                        
                    $sqld = "SELECT * FROM enrolled INNER JOIN student ON (enrolled.stu_index = student.stu_index AND enrolled.prog_index = '" . $query_fodder[3] . "' AND enrolled.status = 0) OR (enrolled.stu_index = student.stu_index AND enrolled.prog_index = '" . $query_fodder[3] . "' AND enrolled.status = 2) ORDER BY student.Stu_L_name, student.Stu_F_name";
                    $resultsd = $mysqli->query($sqld);
                    
                    while($rowsd = $resultsd->fetch_assoc()){
                        $left_inner_table->addRow();
                        $student_name = sprintf("%s, %s %s", $rowsd['Stu_L_name'], $rowsd['Stu_F_name'], $rowsd['Stu_M_name']);
                            $left_inner_table->addCell(2000)->addText($student_name, 'noBold', 'boldLeft');
                            $days_data = 1;
                                for($days_data = 0;$days_data < 7;$days_data++){
                                    $dates = str_replace('/', '-', $week_one_dates[$days_data]);
                                    $epoch = strtotime($dates);
                                    
                                    $sqlb = "SELECT * FROM attend WHERE course_index = " . $query_fodder[3] . " AND epoch = " . $epoch . " AND stu_index = " . $rowsd['stu_index'];
                                    
                                    $resultsb = $mysqli->query($sqlb);
                                    $rowsb = $resultsb->fetch_assoc();
                                        if(mysqli_num_rows($resultsb) > 0){
                                            $sql ="SELECT * FROM attend_code WHERE attend_index = '" . $rowsb['attend_code'] . "'";
                                            $result = $mysqli->query($sql);
                                            $row = $result->fetch_assoc();
                                            $attend_code = sprintf("%s", $row['attend_code']);
                                            $left_inner_table->addCell(695, array('bgcolor'=>'f2f2f2'))->addText($attend_code, 'bolden', 'centered');
                                            $resultsb->free();
                                        } else {
                                            $left_inner_table->addCell(695, array('bgcolor'=>'cccccc'))->addText(' ', 'bolden', 'centered');
                                    }
                                }
                    }

                    //$outerTable->addCell(6000)->addText('Hello');

/*------------------------------------------------------------------------------*/
/*------------------------------------------------------------------------------*/
                /*--------------------------------------------------------------*/
                /* Week two, same as week 1, with dates changed and no course   */
                /* or instructor name displayed                                 */
                /*--------------------------------------------------------------*/
                    
                $week_beginning_2 = date('D F d', $epoch_3);
                $lastUpdatedText = sprintf("Last Updated: %s", date("D M d Y",$rowsa[last_updated]));
                
                /*------------------------------------------------------------------------------*/
                /* outerTable has one row divided into two cells, each cell contains            */
                /* a table, inside of which, in the top row, is another table, the left         */
                /* table contains the course name and instructor ID, the right does not         */
                /* the second row of each inner table contains another table with 3 cells and   */
                /* one row, this contains the starting day Always Sunday) and the date.         */
                /*------------------------------------------------------------------------------*/

                    $outerCell_right = $outerTable->addCell(5000, 'noBold');
                    $right_inner_table = $outerCell_right->addTable('Fancy Table');
                    
                    $right_name_table = $right_inner_table->addRow(800)->addCell(6300, array('valign'=>'center', 'gridspan' => 7))->addText($lastUpdatedText);

                    $right_week_from_table = $right_inner_table->addRow(500)->addCell(3000, array('valign' => 'center', 'gridspan' => 7))->addTable('noBorder');
                    $right_week_from_table->addRow();
                    $right_week_from_table->addCell(2700, array('valign'=>'center'))->addText('Week Beginning:', 'bolden', 'boldRight');
                    $right_week_from_table->addCell(2000, array('valign'=>'center'))->addText($week_beginning_2, 'noBold', 'boldLeft');
                    $right_inner_table->addRow();

                /*------------------------------------------------------------------------------*/
                /* Print Sun - Sat in 7 cells                                                   */
                /*------------------------------------------------------------------------------*/

                    $days_counter = 0;
                    for($days_counter = 0;$days_counter < 7;$days_counter++){
                        $right_inner_table->addCell(695, array('bgcolor'=>'cccccc'))->addText($daysofweek[$days_counter], 'bolden', 'centered');
                    }
                    $right_inner_table->addRow();
                
                /*--------------------------------------------------------------*/
                /* Why it can never be straight-forward I don't know, but, the  */
                /* following bit of code takes a bunch of dates given in an     */
                /* array, one - by -one reformats them so that strtotime can    */
                /* turn them into an epoch (uniox time stamp) which date() can  */
                /* process and spit out as the correct day. This is thedays     */
                /* across the top of the weekly report.                         */
                /*--------------------------------------------------------------*/
                
                    
                    $dow_count = 0;
                        while($dow_count < 7){
                
                            $printable_date = $week_two_dates[$dow_count];
                            $printable_date = str_replace('/', '-', $printable_date); 
                            $epoch = strtotime($printable_date);
                            $printable_date = date('d', strtotime($printable_date));
                            $right_inner_table->addCell(695, array('bgcolor'=>'f2f2f2'))->addText($printable_date, 'bolden', 'centered');
                            $dow_count++;
                        }

                        mysqli_data_seek($resultsa, 0);
                        
                /*--------------------------------------------------------------*/
                /* The next query retrieves student names associated with this  */
                /* course and enrollemnt info.                                  */
                /* Then the first loop prints out the students in a loop until  */
                /* the students for this course are all printed. As each        */
                /* student is printed, a query is executed in a loop 7 times    */
                /* (inneficient, I know, I'll get round to fixing it)           */
                /* checking if there is data for a particular day, if there is, */
                /* the attendance record for that student for that day is       */
                /* printed in a table cell.                                     */
                /*--------------------------------------------------------------*/
                        
                    $sqld = "SELECT * FROM enrolled INNER JOIN student ON (enrolled.stu_index = student.stu_index AND enrolled.prog_index = '" . $query_fodder[3] . "' AND enrolled.status = 0) OR (enrolled.stu_index = student.stu_index AND enrolled.prog_index = '" . $query_fodder[3] . "' AND enrolled.status = 2) ORDER BY student.Stu_L_name, student.Stu_F_name";
                    $resultsd = $mysqli->query($sqld);
                    
                    while($rowsd = $resultsd->fetch_assoc()){
                        $right_inner_table->addRow();
                            $days_data = 1;
                                for($days_data = 0;$days_data < 7;$days_data++){
                                    $dates = str_replace('/', '-', $week_two_dates[$days_data]);
                                    $epoch = strtotime($dates);
                                    
                                    $sqlb = "SELECT * FROM attend WHERE course_index = " . $query_fodder[3] . " AND epoch = " . $epoch . " AND stu_index = " . $rowsd['stu_index'];
                                    
                                    $resultsb = $mysqli->query($sqlb);
                                    $rowsb = $resultsb->fetch_assoc();
                                        if(mysqli_num_rows($resultsb) > 0){
                                            $sql ="SELECT * FROM attend_code WHERE attend_index = '" . $rowsb['attend_code'] . "'";
                                            $result = $mysqli->query($sql);
                                            $row = $result->fetch_assoc();
                                            $attend_code = sprintf("%s", $row['attend_code']);
                                            $right_inner_table->addCell(695, array('bgcolor'=>'f2f2f2'))->addText($attend_code, 'bolden', 'centered');
                                            $resultsb->free();
                                        } else {
                                            $right_inner_table->addCell(695, array('bgcolor'=>'cccccc'))->addText(' ', 'bolden', 'centered');
                                    }
                                }
                    } 
                  
            }
}

/*------------------------------------------------------------------------------------------*/
/* Stream output to requesting client (web browser - opens save dialog or asks to open)     */
/*------------------------------------------------------------------------------------------*/

$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($PHPWord, 'Word2007');

$file_name = 'pdt_attend.docx';

$objWriter->save($file_name);

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename='.$file_name);
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($file_name));
flush();
readfile($file_name);
unlink($file_name); // deletes the temporary file
 

/*------------------------------------------------------------------------------*/
/* Make sure to free the memory used by the queries, then cleanly close         */
/* the connection to mysql, if we don't do this, the system will eventually     */
/* bog down, and it takes a while for the system to reclaim the memory and drop */
/* the open connections on its own.                                             */
/*------------------------------------------------------------------------------*/

$results->free();
$resultsa->free();
$resultsc->free();
$resultsd->free();
$results_q->free();
$results_qa->free();
$results_qb->free();
mysqli_close($mysqli);
exit;
?>