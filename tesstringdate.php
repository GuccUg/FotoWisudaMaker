<?

$sidang = '26/10/2013';
$newDate = date("Y-m-d", strtotime(str_replace('/','-',$sidang)));

echo $newDate;

?>