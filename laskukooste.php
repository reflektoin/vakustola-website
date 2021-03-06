<?php
header('Content-Type: text/html; charset=utf8');
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
'<meta http-equiv=Content-Type content="text/html; charset=utf8">'
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

function hae_tulokset($kysely)
{
$result = $kysely->execute();
$eka = $kysely->fetch(PDO::FETCH_ASSOC);
echo '<table>
    <thead>
        <tr>';
              //Let's create headers for the result table
        foreach ($eka as $key => $kentta)
        echo '<th> '. $key .'</th>';
        echo '</tr>
    </thead>';

if ($kysely->execute()) {
	while ($row = $kysely->fetch(PDO::FETCH_ASSOC)){
//	echo htmlentities($row['toimittaja_nimi']);

echo '<tbody>
    <tr>            ';
        foreach ($row as $key => $kentta)
//kun tiliointisumma-kenttä on vuorossa, niin korvataan merkkijonon piste pilkulla.
	if(strcmp($key, 'Summa')==0){
        echo '<td> '. str_replace(".", ",",$kentta) .'</td>';
}else{   echo '<td> '. $kentta .'</td>';
}
        echo '
    </tr>';

}
echo '</tbody>';
}
echo '</table>';

}

$pdo = new PDO('mysql:host='.$strHostName.';dbname='.$strDbName.';charset=utf8', $strUserName, $strPassword);
$nimi = $_GET['name'];
$edellinen_summa = 0;
$nykyinen_summa = 0;
if(strlen($nimi) >0){
$muutos_header = "Muutos edelliseen vuoteen";
//$statement = $pdo->prepare("select h.toimittaja_nimi, year(str_to_date(tositepvm, '%d.%m.%Y')) as 'Vuosi', sum(tiliointisumma ) as 'Summa', ' ' as '$muutos_header' from hankinta h where h.toimittaja_nimi = :in_toim_nimi group by 1, 2 order by 2 asc");
$statement = $pdo->prepare("select h.toimittaja_nimi, 
year(str_to_date(tositepvm, '%d.%m.%Y')) as 'Vuosi', 
sum(tiliointisumma ) as 'Summa', 
' ' as '$muutos_header' 

from hankinta h where h.toimittaja_nimi = :in_toim_nimi group by 1, 2 order by 2 asc");
$statement->bindValue(":in_toim_nimi", $nimi);

}
$result = $statement->execute();
$eka = $statement->fetch(PDO::FETCH_ASSOC);
echo '<h3>Laskut vuosittain</h3>';
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
	if(strcmp($key, 'Summa')==0){
          echo '<td> '. str_replace(".", ",",$kentta) .'</td>';

}elseif (strcmp($key, $muutos_header)==0){
	  echo '<td> '. str_replace(".", ",",$row['Summa']-$edellinen_summa) .'</td>';
}
else{
  echo '<td> '. $kentta .'</td>';
}
$edellinen_summa = $row['Summa'];
        echo '
    </tr>';

}
echo '</tbody>
     </table>
     ';
}

