<?php
// Debug
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

function CheckLogin()
{
	return isset($_GET['secret'])  &&  $_GET['secret']== 'owned';
}
// MYSQL STORE MODE ***************************************************************************************
//  CREATE TABLE logs.requestLogs(DATA JSON);
function Connect()
{
	$link = new mysqli('127.0.0.1','root','','logs',3306);
	if($link->connect_error)
		die('');

	return $link;
}
function StoreData(&$array)
{
	$link=Connect();
	$link->query('INSERT INTO requestLogs(DATA)VALUES("'.$link->real_escape_string(json_encode($array)).'")');
	$link->close();
}
function ClearData()
{
	$link=Connect();
	$link->query('TRUNCATE TABLE requestLogs');
	$link->close();
}
function GetData()
{
	$link=Connect();
	$res=$link->query('SELECT DATA from requestLogs');
	$ret=array();

	while($row=$res->fetch_assoc())
		$ret[]=json_decode($row['DATA']);

	$res->free();
	$link->close();
	return $ret;
}
// FILE STORE MODE ****************************************************************************************
/*
function StoreData(&$array)
{
	$file='log.txt';

	$file=fopen($file,'a+');
	fseek($file,0,SEEK_END);
	if(ftell($file)>0) fwrite($file,",\n");
	fwrite($file,json_encode($array));
	fclose($file);
}
function ClearData()
{
	$file='log.txt';
	unlink($file);
}
function GetData()
{
	$file='log.txt';
	if(!file_exists($file)) return NULL;

	$ret=file_get_contents($file);
	$ret=json_decode('['.$ret.']');
	return $ret;
}
*/
// ********************************************************************************************************

if(!CheckLogin())
{
	// Log it!
	$array = array
	(
		'date'	=> date("Y-m-d H:i:s"),
		'time'	=> time(),
	);

	if (isset($_SERVER["REMOTE_ADDR"])) 	$array['ip']		=$_SERVER["REMOTE_ADDR"];
	if (isset($_SERVER["REQUEST_URI"])) 	$array['uri']		=$_SERVER["REQUEST_URI"];
	if (isset($_SERVER["HTTP_USER_AGENT"])) $array['agent']		=$_SERVER["HTTP_USER_AGENT"];
	if (isset($_SERVER["HTTP_REFERER"])) 	$array['referer']	=$_SERVER["HTTP_REFERER"];
	if (isset($_SERVER["HTTP_HOST"])) 	$array['domain']	=$_SERVER["HTTP_HOST"];
	if (isset($_SERVER["REQUEST_METHOD"])) 	$array['method']	=$_SERVER["REQUEST_METHOD"];
	if (isset($_SERVER["QUERY_STRING"]) &&
		$_SERVER["QUERY_STRING"]!='') 	$array['get']		=$_SERVER["QUERY_STRING"];

	$data=file_get_contents("php://input");
	if ($data!='') $array['post']=$data;
	StoreData($array);
	die();
}

// Clear
if (isset($_GET['clear']) && $_GET['clear']=='1')
{
	ClearData();
	header('Location: ?secret='.$_GET['secret']);
	die();
}

// Parse
$obj=GetData();
if($obj==NULL || $obj=='')
	{
	// Viewer
	$obj='[]';
	echo 'No data ...';
	die();
	}
else
	$obj=json_encode($obj);

$obj='{"data":'.$obj.'}';
$obj=json_decode($obj);

// Output
if (isset($_GET['out']) && ($_GET['out']=='json'|| $_GET['out']=='json_data'))
	{
	header('Content-type: application/json');

	if($_GET['out']=='json_data')
	{
		$max=count($obj->data);
		for($x=0;$x<$max;$x++)
		{
			$row=$obj->data[$x];
			$row=array
			(
				isset($row->date  )?$row->date  :'',
				isset($row->domain)?$row->domain:'',
				isset($row->ip    )?$row->ip    :'',
				isset($row->agent )?$row->agent :'',
				isset($row->method)?$row->method:'',
				isset($row->get   )?$row->get   :'',
				isset($row->post  )?$row->post  :'',
			);
			$obj->data[$x]=$row;
		}
	}

	echo json_encode($obj, JSON_PRETTY_PRINT);
	}
else
{
	// http://www.datatables.net/examples/data_sources/ajax.html
	?>
	<head>
		<link href="https://cdn.datatables.net/1.10.10/css/jquery.dataTables.min.css" rel="stylesheet">
		<script type="text/javascript" language="javascript" src="http://code.jquery.com/jquery-1.12.0.min.js"></script>
		<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.10/js/jquery.dataTables.min.js"></script>
		<script>
		$(document).ready(function() 
		{
		    $('#example').DataTable( 
		    {
		        "ajax": 'index.php?secret=<?php echo $_GET['secret']; ?>&out=json_data',
		        "order": [[ 0, "desc" ]]
		    } );
		});
		</script>
	</head>
	<body>
		<table id="example" class="display" cellspacing="0" width="100%">
	        <thead>
	            <tr>
	                <th>Date</th>
	                <th>Domain</th>
	                <th>Ip</th>
	                <th>Agent</th>
	                <th>Method</th>
	                <th>Get</th>
	                <th>Post</th>
	            </tr>
	        </thead>
	        <tfoot>
	            <tr>
	                <th>Date</th>
	                <th>Domain</th>
	                <th>Ip</th>
	                <th>Agent</th>
	                <th>Method</th>
	                <th>Get</th>
	                <th>Post</th>
	            </tr>
	        </tfoot>
	    </table>
	    <a href='?secret=<?php echo $_GET['secret']; ?>&clear=1'>Clear log</a>
	</body>
<?php
}
