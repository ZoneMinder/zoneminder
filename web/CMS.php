<?php
if (shell_exec('which gawk')=="")
{
?>
<html>
  <head>
    <meta charset="utf-8">
    <title>zmCMS</title>
  </head>
  <body>
    <h2> This utility requires 'gawk' to be installed on the server. </h2>
  </body>
</html>
<?php	
	die();
}

require_once( 'includes/config.php' );

$qq = dbQuery("select * from Monitors");
$Monitor = $qq->fetchAll(PDO::FETCH_ASSOC);

$ShowForm=false;
$MonitoringDate = DateTime::createFromFormat('m/d/Y', $_POST["MonitoringDate"]);

if(!($MonitoringDate))
{
	$ShowForm=true;
}
else
{
	$MonitoringDate = $MonitoringDate ->format("Y-m-d");

	$MList ="";
	$Comma ="";

	foreach($Monitor as $M)
	{
		if(isset($_POST["M".$M["Id"]]))
		{
			$MList .= $Comma.$M["Id"];
			$Comma =", ";
		}
	}

	if ($MList=="")
	{
		$ShowForm=true;
		$ErrMsg="Please select atleast one Monitor.";
	}
	else
	{
		$qq = dbQuery("select count(*) as EventCount, min(StartTime) as T1, max(StartTime) as T2 from Events where MonitorId in (".$MList.") and DATE(StartTime) ='".$MonitoringDate."'");

		$Result = $qq->fetch(PDO::FETCH_ASSOC);

		if (!($Result) || ($Result["EventCount"]==0))
		{
			$ShowForm=true;
			$ErrMsg="No data available for selected criteria.";
		}
	}
}

if ($ShowForm)
{
?>
<html>
	<head>
		<meta charset="utf-8">
		<title>zmCMS</title>
		<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
		<script src="//code.jquery.com/jquery-1.10.2.js"></script>
		<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
		<link rel="stylesheet" href="/resources/demos/style.css">
		<script>
			$(function() {
				$( "#datepicker" ).datepicker();
			});
		</script>
	</head>
	<body>
		<form method="POST" action="CMS.php">

<?php
if (isset($ErrMsg))
{
	echo "<span style='color:red;'>".$ErrMsg."<BR><BR></span>";
}

foreach($Monitor as $M)
{
?>
			<input type="checkbox" name="M<?php echo $M["Id"];?>" value="<?php echo $M["Id"];?>" <?php if(isset($_POST["M".$M["Id"]])) echo "checked";?>> <?php echo $M["Name"];?> <br>
<?php
}

?>
			<BR>
			<p>Date: <input type="text" id="datepicker" name="MonitoringDate" value="<?php echo isset($_POST["MonitoringDate"])?$_POST["MonitoringDate"]:date("m/d/Y"); ?>"></p>
			<BR>
			<input type="submit" value="Proceed">
		</form>
	</body>
</html>

<?php
	die();
}


$FrameRate=2;	//Frames per second [1, 2, 4 or 8]


?>

<html>
<head>
  <meta charset="utf-8">
  <title>zmCMS</title>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
  <script src="//code.jquery.com/jquery-1.10.2.js"></script>
  <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
</head>
<body>

<script>
  $(function() {
    $( "#slider" ).slider(
    {
      max: <?php echo 24*60*60*$FrameRate-1;?>    });
    
    $( "#slider" ).slider({                        
        change: function( event, ui ) 
        {
            i = $( "#slider" ).slider("value");
            DoMove(0);
        }
    });
  });
  
</script>

<h2 id="Heading" style="text-align: center">zoneminder CMS - <?php echo $MonitoringDate;?></h2>
<?php


foreach($Monitor as $M) if(isset($_POST["M".$M["Id"]]))
{
	echo "<img id='Img_".$M["Id"]."' src='' width='480' height='360' onload='PendingFrames--;' />\n";
}
?>

<br />

<div style="">
  <input type="button" id="Prev300" value="-5 min" onclick="DoMove(<?php echo -300*$FrameRate;?>)">
  <input type="button" id="Prev060" value="-1 min" onclick="DoMove(<?php echo  -60*$FrameRate;?>)">
  <input type="button" id="Prev010" value="-10 sec" onclick="DoMove(<?php echo -10*$FrameRate;?>)">
  <input type="button" id="Prev001" value="-1 sec" onclick="DoMove( <?php echo  -1*$FrameRate;?>)">

  <select id="ddPlaySpeed">
    <option value="<?php echo 2048/$FrameRate; ?>">0.5x (slow)</option>
    <option value="<?php echo  1024/$FrameRate; ?>" selected="selected">1x (norm)</option>
    <option value="<?php echo  512/$FrameRate; ?>">2x (fast)</option>
    <option value="<?php echo  256/$FrameRate; ?>">4x (fast2)</option>
  </select>

  <input type="button" id="btnPlay" value="Play" onclick="PlayStop();">

  <input type="button" id="Next001" value="+1 sec" onclick="DoMove(<?php echo   1*$FrameRate;?>)">
  <input type="button" id="Next010" value="+10 sec" onclick="DoMove(<?php echo 10*$FrameRate;?>)">
  <input type="button" id="Next060" value="+1 min" onclick="DoMove(<?php echo  60*$FrameRate;?>)">
  <input type="button" id="Next300" value="+5 min" onclick="DoMove(<?php echo 300*$FrameRate;?>)">
