<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title>Install AE Notes Mac OS app</title>
</head>
<body>

<?php
if($_POST && $_POST['login_name']=='nh8to9' && $_POST['login_password']=='8goto9')
{
?>
<style type="text/css">
body {
	background: url(bkg.png) repeat #c5ccd4;
	font-family: Helvetica, arial, sans-serif;
}
.congrats {
	font-size: 16pt;
	padding: 6px;
	text-align: center;
}
.step {
	background: white;
	border: 1px #ccc solid;
	border-radius: 14px;
	padding: 4px 10px;
	margin: 10px 0;
}
.instructions {
	font-size: 10pt;
}
.arrow {
	font-size: 15pt;
}
table {
	width: 100%;
}
</style>
<div class="congrats">Click on the link below to download the app.</div>
<div class="step">
  <table>
    <tr>
      <td class="instructions">Install the<br />
    AE Notes Mac OS App</td>
      <td width="24" class="arrow">&rarr;</td>
      <td width="57" class="imagelink"><a href="https://aenote.aeedison.com/build/AENotemacOS.zip"> AE Notes </a></td>    </tr>
  </table>
</div>

<?php 
} else { 
?>
<style type="text/css">
html {
	height:100%;
}
body, div, span, h1, h2, h3, h4, h5, h6, p, a, abbr, font, img, ins, kbd, table, caption {
	margin: 0;
	padding:0;
	border: 0;
	outline: 0;
	font-weight: inherit;
	font-style: inherit;
	font-family: inherit;
	vertical-align: baseline;

}
a{ padding:0!important; margin:0!important;}

.under-cons-table {
	width: 94%;
	max-width:620px;
	margin: 0 auto;
	padding: 0;
	margin-top:120px;
	padding:0 5px 5px;
	font-weight:normal;
	border:solid 1px #B3B3B3;

}
.under-construction {

    color: #f49b00;
    font-family: arial;
    font-size: 66px;
    letter-spacing: 2px;
    line-height: 60px;
    margin: 0;
    text-align: center;
    text-shadow: 1px 1px 0 #CCCCCC;
    text-transform: uppercase;
}

.logo{ text-align:center}


.powered {
	color:#333333;
	font-size:12px;
	font-weight:bold;
	font-family:Arial, Helvetica, sans-serif;
	position:relative;
	line-height:11px;
	text-align:right;
	padding-top:12px;
	padding-right:22px;
	overflow:auto;
	margin-top:5px;

}
.powered a {
	height:20px;
	width:46px;
	display:inline-block;
	float:right;
	padding-bottom:6px;

}
.powered span {
	position:relative;
	float:right;

}
.under-cons-table input[type="text"], .under-cons-table input[type="password"] {
    border: 1px solid #b3b3b3;
    color:#8D8D8D;
    font-size: 12px;
    line-height: 20px;
    margin: 2px 3px 2px 0;
    padding: 5px !important;
    width: 260px;
}


.under-cons-table input[type="submit"] {
    background:  #f49b00;
    border: medium none;
    border-radius: 0;
    color: #FFFFFF;
    display: block;
    font-family:"Trebuchet MS", Arial, Helvetica, sans-serif;
    font-size: 14px;
    margin: 0 5px 5px 0;
    padding: 4px 35px;
	height: 32px;
    text-align: center;
    text-transform: uppercase;
    cursor:pointer;
}
.under-cons-table img{ max-width: 100%;}

</style>
<div class="under-cons-table">
  <div class=""> <h1 style="text-align:center; margin-top:20px; color:#888;"> AE Notes </h1></div>
  <br />
  <table cellpadding="10" cellspacing="0" border="0" width="100%" align="center">
    <tr>
      <td height="150" align="center" valign="top"><div class="login" >
          <form method="post" id="login_form" name="login_form">
            <table cellpadding="0" cellspacing="0" border="0" align="center" style="font-family: Arial,Helvetica,sans-serif; font-size:12px;">
              <tr>
                <td height="10"></td>
              </tr>
              <tr>
                <td style="font-size:10px; color: #FF0000;"><?php echo ($error) ?>&nbsp;</td>
              </tr>
              <tr>
                <td><input type="text" name="login_name" id="login_name" value="Username"  onBlur="myBlur(this)" onFocus="myFocus(this)"></td>
              </tr>
              <tr>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td><input type="password" name="login_password" id="login_password" value="Password"  onBlur="myBlur(this)" onFocus="myFocus(this)"></td>
              </tr>
              <tr>
                <td align="center" style="padding-top:10px;"><input type="submit" value="Login" name="Login" id="login" class="input_button" ></td>
              </tr>
            </table>
          </form>
        </div></td>
    </tr>
  </table>
</div>
<SCRIPT type=text/javascript>
function myFocus(element)
{
    if (element.value == element.defaultValue)
    {
        element.value = '';
    }
}
function myBlur(element)
{
    if (element.value == '')
    {
    element.value = element.defaultValue;
    }
}
</SCRIPT>

<?php
}
?>
</body>
</html>
