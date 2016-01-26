<?php
$file='log.txt';	// Log File

if(!isset($_GET['secret']) || $_GET['secret']!='owned')	// Secret Password
{
	// Log it!
	$file=fopen($file,'a+');

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

	fseek($file,0,SEEK_END);
	if(ftell($file)>0) fwrite($file,",\n");
	fwrite($file,json_encode($array));

	fclose($file);
	die();
}
// Viewer
if(!file_exists($file))
{
	echo 'No data ...';
	die();
} 

// Clear

if (isset($_GET['clear']) && $_GET['clear']=='1')
{
	unlink($file);
	header('Location: ?secret='.$_GET['secret']);
	die();
}

// Parse

$obj=file_get_contents($file);
$obj='{"data":['.$obj.']}';
$obj=json_decode($obj);
//$obj->data=array_reverse($obj->data);

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