</div>

<br/>

<div style="position: relative; border: 0px solid #666666; height: 26px; margin: 0">
   <table border=0 padding=0 cellspacing=0 width=100% style="font-family: arial; font-size: 10px;">
      <tr>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>

	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>

	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>

	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
	<td width=2.0833%> </td>
      </tr>

      <tr height=10px>
	<td colspan="1"></td>
	<td colspan="2" align=center> 01:00</td>
	<td colspan="2" align=center> 02:00</td>
	<td colspan="2" align=center> 03:00</td>
	<td colspan="2" align=center> 04:00</td>
	<td colspan="2" align=center> 05:00</td>
	<td colspan="2" align=center> 06:00</td>
	<td colspan="2" align=center> 07:00</td>
	<td colspan="2" align=center> 08:00</td>
	<td colspan="2" align=center> 09:00</td>
	<td colspan="2" align=center> 10:00</td>
	<td colspan="2" align=center> 11:00</td>
	<td colspan="2" align=center> 12:00</td>
	<td colspan="2" align=center> 13:00</td>
	<td colspan="2" align=center> 14:00</td>
	<td colspan="2" align=center> 15:00</td>
	<td colspan="2" align=center> 16:00</td>
	<td colspan="2" align=center> 17:00</td>
	<td colspan="2" align=center> 18:00</td>
	<td colspan="2" align=center> 19:00</td>
	<td colspan="2" align=center> 20:00</td>
	<td colspan="2" align=center> 21:00</td>
	<td colspan="2" align=center> 22:00</td>
	<td colspan="2" align=center> 23:00</td>
	<td colspan="1"></td>
      </tr>

      <tr height=10px>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
	<td colspan="2" style="border-right: 1px solid #666666; border-left: 1px solid #666666;"> </td>
      </tr>
   </table>
</font>
</div>
 
<div id='slider'></div>

<script>



<?php

for ($minute=0; $minute<24*60; $minute++)
{
	$Color[$minute]=0;
}

$Interval=1/$FrameRate; //seconds

echo "var FrameRate=".$FrameRate."; //Frames per second\n";

foreach($Monitor as $M) if(isset($_POST["M".$M["Id"]]))
{
	$DatePath="events/".$M["Id"]."/".DateTime::createFromFormat('m/d/Y', $_POST["MonitoringDate"])->format("y/m/d")."/";

	$t=DateTime::createFromFormat('m/d/Y H:i:s', $_POST["MonitoringDate"]." 00:00:00")->getTimestamp();
	$i=0;
	echo "var monitor".$M["Id"]." = new Array();\n";

	$qq = dbQuery("select *, time(StartTime) as EventStartTime from Events where Date(StartTime)='".$MonitoringDate."' and   MonitorId=".$M["Id"]." order by StartTime");
	$Event = $qq->fetchAll(PDO::FETCH_ASSOC);

	foreach($Event as $E)
	{
		$SlashedEventTime=str_replace(":","/",$E["EventStartTime"])."/";

		unset($FT);
		eval(shell_exec('cd '.$DatePath.$SlashedEventTime.'; ls --full-time *.jpg | gawk \'{ ss=substr($9,1,5); sub(/0+/, "", ss); print "$FT[" ss "]=DateTime::createFromFormat(\"Y-m-d H:i:s\", \"" $6 " " substr($7,1,8) "\")->getTimestamp()+0" substr($7,9) ";" }\' '));

		for($f=1; $f<=$E["Frames"];$f++)
		{
			$Fname_Short=$SlashedEventTime.str_pad($f,5,"0",STR_PAD_LEFT);

			$Filename=$DatePath.$Fname_Short."-capture.jpg";

			if($FT[$f])
			{
				if($FT[$f]>=$t)
				{
					while ($FT[$f]-$t>=$Interval)
					{
						$i++;
						$t=$t+$Interval;
					}

					if($FT[$f]>=$t)
					{
						echo "monitor".$M["Id"]."[".$i."]='".$Fname_Short."';\n";
						
						$Color[floor($i/(60*$FrameRate))]=1;

						$i++;
						$t=$t+$Interval;
					}
				}
			}
		}

	}
}

