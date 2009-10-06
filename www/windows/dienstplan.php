<?php

//error_reporting(E_ALL);
// $_SESSION['LEVEL_CURRENT'] = LEVEL_IMPORTANT;

get_http_var( 'plan_dienst', '/^[0-9\/]+$/', '1/2', true ); // fuer anzeige rotationsplan

$editable = ! $readonly;

get_http_var( 'action', 'w', false );
$editable or $action = false;

if( $action ) {
  $parts = explode( '_', $action );
  $action = $parts[0];
  if( count( $parts ) > 1 ) {
    $id = sprintf( '%u', $parts[1] );
  } else {
    $id = 0;
  }
}

get_http_var("startdatum_day",'u',0);
get_http_var("startdatum_month",'u',0);
get_http_var("startdatum_year",'u',0);
get_http_var("enddatum_day",'u',0);
get_http_var("enddatum_month",'u',0);
get_http_var("enddatum_year",'u',0);

if( $startdatum_day ) {
  $startdatum = "$startdatum_year-$startdatum_month-$startdatum_day";
  $enddatum = "$enddatum_year-$enddatum_month-$enddatum_day";
} else {
  $startdatum = date("Y-m-d");
  $enddatum = $startdatum;
}

switch( $action ) {
  case 'diensteErstellen':
    need( $startdatum_day );
    create_dienste( $startdatum, $enddatum, $dienstfrequenz );
    break;
  case 'dienstLoeschen':
    need_http_var( 'message', 'U' );
    sql_delete_dienst( $message );
    break;
  case 'dienstInsert':
    // TODO...
    break;
  case 'moveUp':
    need_http_var( 'message', 'U' );
    sql_change_rotationsplan( $message, $plan_dienst, false );
    break;
  case 'moveDown':
    need_http_var( 'message', 'U' );
    sql_change_rotationsplan( $message, $plan_dienst, true );
    break;
  case 'uebernehmen':
    need( $id );
    get_http_var( 'message', 'u', 0 ) or $message = 0;
    $abgesprochen = $message;
    $dienst = sql_dienst( $id );
    if( $dienst["status"]=="Offen" || $abgesprochen ) {
      sql_dienst_akzeptieren( $id, $abgesprochen );
    } else {
      open_div( 'warn' );
      ?> Dies müsste mit der andern Gruppe abgesprochen sein oder die Gruppe ist nach mehreren
         Versuchen (Telefon und Email) nicht erreichbar 
      <?
      echo fc_action( 'class=button,text=Klar', sprintf( 'action=uebernehmen_%u,message=1', $id ) );
      close_div();
      smallskip();
    }
    break;
  case 'wirdOffen':
    need( $id );
    sql_dienst_wird_offen( $id );
    break;
  case 'abtauschen':
    need( $id );
    $dienst = sql_dienst( $id );
    get_http_var( 'abtauschdatum', 'R', false );
    if( ! $abtauschdatum ){
      $dates = sql_dates_dienst( $dienst['dienst'], "Vorgeschlagen" );
      if( count($dates) <= 1 ) {
        sql_dienst_wird_offen( $id );
        open_div( 'warn', '', 'Keine Tauschmöglichkeit: Dienst ist jetzt offen!' );
      } else {
        open_div( 'warn', 'Bitte Ausweichdatum auswählen:' );
          open_form( '', sprintf( 'action=abtauschen_%u', $id ) );
            open_select( 'abtauschdatum' );
              echo "<option value=''>(bitte auswaehlen)</option>";
              foreach( $dates as $date ) {
                echo "<option value={$date['datum']}>{$date['datum']}</option>";
              }
            close_select();
            open_div( 'right' );
            submission_button( 'Dieses Datum geht' );
          close_form();
        close_div();
      }
    } else {
      sql_dienst_abtauschen( $id, $abtauschdatum );
    }
    break;
  case 'akzeptieren':
    need( $id );
    sql_dienst_akzeptieren( $id );
    break;
  case "gruppeAendern":
    need_http_var( 'message', 'u' );
    need( $id );  // hier: eine dienste.id!
    $gruppe_neu_id = $message;
    sql_dienst_gruppe_aendern( $id, $gruppe_neu_id );
    break;
  case "personAendern":
    need_http_var( 'message', 'u' );
    need( $id );  // hier: eine dienste.id!
    $person_neu_id = $message;
    sql_dienst_person_aendern( $id, $person_neu_id );
    break;
}


