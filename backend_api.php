<?php
date_default_timezone_set('Asia/Jakarta');

require_once '_db.php';

$json = file_get_contents('php://input');
$params = json_decode($json);

$kode = $params->code;

// Get data 
$stmt = $db->prepare("SELECT id FROM resources WHERE kode='$kode'");
$stmt->execute();

$timesheet_resources = $stmt->fetchAll();
$nrows = count($timesheet_resources);

if($nrows <= 0){
	$msg = array(
		'status' 	=> "error",
		'msg'    	=> "Kode tidak teregistrasi!!!",
		'data'	 	=> "",
	);
	
	$msg = json_encode($msg);
	return print_r($msg);
}

// Get ID resource
$resource = 0;

foreach($timesheet_resources as $res) {
  $resource = $res['id'];
}

// Check start datetime
$datetime = date('Y-m-d H:i:s');
$y = date('Y'); $m = date('m'); $d = date('d');

$events = $db->prepare("SELECT id FROM events WHERE resource_id='$resource' AND YEAR(start)='$y' AND MONTH(start)='$m' AND DAY(start)='$d' ");
$events->execute();

$nrows = $events->fetchColumn();

if($nrows <= 0){
	$name = "Online";
	
	$stmt = $db->prepare("INSERT INTO events (name, start, end, resource_id) VALUES (:name, :start, :end, :resource)");
	$stmt->bindParam(':start', $datetime);
	$stmt->bindParam(':end', $datetime);
	$stmt->bindParam(':name', $name);
	$stmt->bindParam(':resource', $resource);
	$stmt->execute();
}

// Check timeout
$select = "SELECT max(id) FROM events WHERE resource_id='$resource' AND YEAR(start)='$y' AND MONTH(start)='$m' AND DAY(start)='$d' ";
$events = $db->prepare("SELECT id,end FROM events WHERE id = ($select) ");
$events->execute();

$timesheet_events = $events->fetchAll();

//$times = timediff($datetime,$timesheet_events[0]['end']);

$date1 = strtotime($datetime);
$date2 = strtotime($timesheet_events[0]['end']);
$interval = $date2 - $date1;

$times = floor($interval / 60);;
if($times > 5){
	$stmt = $db->prepare("INSERT INTO events (name, start, end, resource_id) VALUES (:name, :start, :end, :resource)");
	$stmt->bindParam(':start', $datetime);
	$stmt->bindParam(':end', $datetime);
	$stmt->bindParam(':name', 'Online');
	$stmt->bindParam(':resource', $resource);
	$stmt->execute();
}else{
	$idev = $timesheet_events[0]['id'];
	
	$stmt = $db->prepare("UPDATE events SET end = :end WHERE id = :id");
	$stmt->bindParam(':id', $idev);
	$stmt->bindParam(':end', $datetime);
	$stmt->execute();
}

$msg = array(
		'status' 	=> "success",
		'msg'    	=> "Koneksi berhasil!!!",
		'data'	 	=> "time : " . $datetime,
	);
	
	$msg = json_encode($msg);
	return print_r($msg);