$T0=DateTime::createFromFormat('Y-m-d H:i:s', $MonitoringDate." 00:00:00")->getTimestamp();
$qq = dbQuery("select distinct (unix_timestamp(f.TimeStamp)-".$T0.") div 60 as Minute from Frames as f inner join Events as e on e.Id=f.EventId where e.MonitorId in (1,2) and date(e.StartTime)='".$MonitoringDate."' and f.Score>0 order by Minute");
$Minute = $qq->fetchAll(PDO::FETCH_ASSOC);
foreach($Minute as $M)
{
	$Color[$M["Minute"]]=2;
}
?>
</script>

<div style="position: relative; border: 1px solid #666666; height: 30px; margin: 0">
<table border="0" padding="0" cellspacing="0" width="100%">
	<tr height="30px">
<?php

	$RGB[0]="FFFFFF";
	$RGB[1]="7777FF";
	$RGB[2]="FF0000";
	$CurrentColor=$Color[0];
	$Color[24*60]=3;
	$t=0;
	for($minute=1; $minute<=24*60; $minute++)
	{
		if ($Color[$minute]!=$CurrentColor)
		{
			$percent = ($minute-$t)*100/24/60;
			echo "<td bgcolor='#".$RGB[$CurrentColor]."' width='".$percent."%'> </td>\n";


			$CurrentColor=$Color[$minute];
			$t=$minute;
		}

	}
?>
	</tr>
</table>
</div>

<script language="javascript">

var i = 0;
var MoveEnabled=1;
var PendingFrames=0;

<?php
foreach($Monitor as $M) if(isset($_POST["M".$M["Id"]]))
{
	echo "var Img_".$M["Id"]." = document.getElementById('Img_".$M["Id"]."');\n";
	echo "var Src_".$M["Id"]." = 'NoData.jpg';\n";
}
?>
    
function DoMove(n){
  if(MoveEnabled==1){
    i=i+n;
    if (i<0) {i=0;}
    if (i> <?php echo 24*60*60*$FrameRate-1;?>) {i=<?php echo 24*60*60*$FrameRate-1;?>;}

<?php
$D = DateTime::createFromFormat('m/d/Y', $_POST["MonitoringDate"])->format("y/m/d");
foreach($Monitor as $M) if(isset($_POST["M".$M["Id"]]))
{
	echo "    if (monitor".$M["Id"]."[i]==null)\n";
	echo "    {\n";
	echo "        Src_".$M["Id"]." ='NoData.jpg';\n";
	echo "    }\n";
	echo "    else\n";
	echo "    {\n";
	echo "        Src_".$M["Id"]." ='events/".$M["Id"]."/".$D."/'+monitor".$M["Id"]."[i]+'-capture.jpg';\n";
	echo "    }\n";
}
?>
	document.getElementById("Heading").innerHTML="zoneminder CMS  <?php echo $MonitoringDate;?>  "+Math.floor(i/<?php echo 60*60*$FrameRate;?>)+":"+Math.floor(i/<?php echo 60*$FrameRate;?>)%60+":"+Math.floor(i/<?php echo $FrameRate;?>)%60;
    MoveEnabled=0;
    $('#slider').slider( 'value', i);
    MoveEnabled=1;
  }
}
    
function playNextFrame(){
    DoMove(1);
    if (i< <?php echo 24*60*60*$FrameRate-1;?>){
        Play();
    }
}

function Play(){
	var dd = document.getElementById("ddPlaySpeed");
	
    PlayTimer = setTimeout(playNextFrame, dd.options[dd.selectedIndex].value);
}

function Stop(){
    clearTimeout(PlayTimer);
}

function PlayStop(){
	if (document.getElementById('btnPlay').value=='Play'){
		Play();
		document.getElementById('btnPlay').value='Stop';
	} else {
		Stop();
		document.getElementById('btnPlay').value='Play';
	}
}

function LoadFrames(){
	if (PendingFrames==0){
<?php
foreach($Monitor as $M) if(isset($_POST["M".$M["Id"]]))
{
	echo "		if (Img_".$M["Id"].".src!=Src_".$M["Id"]."){\n";
	echo "			PendingFrames++;\n";
	echo "			Img_".$M["Id"].".src=Src_".$M["Id"].";\n";
	echo "		}\n";
}
?>
	}
	LoadFrameTimer = setTimeout(LoadFrames, 50);
}

//DoMove(0);
function DoMoveZero(){
    DoMove(0);
	LoadFrames();
}

var initTimer = setTimeout(DoMoveZero, 100);
</script>


	</body>
</html>
