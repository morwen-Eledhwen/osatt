<?php

/*
 * Attendance Recording
 * 
 * OSATT - Open Source Attendance
 * 
 * spons_doc_reports.php
 * Copyright (C) 2002, 2003, 2016 Ken Plumbly <frotusroom@gmail.com>
 *
 * spons_doc_reports.php is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * spons_doc_reports.php is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along
 * with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*------------------------------------------------------------------------------*/
/* spons_doc_reports.php generates a docx file categorizing attendance by       */
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

/*
 *  include the config file
 */

include 'nextdex.php';

/*
 * Include global subroutines
 */

include 'include.php';

$daysofweek = array( "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");

/*
 * Connect to Mysql
 */    

$mysqli = connect_me();

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
/* Since this is a report by sponsor; let's get the sponsor name and            */
/* display it in a small table at the top of the page right under the           */
/* attendence code definition table.                                            */
/*------------------------------------------------------------------------------*/        
        
$spons_id = $_GET['sponsor']; 
$first_date = $_GET['date'];
   
$spons_sql = "SELECT * FROM sponsor WHERE sponsor_index = '" . $spons_id . "'";

$spons_results = $mysqli->query($spons_sql);
//$spons_rows = $spons_results->fetch_assoc();
    



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


/*------------------------------------------------------------------------------*/
/* Let's print who the sponsor is                                               */
/*------------------------------------------------------------------------------*/

$section->addTextBreak();

$table_spons = $section->addTable('Fancy Table', $styleTable, $styleFirstRow);
$table_spons->addRow();
while($spons_rows = $spons_results->fetch_assoc()){
    $contents_spons_title = sprintf("Sponsor: ");
    $contents_spons_name = sprintf("%s", $spons_rows['sponsor']);
    $table_spons->addCell(1200)->addText($contents_spons_title, 'bolden', 'centered');
    $table_spons->addCell(4000)->addText($contents_spons_name, 'noBold', 'centered');
}

$spons_results->free();