//hankintayksikkokooste
if(strlen($nimi) > 0){
echo '<h3>'.mb_convert_encoding("Laskut vuosittain ja hankintayksik�itt�in", "utf8", "latin1").'</h3>';
$statement = $pdo->prepare("select h.toimittaja_nimi, year(str_to_date(tositepvm, '%d.%m.%Y')) as 'Vuosi', h.hankintayksikko, sum(tiliointisumma ) as 'Summa' from hankinta h where h.toimittaja_nimi = :in_toim_nimi group by 1, 2, 3");
$statement->bindValue(":in_toim_nimi", $nimi);

hae_tulokset($statement);

//hallinnonalan kooste
echo '<h3>'.utf8_decode("Laskut vuosittain ja hallinnonaloittain").'</h3>';
$statement = $pdo->prepare("select h.toimittaja_nimi, year(str_to_date(tositepvm, '%d.%m.%Y')) as 'Vuosi', h.ylaorganisaatio, sum(tiliointisumma ) as 'Summa' from hankinta h where h.toimittaja_nimi = :in_toim_nimi group by 1, 2, 3");
$statement->bindValue(":in_toim_nimi", $nimi);

hae_tulokset($statement);

echo '<h3>'.mb_convert_encoding("Laskujen keskiarvo", "utf8", "latin1").'</h3>';
$statement = $pdo->prepare("select  
toimittaja_nimi,
avg(summa) 'Summa' from (
select lasku_id, 
sum(tiliointisumma) 'summa', 
tositepvm, 
toimittaja_y_tunnus, 
toimittaja_nimi, 
hankintayksikko_tunnus, 
hankintayksikko, 
ylaorganisaatio_tunnus, 
ylaorganisaatio, 
sektori, 
tuote_palveluryhma, 
hankintakategoria 
from hankinta where toimittaja_nimi = :in_toim_nimi group by lasku_id, tositepvm, 
toimittaja_y_tunnus, 
toimittaja_nimi, 
hankintayksikko_tunnus, 
hankintayksikko, 
ylaorganisaatio_tunnus, 
ylaorganisaatio, 
sektori, 
tuote_palveluryhma, 
hankintakategoria ) X");
$statement->bindValue(":in_toim_nimi", $nimi);

hae_tulokset($statement);


//laskujen mediaani
echo '<h3>'.mb_convert_encoding("Laskujen mediaani", "utf8", "latin1").'</h3>';
$statement = $pdo->prepare("
SELECT x.toimittaja_nimi, x.SUMMA 'Summa' from (select summa, toimittaja_nimi 
from (
select lasku_id, 
sum(tiliointisumma) 'summa', 
tositepvm, 
toimittaja_y_tunnus, 
toimittaja_nimi, 
hankintayksikko_tunnus, 
hankintayksikko, 
ylaorganisaatio_tunnus, 
ylaorganisaatio, 
sektori, 
tuote_palveluryhma, 
hankintakategoria 
from hankinta where toimittaja_nimi = :in_toim_nimi group by lasku_id, tositepvm, 
toimittaja_y_tunnus, 
toimittaja_nimi, 
hankintayksikko_tunnus, 
hankintayksikko, 
ylaorganisaatio_tunnus, 
ylaorganisaatio, 
sektori, 
tuote_palveluryhma, 
hankintakategoria ) X order by 1
) x, (select summa 
from (
select lasku_id, 
sum(tiliointisumma) 'summa', 
tositepvm, 
toimittaja_y_tunnus, 
toimittaja_nimi, 
hankintayksikko_tunnus, 
hankintayksikko, 
ylaorganisaatio_tunnus, 
ylaorganisaatio, 
sektori, 
tuote_palveluryhma, 
hankintakategoria 
from hankinta where toimittaja_nimi = :in_toim_nimi group by lasku_id, tositepvm, 
toimittaja_y_tunnus, 
toimittaja_nimi, 
hankintayksikko_tunnus, 
hankintayksikko, 
ylaorganisaatio_tunnus, 
ylaorganisaatio, 
sektori, 
tuote_palveluryhma, 
hankintakategoria ) X order by 1
) y
GROUP BY x.SUMMA
HAVING SUM(SIGN(1-SIGN(y.SUMMA-x.SUMMA)))/COUNT(*) > .5
LIMIT 1");
$statement->bindValue(":in_toim_nimi", $nimi);
hae_tulokset($statement);

/*
//Moodi, ainoastaan yleisin luku
echo '<h3>'.mb_convert_encoding("Laskujen moodi", "utf8", "latin1").'</h3>';
$statement = $pdo->prepare("
select summa, frequency from(SELECT X.summa, COUNT(X.summa) AS frequency
    FROM (select lasku_id, 
sum(tiliointisumma) 'Summa', 
toimittaja_y_tunnus, 
toimittaja_nimi
from hankinta where toimittaja_nimi = :in_toim_nimi group by lasku_id, 
toimittaja_y_tunnus, 
toimittaja_nimi) X
     GROUP BY summa 
     ORDER BY frequency DESC limit 1) T1;");
$statement->bindValue(":in_toim_nimi", $nimi);
hae_tulokset($statement);
*/
//kaikkien lukujen moodit
echo '<h3>'.mb_convert_encoding("Laskujen moodi", "utf8", "latin1").'</h3>';
$statement = $pdo->prepare("
SELECT X.summa 'Summa', COUNT(X.summa) AS frequency
    FROM (select lasku_id, 
sum(tiliointisumma) 'Summa', 
toimittaja_y_tunnus, 
toimittaja_nimi
from hankinta where toimittaja_nimi = :in_toim_nimi group by lasku_id, 
toimittaja_y_tunnus, 
toimittaja_nimi) X
     GROUP BY summa 
     ORDER BY frequency DESC
");
$statement->bindValue(":in_toim_nimi", $nimi);
hae_tulokset($statement);

}
$pdo = null; //close connection
echo '
</table>
</body>
</html>';

?>


