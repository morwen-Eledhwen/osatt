<!DOCTYPE html>
<?php

/*
 * index.php
 * Copyright (C) 2016 Ken Plumbly <frotusroom@gmail.com>
 *
 * gtkCalWidget is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * gtkCalWidget is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along
 * with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/* if(!function_exists('hash_equals'))
{
    function hash_equals($str1, $str2)
    {
        if(strlen($str1) != strlen($str2))
        {
            return false;
        }
        else
        {
            $res = $str1 ^ $str2;
            $ret = 0;
            for($i = strlen($res) - 1; $i >= 0; $i--)
            {
                $ret |= ord($res[$i]);
            }
            return !$ret;
        }
    }
}
*/

function new_error($pub, $pvt = ''){
	global $debug;
	$msg = $pub;
	if ($debug && $pvt !== '')
		$msg .= ": $pvt";
/* The $pvt debugging messages may contain characters that would need to be
 * quoted if we were producing HTML output, like we would be in a real app,
 * but we're using text/plain here.  Also, $debug is meant to be disabled on
 * a "production install" to avoid leaking server setup details. */
	exit("An error occurred ($msg).\n");    
}

function SignIn() {
ob_start();
include 'nextdex.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if($mysqli->connect_errno) {
        echo "Failed to connect to database: (" . $mysqli->connect_errno . ")" . $mysqli->connect_error;
    }

session_start();
    
$username = $_POST['log_name'];
$password = $_POST['U_pass'];

//protect mysql from injections
//this is elementary, it could be done better.
$username = stripslashes($username);
$password = stripslashes($password);
$username = mysqli_real_escape_string($mysqli, $username);
$password = mysqli_real_escape_string($mysqli, $password);

/* this is a "prepared" or "parameterized" statement,
 * this is a part of the process to protect against injections.
 */

//$sql = <<<SQL
//        SELECT *
//        FROM U_ops
//        WHERE log_name = '$username'
//        AND U_pass = '$password'
//SQL;

//$sql = $mysqli->prepare('
//        SELECT
//        U_pass
//        FROM U_ops
//        WHERE
//        log_name = $username
//        LIMIT 1
//        ');

$sql = $mysqli->prepare("SELECT U_pass FROM U_ops WHERE log_name=? LIMIT 1");

$sql->bind_param("s", $username);
$sql->execute();

$sql->bind_result($U_pass);
$sql->fetch();



//printf("Query: %s\n<br>\n", $sql);

//if(!$result = $mysqli->query($sql)){
//    die('There was an error running the query [' . $mysqli->error . ']');
//}

//$count = mysql_num_rows($result);
//$count = $result->num_rows;
//echo "Rows: " . $count;

//if($count==1){
if(hash_equals($U_pass, crypt($password, $U_pass))){
    //session_register("$username");
    //session_register("$password");
    $_SESSION["username"]=$username;
    $_SESSION["password"]=$password;
    header("location:login_succeeded.php");
} else {
    printf("<html>\n");
    printf("    <head>\n");
    printf("        <title>Bad password</title>\n");
    printf("        <link rel=\"stylesheet\" type=\"text/css\" href=\"style-sign.css\">");
    printf("    </head>\n");
    printf("        <body id=\"body-color\">\n");
    printf("            <div id=\"Sign-In\">\n");
    printf("                    <br>\n");
    printf("                    <b>Bad username or password</b>\n<br>");
    printf("                    <br>\n");
    printf("            </div>\n");
    printf("        </body>\n");
    printf("</html>\n");
    header("refresh: 5;index.php");
}
ob_end_flush();
exit;
}

if(isset($_POST['submit']))
               {
               SignIn();
               }
?>

<html>
    <head>
        <meta charset="UTF-8">
        <title>Attendance Records</title>
        <link rel="stylesheet" type="text/css" href="style-sign.css">
    </head>
    <body id="body-color">
        <div id="Sign-In"> 
            <fieldset style="width:30%">
                <legend>OSATT Login</legend> 
                <form method="POST" action="<?PHP echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"> User 
                    <br>
                    <input type="text" name="log_name" size="40">
                    <br> 
                    Password 
                    <br>
                    <input type="password" name="U_pass" size="40">
                    <br> 
                    <br>
                    <input id="button" type="submit" name="submit" value="Log-In">
                </form>
            </fieldset>
        </div>
    </body>
</html>
