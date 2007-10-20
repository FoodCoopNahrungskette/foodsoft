<?php
  //
  // konto.php: Bankkonto-Verwaltung
  //

  if( ! $angemeldet ) {
    exit( "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>");
  } 

  ?> <h1>Kontoverwaltung</h1> <?

  $konten = sql_konten();
  if( mysql_num_rows($konten) < 1 ) {
    ?>
      <div class='warn'>
        Keine Konten definiert!
        <a href='index.php'>Zurück...</a>
      </div>
    <?
    return;
  }
  if( mysql_num_rows($konten) == 1 ) {
    $row = mysql_fetch_array($konten);
    $konto_id = $row['id'];
    mysql_data_seek( $konten, 0 );
  } else {
    $konto_id = 0;
  }
  get_http_var( 'konto_id', 'u', $konto_id, true );

  ?>
    <h4>Konten der Foodcoop:</h4>
    <div style='padding-bottom:2em;'>
    <table style='padding-bottom:2em;' class='liste'>
      <tr>
        <th>Name</th>
        <th>BLZ</th>
        <th>Konto-Nr</th>
      </tr>
  <?
  while( $row = mysql_fetch_array($konten) ) {
    if( $row['id'] != $konto_id ) {
      echo "
        <tr>
          <td><a class='tabelle' href='" . self_url('konto_id') . "&konto_id={$row['id']}'>{$row['name']}</a></td>
          <td>{$row['blz']}</td>
          <td>{$row['kontonr']}</td>
        </tr>
      ";
    } else {
      echo "
        <tr class='active'>
          <td style='font-weight:bold;'>{$row['name']}</td>
          <td>{$row['blz']}</td>
          <td>{$row['kontonr']}</td>
        </tr>
      ";
    }
  }
  ?> </table></div> <?

  if( ! $konto_id )
    return;

  $auszuege = sql_kontoauszug( $konto_id );

  ?>
    <h3>Auszüge:</h3>
    
    <table class='liste'>
      <tr class='legende'>
        <th>Jahr</th>
        <th>Nr</th>
        <th>Anzahl Posten</th>
        <th>Saldo</th>
      </tr>
  <?
  
  while( $auszug = mysql_fetch_array( $auszuege ) ) {
    $jahr = $auszug['kontoauszug_jahr'];
    $nr = $auszug['kontoauszug_nr'];

    $posten = mysql_num_rows( sql_kontoauszug( $konto_id, $jahr, $nr ) );
    $saldo = sql_saldo( $konto_id, $auszug['kontoauszug_jahr'], $auszug['kontoauszug_nr'] );
    need( mysql_num_rows( $saldo ) == 1 );

    $saldo_row = mysql_fetch_array( $saldo );
    echo "
      <tr>
        <td>$jahr</td>
        <td class='number'>$nr</td>
        <td class='number'>$posten</td>
        <td class='number'>{$saldo_row['saldo']}</td>
      </tr>
    ";
  }
  ?> </table> <?





?>

