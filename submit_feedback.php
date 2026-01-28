<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || $_SESSION['user_role']!=='volunteer'){
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}

$data=json_decode(file_get_contents('php://input'),true);
if(!$data || !isset($data['report_id'],$data['feedback'])){
    echo json_encode(['success'=>false,'message'=>'Invalid data']);
    exit();
}

$report_id=intval($data['report_id']);
$feedback=trim($data['feedback']);
$volunteer_id=$_SESSION['user_id'];

try{
    $pdo=new PDO("mysql:host=localhost;dbname=pet_rescue;charset=utf8","root","");
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

    $stmt=$pdo->prepare("INSERT INTO report_feedback (report_id, volunteer_id, feedback, created_at) VALUES (:report_id,:volunteer_id,:feedback,NOW())");
    $stmt->bindParam(':report_id',$report_id,PDO::PARAM_INT);
    $stmt->bindParam(':volunteer_id',$volunteer_id,PDO::PARAM_INT);
    $stmt->bindParam(':feedback',$feedback,PDO::PARAM_STR);

    if($stmt->execute()) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false,'message'=>'Insert failed']);
}catch(PDOException $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>