if( hat_dienst(5) or true ) {
  open_div( '', 'id=Zusatz' );

    ?> <h1>Dienste erstellen</h1> <?

    open_form( '', 'action=create' );
      open_table( 'smallskip' );
        open_tr();
          open_td( '', '', "Verteile Dienste mit" );
          open_td( 'quad', '', int_view( 7, 'dienstfrequenz' ) ." -taegigem Abstand" );
        form_row_date( 'ab dem:', 'startdatum', $startdatum );
        form_row_date( 'bis einschliesslich:', 'enddatum', $enddatum );
        open_tr();
          open_td( 'right', "colspan='2'" );
            submission_button( 'Dienste Erstellen' );
      close_table();
    close_form();
    smallskip();

    ?> <h1>Rotationsplan</h1> <?

    ?> Rotationsplan für <?
     open_select( 'plan_dienst', 'autoreload' );
       foreach( array( '1/2', '3', '4' ) as $dienst ) {
         $selected = ( $plan_dienst == $dienst ? 'selected' : '' );
         echo "<option value='$dienst' $selected>Dienst $dienst</option>";
       }
     close_select();
    ?> bearbeiten: <?

    open_table( 'smallskip' );
      foreach( sql_rotationsplan( $plan_dienst ) as $mitglied ) {
        $id = $mitglied['gruppenmitglieder_id'];
        open_tr( 'smallskip' );
          open_th( '', '', $mitglied['nr'] );
          open_td( '', '', "Gruppe {$mitglied['gruppennummer']}: {$mitglied['name']}" );
          open_td( '', '', fc_action( 'update,text=UP', sprintf( "action=moveUp,message=%u", $id ) ) );
          open_td( '', '', fc_action( 'update,text=DOWN', sprintf( "action=moveDown,message=%u", $id ) ) );
        close_tr();
      }
    close_table();

  close_div();
}


?> <h1>Dienstliste</h1> <?

open_div( 'kommentar' );
  open_span( '', '',
    "Zum Abtauschen von Diensten: Beide Gruppen klicken auf <code>kann doch nicht</code>
     und übernehmen anschliessend den von der andern Gruppe entstandenen offen Dienst." );
  open_span( '', '', wikiLink("foodsoft:dienstplan", "Mehr Infos im Wiki..." ) );
close_div();


$dienstnamen = array( '1/2', '3', '4' );

//Formular vorbereiten und anzeigen
open_table( 'list' );
  open_th( '', '', 'Datum' );
  open_th( '', '', 'Dienst 1/2' );
  open_th( '', '', 'Dienst 3' );
  open_th( '', '', 'Dienst 4' );

  $dienste = sql_dienste();

  $currentDate = "initial";
  $dienst = current( $dienste );
  while( $dienst ) {
    if( $dienst["lieferdatum"] != $currentDate ) {
      $currentDate = $dienst["lieferdatum"];
      open_tr();
      open_th( 'top', '', $currentDate );
    }
    foreach( $dienstnamen as $d ) {
      open_td( 'top' );
        open_table( 'inner layout hfill tight' );
          while( $dienst and ( $dienst['dienst'] == $d ) ) {
            open_tr();
            open_td();
              // echo "{$dienst['id']} , {$dienst['soon']}";
              dienstplan_eintrag_view( $dienst['id'] );
              smallskip();
            $dienst = next( $dienste );
          }
        close_table();
    }
  }
close_table();

?>
