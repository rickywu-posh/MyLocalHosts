<?php
session_start();
$toEdit=isset($_GET['edit']);
$hosts_file="C:/Windows/System32/drivers/etc/hosts";
$lines=file($hosts_file,FILE_IGNORE_NEW_LINES);
$hosts=[];
foreach($lines as $line_no=>$line)
{
	if(preg_match('/\s*(#*)((\d{1,3}\.){3}\d{1,3})\s+([\w\.-]+)\s*/',$line,$m))
	{
		$hosts[]=[
			'line_no'=>$line_no,
			'isActive'=>empty($m[1]),
			'ip'=>$m[2],
			'domain'=>$m[4],
		];
	}
}
if(!empty($_POST))
{
	$line_no=$_POST['line_no'];
	$ip=$_POST['ip'];
	$domain=$_POST['domain'];
	$count=count($line_no);
	
	
	for($i=0;$i<$count;$i++)
	{
		if(!empty($_POST['delete::'.$line_no[$i]]))
		{
			$lines[$line_no[$i]]='deleted';
			continue;
		}
		$lines[$line_no[$i]]=(empty($_POST['active::'.$line_no[$i]])?'#':'')
			.$_POST['ip::'.$line_no[$i]].'   '.$_POST['domain::'.$line_no[$i]];
	}
	
	$new_lines=[];
	$new_line_no=$_POST['line_no::new'];
	$new_count=count($_POST['line_no::new']);
	for($i=0;$i<$new_count;$i++)
	{
		$new_no=$new_line_no[$i];
		if(!$_POST['ip::new'.$new_no] || !$_POST['domain::new'.$new_no])
		{
			continue;
		}
		$lines[]=(empty($_POST['active::new'.$new_no])?'#':'')
			.$_POST['ip::new'.$new_no].'   '.$_POST['domain::new'.$new_no];
	}
	$new_contents=implode("\n",array_filter($lines,function($row){
		return $row!=='deleted';
	}));
	!empty($_POST['backup']) && copy($hosts_file,$hosts_file.'_bakAt'.date('YmdHis'));
	if(!file_put_contents($hosts_file,$new_contents))
	{
		$_SESSION['EDIT_HOSTS_SUCCESS']=false;
	}
	header('location: http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	exit;
}
?>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- 新 Bootstrap 核心 CSS 文件 -->
<link rel="stylesheet" href="//cdn.bootcss.com/bootstrap/3.3.5/css/bootstrap.min.css">

<!-- 可选的Bootstrap主题文件（一般不用引入） -->
<link rel="stylesheet" href="//cdn.bootcss.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">

<!-- jQuery文件。务必在bootstrap.min.js 之前引入 -->
<script src="//cdn.bootcss.com/jquery/1.11.3/jquery.min.js"></script>

<!-- 最新的 Bootstrap 核心 JavaScript 文件 -->
<script src="//cdn.bootcss.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

<style type="text/css">
.form-control[type=checkbox]{
	box-shadow:none!important;
	-webkit-box-shadow:none!important;
}
.table:not(.just_view) td{
	text-align: center;
}
.table th {
  color: #fff;
  background-color: #555;
  border: 1px solid #555;
  font-size: 12px;
  padding: 3px;
  vertical-align: top;
  text-align: left;
}
.table td{
	vertical-align: middle!important;
}
#add_one_row:active,#add_one_row:focus{
	outline:none!important;
}
</style>
</head>
<?php
if(empty($_POST) && isset($_SESSION['EDIT_HOSTS_SUCCESS']) && !$_SESSION['EDIT_HOSTS_SUCCESS'])
{
	echo '<div class="alert alert-danger alert-dismissable">
	Failed to Save Hosts file!<button type="button" class="close" data-dismiss="alert" 
      aria-hidden="true">
      &times;
    </button></div>';
	unset($_SESSION['EDIT_HOSTS_SUCCESS']);
}

?>
<form method="post" class="form-hosts" role="form" action="">
<table class="table table-striped table-bordered table-hover table-condensed 
<?=($toEdit?'':'just_view')?>" style="width:auto;margin:20px auto" id="hosts_table">
<thead>
<tr>
<th>IP</th>
<th>Domain</th>
<th>Active?</th>
<?php
if($toEdit):
?>
<th>Delete?</th>
<?php 
endif;
?>
</tr>
</thead>
<tbody>
<?php
foreach($hosts as $host)
{
	
	if($toEdit)
	{
		$checked=$host['isActive']?'checked':'';
		echo <<<TR
		<tr>
		<input type="hidden" name="line_no[]" value="{$host['line_no']}" />
		<td><input class="form-control" type="text" 
			name="ip::{$host['line_no']}" value="{$host['ip']}" /></td>
		<td><input class="form-control" type="text" 
			name="domain::{$host['line_no']}" value="{$host['domain']}" /></td>
		<td><input class="form-control" type="checkbox" 
			name="active::{$host['line_no']}" $checked /></td>
		<td><input class="form-control" type="checkbox" 
			name="delete::{$host['line_no']}" /></td>
		</tr>
TR;
	}else{
		$checked=$host['isActive']?'Yes':'No';
		echo <<<TR
		<tr>
		<td>{$host['ip']}</td>
		<td>{$host['domain']}</td>
		<td>$checked</td>
		</tr>
TR;
		
	}
	
}
?>
</tbody>
<tfoot>
<?php
if($toEdit):
?>
<tr><td colspan="4" style="text-align:left;">
<button id="add_one_row" class="btn btn-xs">Add One Row</button>

<input type="submit" value="Submit" class="btn btn-primary pull-right" />
<label class="checkbox-inline pull-right" style="margin-top:8px;margin-right:5px;">
<input type="checkbox" name="backup"> Backup
</label>
</td></tr>
<?php
else:
?>
<tr>
<td colspan="3" style="text-align:right;">
<a href="?edit">Edit</a>
</td>
</tr>
<?php
endif;
?>
</tfoot>
</table>

</form>

<script>
var new_row=1;
$(function(){
	
	$("#add_one_row").click(function(){
		$row_html='<tr> \
		<input type="hidden" name="line_no::new[]" value="'+new_row+'" /> \
		<td><input class="form-control" type="text" \
		name="ip::new'+new_row+'" value=""></td> \
		<td><input class="form-control" type="text" \
		name="domain::new'+new_row+'" value=""></td> \
		<td><input class="form-control" type="checkbox" \
		name="active::new'+new_row+'" checked=""></td> \
		<td>\
		<span class="glyphicon glyphicon-remove" id="delete::new'+new_row+'" style="cursor:pointer;"></span>  \
        </td> \
		</tr>';
		$("#hosts_table tbody").append($row_html);
		new_row++;
		return false;
	});
	$(document).on('click','[id^="delete::new"]',function(){
		$(this).closest('tr').remove();
	});
});
</script>