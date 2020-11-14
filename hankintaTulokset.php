<?php
header('Content-Type: text/html; charset=latin1');
echo '<html lang="fi">
<style type="text/css">
    table,
td {
    border: 1px solid #333;
}

thead,
tfoot {
    background-color: #333;
    color: #fff;
}

    </style>
  <head>'.
'<meta http-equiv=Content-Type content="text/html; charset=latin1">'
.'  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Cache-Control" content="no-cache">
  <title>Laskutiedot</title>
</head>';
echo '<body>';

if(strcmp(basename(getcwd()), "test") == 0){
require_once (__DIR__.'/../../../testiasetukset.php');
}
else{
require_once (__DIR__.'/../../asetukset.php');

}

$pdo = new PDO('mysql:host='.$strHostName.';dbname='.$strDbName.';charset=latin1', $strUserName, $strPassword);
$nimi = $_GET['name'];
$y_tunnus = $_GET['y-tunnus'];
$yritys = $_GET['yritys'];

if(strlen($nimi) >0){
  $statement = $pdo->prepare("SELECT * FROM hankinta WHERE toimittaja_nimi like :in_toim_nimi");
  $statement->bindValue(":in_toim_nimi", $nimi.'%');

}
elseif (strlen($y_tunnus) > 0){
//jos merkkijonosta loytyy valilyonti, niin oletetaan etta kyseessa on lista vaihtoehtja
if(strpos($y_tunnus, ' ') !== false){
  $palat = explode(" ", $y_tunnus);
  $clause = implode(',', array_fill(0, count($palat), '?'));
$qmarks = str_repeat('?,', count($palat) - 1) . '?';
$statement = $pdo->prepare("SELECT h.lasku_id, h.tili, h.tiliointisumma, h.tositepvm, h.toimittaja_y_tunnus, h.toimittaja_nimi, h.hankintayksikko_tunnus, h.hankintayksikko, h.ylaorganisaatio_tunnus, h.ylaorganisaatio, h.sektori, h.tuote_palveluryhma, h.hankintakategoria FROM hankinta h WHERE toimittaja_y_tunnus in ($qmarks)");
$statement->execute($palat);
}
else {
$statement = $pdo->prepare("SELECT h.lasku_id, h.tili, h.tiliointisumma, h.tositepvm, h.toimittaja_y_tunnus, h.toimittaja_nimi, h.hankintayksikko_tunnus, h.hankintayksikko, h.ylaorganisaatio_tunnus, h.ylaorganisaatio, h.sektori, h.tuote_palveluryhma, h.hankintakategoria FROM hankinta h WHERE toimittaja_y_tunnus =:in_y_toim_tunnus");
$statement->bindValue(":in_y_toim_tunnus", $y_tunnus);                                                                                                                                                                                       }                                                        
}
elseif (strlen($yritys) > 0){
$statement = $pdo->prepare("SELECT y.toimittaja_y_tunnus, y.toimittaja_nimi, concat('<a href=\"laskukooste.php?name=',y.toimittaja_nimi, '\">linkki</a>') as 'Laskukooste' FROM yritys y WHERE y.toimittaja_nimi like :in_toim_nimi");
$statement->bindValue(":in_toim_nimi", $yritys);

}
$result = $statement->execute();
$eka = $statement->fetch(PDO::FETCH_ASSOC);
echo '<table>
    <thead>
        <tr>';
              //Let's create headers for the result table
        foreach ($eka as $key => $kentta)
        echo '<th> '. $key .'</th>';
        echo '</tr>
    </thead>';

if ($statement->execute()) {
	while ($row = $statement->fetch(PDO::FETCH_ASSOC)){
//	echo htmlentities($row['toimittaja_nimi']);

echo '<tbody>
    <tr>            ';
        foreach ($row as $key => $kentta)
//kun tiliointisumma-kenttä on vuorossa, niin korvataan merkkijonon piste pilkulla.
	if(strcmp($key, 'tiliointisumma')==0){
        echo '<td> '. str_replace(".", ",",$kentta) .'</td>';
}else{   echo '<td> '. $kentta .'</td>';
}
        echo '
    </tr>';

}
}

$pdo = null; //close connection
echo '</tbody>
</table>
</body>
</html>';

?>


