<?php

/*
 * Attendance Recording
 * 
 * calendar.php
 * Copyright (C) 2002, 2003, 2016 Ken Plumbly <frotusroom@gmail.com>
 *
 * calendar.php is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * calendar.php is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along
 * with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * This is based off of code from www.plus2net.com modified to
 * suit my needs
 * 
 *
 * 
 *  Get whether or not this is a from date or a to date
 */
$got_value = $_GET['form'];

?>

<html>
    <head>
        <meta charset="UTF-8">
        <title>Calendar</title>
        <link rel="stylesheet" type="text/css" href="style-sign.css">
<script langauge="javascript">
var new_var="<?php printf("%s",$got_value); ?>";

<?php

    if($got_value === "from"){
        printf("function post_value(dt,mm,yy){\n");
        printf("opener.document.course_dates.from.value = dt + \"/\" + mm + \"/\" + yy;\n");
        printf("/// cheange the above line for different date format\n");
        printf("self.close();\n");
        printf("}\n");
    } else {
        printf("function post_value(dt,mm,yy){\n");
        printf("opener.document.course_dates.to.value = dt + \"/\" + mm + \"/\" + yy;\n");
        printf("/// cheange the above line for different date format\n");
        printf("self.close();\n");
        printf("}\n");   
    }
?>
    
function reload(form){
var month_val=document.getElementById('month').value; // collect month value
var year_val=document.getElementById('year').value;      // collect year value
self.location='calendar.php?form=' + new_var + '&month=' + month_val + '&year=' + year_val ; // reload the page
}
</script>
    </head>
    <body>

<?php

if($_GET['month'] == ""){
        $current_month = date("n");
    }
    else {
        $current_month = $_GET['month'];
        if(!is_numeric($current_month)){
        $current_month = date('n', strtotime($current_month));
        }
        
    }

if($_GET['year'] == ""){
        $current_year = date("Y");
    }
    else {
        $current_year = $_GET['year'];
    }
    
/*
 * give me the number of days in the month
 * given current month
 */

$days_in_the_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);

/*
 * weekday of the first date of the month
 */

$first_day_of_month = date('w', mktime(0, 0, 0, $current_month, 1, $current_year));

$days_of_week = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");

$adj=str_repeat("<td bgcolor='#ffff00'>*&nbsp;</td>",$first_day_of_month);

printf("    <table class='calendar'>\n");
printf("        <tr>\n");
printf("            <th colspan=\"2\">");


/*
 * Kluge to get back by one month button working
 */

$prev_month = $current_month;
    $prev_month = $prev_month-1;
    if($prev_month == 0){
        $prev_month = 12;
    }


printf("<a href=\"");
printf(htmlspecialchars($_SERVER['PHP_SELF']));
printf("?month=");
printf("%s&year=%s&form=%s\">", $prev_month, $current_year, $got_value);
printf("<img src=\"./images/previous.png\" height=\"32\" width\"32\"></a></th>\n");

printf("            <th colspan=\"3\">\n");

printf("            <select name=month value='' onchange=\"reload(this.form)\" id=\"month\">\n");
printf("            <option value=''>Select Month</option>\n");
    for($month = 1; $month <= 12; $month++){

    $dateObject   = DateTime::createFromFormat('!m', $month);
    $month_name = $dateObject->format('F');
        if($current_month==$month){
            printf("            <option value=\"$month\" selected>$month_name</option>\n");
            }else{
        printf("            <option value=\"$month\">$month_name</option>\n");
        }
    }
printf("            </select>\n");

printf("            <select name=\"year\" value='' onchange=\"reload(this.form)\" id=\"year\">Select Year</option>\n");

$this_year = date("Y");
$from_years = $this_year-5;
$to_years = $this_year+5;

for($i = $from_years ;$i <= $to_years ;$i++){
if($current_year==$i){
        printf("              <option value=\"%s\" selected>%s</option>\n", $i, $i);
} else {
        printf("              <option value=\"%s\">%s</option>\n", $i, $i);
    }
}
printf("              </select>\n");

printf("            </th>\n");
printf("            <th valign=\"middle\" colspan=\"2\">");

/*
 * Kluge to get advance by one month button working
 */

$next_month = $current_month;
    $next_month = $next_month+1;
    if($next_month == 13){
        $next_month = 1;
    }

printf("<a href=\"");
printf(htmlspecialchars($_SERVER['PHP_SELF']));
printf("?month=");
printf("%s&year=%s&form=%s\">", $next_month, $current_year, $got_value);
printf("<img src=\"./images/next.png\" height=\"32\" width\"32\"></a></th>\n");
printf("        </tr>\n");
printf("        <tr>\n");


$x = 0;
    for($x == 0;$x <= 6;$x++){
        printf("            <th>%s</th>\n", $days_of_week[$x]);
    }
printf("        </tr>\n");

for($i=1;$i<=$days_in_the_month;$i++){
    $link_data = "'$i'".","."'$current_month'".","."'$current_year'";
    printf("        $adj<td><a href='#' onclick=\"post_value($link_data);\">$i</a></td>\n");
    $adj = '';
    $first_day_of_month++;
        if($first_day_of_month == 7){
            printf("            </tr>\n");
            printf("            <tr>\n");
        $first_day_of_month=0;
        }
}
printf("    </table>\n");
    
?>

</body>
</html>