/*------------------------------------------------------------------------------*/
/* Let's get to the actual meat of the report                                   */
/*------------------------------------------------------------------------------*/

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
        
    //$sql_a = "SELECT * FROM student, attend, enrolled WHERE student.sponsor = '" . $spons_id . "' AND attend.course_index = '" . $class_ids[$min] . "' AND enrolled.prog_index = '" . $class_ids[$min] . "' AND enrolled.stu_index = student.stu_index AND enrolled.status = '0' AND attend.epoch BETWEEN " . $epoch_1_start . " AND " . $epoch_1_end;
    $sql_a = "SELECT * FROM student, attend, enrolled WHERE student.sponsor = '" . $spons_id . "' AND attend.course_index = '" . $class_ids[$min] . "' AND enrolled.prog_index = '" . $class_ids[$min] . "' AND enrolled.stu_index = student.stu_index AND enrolled.status = '0' AND enrolled.status NOT IN (2, 3) AND attend.epoch BETWEEN " . $epoch_1_start . " AND " . $epoch_1_end;
    //echo "Query = " . $sql_a . "<br>\n";
    $results_a = $mysqli->query($sql_a);
    $rows_a = $results_a->fetch_assoc();    
    
    $sql_b = "SELECT * FROM classes WHERE prog_index = '" . $class_ids[$min] . "'";
    $results_b = $mysqli->query($sql_b);
    $rows_b = $results_b->fetch_assoc();
    
    $sql_c = "SELECT * FROM U_ops WHERE U_index = '" . $rows_b['inst_index'] . "'";
    $results_c = $mysqli->query($sql_c);
    $rows_c = $results_c->fetch_assoc();
    
    $instructor = $rows_c['U_F_name'] . " " . $rows_c['U_L_name'];
    $course_name = $rows_b['program'];
    $week_beginning_1 = date('D F d', $epoch_1_start);
    
    if($rows_a != NULL){
        mysqli_data_seek($results_a,0);                                         // Make sure the record is reset to the start of the row. Else records get chopped off

        /*----------------------------------------------------------------------*/
        /* Print outside table which contains two weeks side by side            */
        /*----------------------------------------------------------------------*/
        
        /*----------------------------------------------------------------------*/
        /* Meat and potatos - start adding the tables in                        */
        /*----------------------------------------------------------------------*/

        //$section->addPageBreak();

        /*----------------------------------------------------------------------*/
        /* outerTable has one row divided into two cells, each cell contains    */
        /* a table, inside of which, in the top row, is another table, the left */
        /* table contains the course name and instructor ID, the right does not */
        /* the second row of each inner table contains another table with 3     */
        /* cells and one row, this contains the starting day Always Sunday)     */
        /* and the date.                                                        */
        /*----------------------------------------------------------------------*/

        $section->addTextBreak();
                    
        $outerTable = $section->addTable();
        $outerCell_left = $outerTable->addRow()->addCell(7000);
        $left_inner_table = $outerCell_left->addTable('Fancy Table');

        $left_name_table = $left_inner_table->addRow(800)->addCell(6000, array('valign'=>'center', 'gridspan' => 8))->addTable();
        $left_name_table->addRow();
        $left_name_table->addCell(3000, array('valign'=>'center'))->addText($course_name, 'noBold', 'boldLeft');
        $left_name_table->addCell(1300, array('valign'=>'center'))->addText('Instructor:', 'bolden','boldRight');
        $left_name_table->addCell(2390, array('valign'=>'center'))->addText($instructor, 'noBold', 'boldLeft');

        $left_week_from_table = $left_inner_table->addRow(500)->addCell(3000, array('valign' => 'center', 'gridspan' => 8))->addTable('noBorder');
        $left_week_from_table->addRow();
        $left_week_from_table->addCell(1800, array('valign'=>'center'))->addText(' ', 'noBold', 'boldLeft');
        $left_week_from_table->addCell(2700, array('valign'=>'center'))->addText('Week Beginning:', 'bolden', 'boldRight');
        $left_week_from_table->addCell(2100, array('valign'=>'center'))->addText($week_beginning_1, 'noBold', 'boldLeft');
        $left_inner_table->addRow();
        $left_inner_table->addCell(2000)->addText('Student Name', 'bolden', 'boldLeft');

        /*----------------------------------------------------------------------*/
        /* Print Sun - Sat in 7 cells                                           */
        /*----------------------------------------------------------------------*/

        $x = 0;
            for($x = 0;$x < 7;$x++){
                    $left_inner_table->addCell(695, array('bgcolor'=>'cccccc'))->addText($daysofweek[$x], 'bolden', 'centered');
            }

        /*----------------------------------------------------------------------*/    
        /* Add a row so that the dates which follow will be on the next line    */
        /*----------------------------------------------------------------------*/
            
        $left_inner_table->addRow();
            
        /*----------------------------------------------------------------------*/
        /* Add blank cell - helps to make apperance between different           */
        /* file formats appear the same.                                        */
        /*----------------------------------------------------------------------*/
  
        $left_inner_table->addCell(2000)->addText(' ', 'noBold', 'centered');    
        
        /*----------------------------------------------------------------------*/
        /* Print out the dates of the days of the week underneath the three     */
        /* character abbreviations for the weekdays                             */
        /*----------------------------------------------------------------------*/
        
        $x = 0;
            while($x < 7){
                $left_inner_table->addCell(695, array('bgcolor'=>'f2f2f2'))->addText(date('d', strtotime(str_replace('/', '-', $week_one_dates[$x]))), 'bolden', 'centered');
                $x++;
            }

        /*----------------------------------------------------------------------*/
        /* The next query retrieves student names associated with this          */
        /* course and enrollemnt info.                                          */
        /* Then the first loop prints out the students in a loop until          */
        /* the students for this course are all printed. As each                */
        /* student is printed, a query is executed in a loop 7 times            */
        /* (inneficient, I know, I'll get round to fixing it)                   */
        /* checking if there is data for a particular day, if there is,         */
        /* the attendance record for that student for that day is               */
        /* printed in a table cell.                                             */
        /*----------------------------------------------------------------------*/
                        
                    $sql_d = "SELECT * FROM enrolled INNER JOIN student ON (enrolled.stu_index = student.stu_index AND enrolled.prog_index = '" . $class_ids[$min] . "' AND enrolled.status = 0 AND student.sponsor = '" . $spons_id . "') ORDER BY student.Stu_L_name, student.Stu_F_name";
                    $results_d = $mysqli->query($sql_d);
                    
                    while($rows_d = $results_d->fetch_assoc()){
                        $left_inner_table->addRow();
                        $student_name = sprintf("%s, %s %s", $rows_d['Stu_L_name'], $rows_d['Stu_F_name'], $rows_d['Stu_M_name']);
                            $left_inner_table->addCell(2000)->addText($student_name, 'noBold', 'boldLeft');
                            $x = 1;
                                for($x = 0;$x < 7;$x++){
                                    $dates = str_replace('/', '-', $week_one_dates[$x]);
                                    $epoch = strtotime($dates);
                                    
                                    $sql_e = "SELECT * FROM attend WHERE course_index = " . $class_ids[$min] . " AND epoch = " . $epoch . " AND stu_index = " . $rows_d['stu_index'];
                                    
                                    $results_e = $mysqli->query($sql_e);
                                    $rows_e = $results_e->fetch_assoc();
                                        if(mysqli_num_rows($results_e) > 0){
                                            $sql_f ="SELECT * FROM attend_code WHERE attend_index = '" . $rows_e['attend_code'] . "'";
                                            $results_f = $mysqli->query($sql_f);
                                            $rows_f = $results_f->fetch_assoc();
                                            $attend_code = sprintf("%s", $rows_f['attend_code']);
                                            $left_inner_table->addCell(695, array('bgcolor'=>'f2f2f2'))->addText($attend_code, 'bolden', 'centered');
                                            $results_e->free();
                                        } else {
                                            $left_inner_table->addCell(695, array('bgcolor'=>'cccccc'))->addText(' ', 'bolden', 'centered');
                                    }
                                }
                    }            
        /*----------------------------------------------------------------------*/
        /* Week two, same as week 1, with dates changed and no course           */
        /* or instructor name displayed                                         */
        /*----------------------------------------------------------------------*/    
        
        $lastUpdatedText = sprintf("Last Updated: %s", date("D M d Y",$rows_a['last_updated']));                    

        /*----------------------------------------------------------------------*/
        /* outerTable has one row divided into two cells, each cell contains    */
        /* a table, inside of which, in the top row, is another table, the left */
        /* table contains the course name and instructor ID, the right does not */
        /* the second row of each inner table contains another table with 3     */
        /* cells and one row, this contains the starting day Always Sunday)     */
        /* and the date.                                                        */
        /*----------------------------------------------------------------------*/        

        $outerCell_right = $outerTable->addCell(5000, 'noBold');
        $right_inner_table = $outerCell_right->addTable('Fancy Table');
                    
        $right_name_table = $right_inner_table->addRow(800)->addCell(6300, array('valign'=>'center', 'gridspan' => 7))->addText($lastUpdatedText);

        $right_week_from_table = $right_inner_table->addRow(500)->addCell(3000, array('valign' => 'center', 'gridspan' => 7))->addTable('noBorder');
        $right_week_from_table->addRow();
        $right_week_from_table->addCell(2700, array('valign'=>'center'))->addText('Week Beginning:', 'bolden', 'boldRight');
        $right_week_from_table->addCell(2000, array('valign'=>'center'))->addText(date('D F d', $epoch_2_start), 'noBold', 'boldLeft');
        $right_inner_table->addRow(); 
        
        /*----------------------------------------------------------------------*/
        /* Print Sun - Sat in 7 cells                                           */
        /*----------------------------------------------------------------------*/

        $x = 0;
            for($x = 0;$x < 7;$x++){
                $right_inner_table->addCell(695, array('bgcolor'=>'cccccc'))->addText($daysofweek[$x], 'bolden', 'centered');
            }
        $right_inner_table->addRow();        

        /*----------------------------------------------------------------------*/
        /* Print out the dates for week two under the three character           */
        /* abbreviations for the weekdays.                                      */
        /*----------------------------------------------------------------------*/
        
        $x = 0;
            while($x < 7){
                $right_inner_table->addCell(695, array('bgcolor'=>'f2f2f2'))->addText(date('d', strtotime(str_replace('/', '-', $week_two_dates[$x]))), 'bolden', 'centered');
                $x++;
            }

        //mysqli_data_seek($resultsa, 0);

        /*----------------------------------------------------------------------*/
        /* The next query retrieves student names associated with this          */
        /* course and enrollemnt info.                                          */
        /* Then the first loop prints out the students in a loop until          */
        /* the students for this course are all printed. As each                */
        /* student is printed, a query is executed in a loop 7 times            */
        /* (inneficient, I know, I'll get round to fixing it)                   */
        /* checking if there is data for a particular day, if there is,         */
        /* the attendance record for that student for that day is               */
        /* printed in a table cell.                                             */
        /*----------------------------------------------------------------------*/
                        
        $sql_g = "SELECT * FROM enrolled INNER JOIN student ON (enrolled.stu_index = student.stu_index AND enrolled.prog_index = '" . $class_ids[$min] . "' AND enrolled.status = 0 AND student.sponsor = '" . $spons_id . "') ORDER BY student.Stu_L_name, student.Stu_F_name";
        $results_g = $mysqli->query($sql_g);
                    
        while($rows_g = $results_g->fetch_assoc()){
            $right_inner_table->addRow();
            $x = 1;
                for($x = 0;$x < 7;$x++){
                $dates = str_replace('/', '-', $week_two_dates[$x]);
                $epoch = strtotime($dates);
                                    
                $sql_h = "SELECT * FROM attend WHERE course_index = " . $class_ids[$min] . " AND epoch = " . $epoch . " AND stu_index = " . $rows_g['stu_index'];
                                    
                $results_h = $mysqli->query($sql_h);
                $rows_h = $results_h->fetch_assoc();
                    if(mysqli_num_rows($results_h) > 0){
                        $sql_i ="SELECT * FROM attend_code WHERE attend_index = '" . $rows_h['attend_code'] . "'";
                        $results_i = $mysqli->query($sql_i);
                        $rows_i = $results_i->fetch_assoc();
                        $attend_code = sprintf("%s", $rows_i['attend_code']);
                        $right_inner_table->addCell(695, array('bgcolor'=>'f2f2f2'))->addText($attend_code, 'bolden', 'centered');
                        $results_h->free();
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

$file_name = 'pdt_attend_by_spons.docx';

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

?>