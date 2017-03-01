<?php
header('Content-Type: text/html; charset=utf-8');

class timer {
  var $starttime;
  function timer(){
    $this->starttime=$this->microtime_float();
  }
  function microtime_float(){
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
  }
  function time(){
    $endtime = $this->microtime_float();
    $time = $endtime - $this->starttime;
    $time=round(($time*1000),3);
    return $time;
  }
}
//  startc
$script=new timer;

$process=false;
$welcome='Salut essayer de résoudre ce sudoku.';
$warning='Ne rentrez seulement que des chiffres';
$stuck='Allez on va y arriver ensemble !!';
$done='Cest bon?';
$oops=' Il semble y avoir un problème. Êtes-vous sûr de saisir correctement les bon numéros?';
$message=$welcome;
function report($time,$runs,$guesses){
  $report='Process time: '.$time.' milliseconds.';
  if($runs>0){
    $report.='<br>Made '.$runs.' runs through the cell checks.';
  }
  if($guesses>0){
    $report.='<br>Made '.$guesses.' guesses.';
  }
  return $report;
}
$runs=0;
$guesses=0;
$maxguesses=20;
$log='';



//ligne pour générer un tableau en 9X9 mais pour l'instant c'est vide.
$Numbers=array(1,2,3,4,5,6,7,8,9);
$Array = array();
$Indexes = array();
$Rows=array();
foreach($Numbers as $row){
  $Row=array();
  foreach($Numbers as $col){
    $Array[$row][$col] = '';
    $Indexes[] = array('row'=>$row,'column'=>$col);
    $Row[]=array('row'=>$row,'column'=>$col);
  }
  $Rows[]=$Row;
}

//Generer des colonnes.
$Columns=array();
foreach($Numbers as $col){
  $Column=array();
  foreach($Numbers as $row){
    $Column[]=array('row'=>$row,'column'=>$col);
  }
  $Columns[]=$Column;
}

//Generer des blocs.
function makeblock($startr,$startc){
  $Block=array();
  for($r=0;$r<=2;$r++){
    for($c=0;$c<=2;$c++){
      $Block[]=array('row'=>$startr+$r,'column'=>$startc+$c);
    }
  }
  return $Block;
}

$Blocks=array();
for($row=1;$row<=7;$row+=3){
  for($col=1;$col<=7;$col+=3){
    $Blocks[]=makeblock($row,$col);
  }
}

$Groups=array_merge($Rows,$Columns,$Blocks);

//Entrée et controle
if(isset($_POST['submit'])){
  $process=true;
  foreach($Indexes as $Index){
    $input=$_POST[$Index['row'].','.$Index['column']];
    if (preg_match('/[^1-9 ,-0.]/u',$input)){
      $process=false;
      $message=$warning;
      break;
    }
  }
}

//////////////////////////////////////////////////////////////////
////////////////////////////////////////
//////////////////////////////////////////////////////////////////

//Recherche les cellules avec une seule valeur et aussi supprime cette valeur des autres cellules et bloc dans l'ensemble.
function singles($Group, $Array){
  global $log;
  foreach($Group as $Cell){
    $r=$Cell['row'];
    $c=$Cell['column'];
    $val=$Array[$r][$c];
    if(strlen($val)==1){
      $log.= 's';
      foreach($Group as $Cell2){
        $r2=$Cell2['row'];
        $c2=$Cell2['column'];
        $val2=$Array[$r2][$c2];
        if(strlen($val2)>1){
          $newval=str_replace($val,'', $val2);
          $Array[$r2][$c2]=$newval;
        }
      }
    }
  }
  return $Array;
}

//Recherche des nombres qui sont dans une seule cellule d'un groupe et supprime d'autres nombres de cette cellule.
function oneplace($Group, $Array){
  global $log;
  for ($num=1;$num<=9;$num++){
    $found='';
    foreach($Group as $index=>$Cell){
      $r=$Cell['row'];
      $c=$Cell['column'];
      $val=$Array[$r][$c];
      if(strpos($val,"$num") !== false){
        $found.="$index";
      }
    }
    if (strlen($found)==1){
      $log.= 'p';
      $found=(int) $found;
      $r=$Group[$found]['row'];
      $c=$Group[$found]['column'];
      $Array[$r][$c]="$num";
    }
  }
  return $Array;
}

//Recherche des paires de cellules avec les mêmes nombres et supprime ces deux autres  nombres dans d'autres cellules dans le groupe
function doubles($Group, $Array){
  global $log;
  $Values=array();
  foreach ($Group as $key=>$Cell){
    $r=$Cell['row'];
    $c=$Cell['column'];
    $Values[$key]=$Array[$r][$c];
  }
  for($num1=1;$num1<=9;$num1++){
        for($num2=$num1+1;$num2<=9;$num2++){
            $double="$num1"."$num2";
            $Found=array_keys($Values,$double);
            if(count($Found)!=2){
                continue;
            }
            $log.= '(dbl)';
            foreach($Group as $key=>$Cell){
                if($key==$Found[0] || $key==$Found[1]){
                continue;
                }
                $r=$Cell['row'];
                $c=$Cell['column'];
                $val=$Array[$r][$c];
                $val=str_replace("$num1",'',$val);
                $val=str_replace("$num2",'',$val);
                $Array[$r][$c]=$val;
            }
        }
    }
    return $Array;
}

//Recherche une paire de nombres qui se trouvent dans seulement  deux cellules d'un groupe et supprime d'autres nombres de ces deux cellules
function twoplaces($Group, $Array){
  global $log;
  for($num1=1;$num1<=9;$num1++){
    for($num2=$num1+1;$num2<=9;$num2++){
      $found1='';
      $found2='';
      foreach($Group as $index=>$Cell){
        $val=$Array[$Cell['row']][$Cell['column']];
        if(strpos($val,"$num1") !== false){
          $found1.="$index";
        }
        if(strpos($val,"$num2") !== false){
          $found2.="$index";
        }
      }
      if ($found1===$found2 && strlen($found1)==2){
        $log.= '(2pl)';
        $i1=(int)$found1[0];
        $i2=(int)$found1[1];
        $r1=$Group[$i1]['row'];
        $c1=$Group[$i1]['column'];
        $Array[$r1][$c1]="$num1"."$num2";
        $r2=$Group[$i2]['row'];
        $c2=$Group[$i2]['column'];
        $Array[$r2][$c2]="$num1"."$num2";
      }
    }
  }
  return $Array;
}

//Exécutez les contrôles de cellule jusqu'à ce que le tableau ne change plus
function cellchecks($Array){
  global $Groups,$runs,$log;
  $finished=false;
  while($finished==false){
    $Backup=$Array;
    $log.= 'start cellchecks:<div class="note">';
    //Vérifier les lignes, colonnes et blocs pour les numéros simples
    foreach($Groups as $Group){
      $Array=singles($Group, $Array);
    }
    $log.= '</div><div class="note">';
    //Vérifier les lignes, les colonnes et les blocs pour les nombres dans une seule cellule
    foreach($Groups as $Group){
      $Array=oneplace($Group, $Array);
    }
    $log.='</div>';
    if($Array==$Backup){
      //Si le tableau n'a pas encore changé, vérifier les lignes , les colonnes et les blocs pour les deux numéros dans deux cellules
      $log.= '<div class="note">';
      foreach($Groups as $Group){
        $Array=doubles($Group,$Array);
      }
      $log.='</div>';
    }

    if($Array==$Backup){
  //Si le tableau n'a pas encore changé, vérifiez les lignes, les colonnes et les blocs pour deux nombres n'apparaissant que dans les deux mêmes cellules
              $log.= '<div class="note">';
              foreach($Groups as $Group){
                  $Array=twoplaces($Group,$Array);
              }
              $log.='</div>';
          }
          //Le tableau a-t-il changé?
          if($Array==$Backup){
              $finished=true;
              $log.='array unchanged'.minitable($Array);
          }
          else{
              $runs++;
          }

      }
      return $Array;
  }

  //test: retourne "1" if array est une solution valide,
  //"2" si certaines cellules contiennent encore plusieurs nombres, "0" si array transgresse les règles sudoku
function test($Array){
      $multi=false;
      global $Groups,$log;
      foreach($Groups as $Group){
          $Check = array();
          foreach ($Group as $Cell){
              $val=$Array[$Cell['row']][$Cell['column']];
              switch(strlen($val)){
              case 0:
                  $log.= 'test:0';
                  return 0;
              case 1:
                  $Check[]=$val;
                  break;
              default :
                  $multi=true;
                  break;
              }
          }
          foreach ($Check as $value){
              if(count(array_keys($Check,$value))!==1){
                  $log.= 'test:0';
                  return 0;
              }
          }
      }
      if($multi){
          $log.= 'test:2';
          return 2;
      }
      $log.= 'test:1';
      return 1;
  }
  //recursive guess function for difficult puzzles
  function guess($Array,$maxguesses,$name){
      global $guesses,$log;
      //find cell with only 2 numbers in it
      foreach ($Array as $r=>$Cells){
          foreach($Cells as $c=>$v){
              if(strlen($v)==2){
                  $row=$r;
                  $col=$c;
                  $val=$v;
                  break 2;
              }
          }
      }
      if(!isset($val)){
          $log.= ' - no 2-number cells found - ';
          $log.= '"'.$name.'" return:2';
          return array(2,$Array);
      }
      //try each of those 2 numbers in turn
      $guesses++;
      $log.= '<div class="guess">starting guess "'.$name.'" (#'.$guesses.')';
      $Try1=$Try2=$Array;
      $Try1[$row][$col]=$val[0];
      $log.= '<br>Try1:<br>';
      $Try1=cellchecks($Try1);
      $result1=test($Try1);
      if($result1==1){
          $log.= ' Try1:SOLVED! "'.$name.'" return:1</div>';
          return array(1,$Try1);
      }
      $Try2[$row][$col]=$val[1];
      $log.= '<br>Try2:<br>';
      $Try2=cellchecks($Try2);
      $result2=test($Try2);
      if($result2==1){
          $log.= ' Try2:SOLVED! "'.$name.'" return:1</div>';
          return array(1,$Try2);
      }
      //try another round of guessing
      if($guesses<$maxguesses){
          if($result1==2){
              $Next1=guess($Try1,$maxguesses,$name.'-1');
              switch($Next1[0]){
              case 1:
                  $log.= '"'.$name.'" return:1</div>';
                  return array(1,$Next1[1]);
              case 2:
                  $Try1=$Next1[1];
                  break;
              case 0:
                  $result1=0;
                  break;
              }
          }
      }
      else{
          $log.= 'Ran out of guesses.';
      }
      if($guesses<$maxguesses){
          if($result2==2){
              $Next2=guess($Try2,$maxguesses,$name.'-2');
              switch($Next2[0]){
              case 1:
                  $log.= '"'.$name.'" return:1</div>';
                  return array(1,$Next2[1]);
              case 2:
                  $Try2=$Next2[1];
                  break;
              case 0:
                  $result2=0;
                  break;
              }
          }
      }
      else{
          $log.= 'Ran out of guesses.';
      }
      if($result1==2 && $result2==2){
          $log.= '"'.$name.'" return:2</div>';
          return array(2,$Array);
      }
      if($result1==2){
          $log.= '"'.$name.'" return:2</div>';
          return array(2,$Try1);
      }
      if($result2==2){
          $log.= '"'.$name.'" return:2</div>';
          return array(2,$Try2);
      }
      $log.= '"'.$name.'" return:0</div>';
      return array(0,$Array);
  }

  ///////////////////////////////////////////////////////////////////
  ///////END FUNCTIONS TO PROCESS ARRAY//////////////////////////////
  ///////////////////////////////////////////////////////////////////

  ////SCRIPT CONTINUES HERE////
  //cleanup input
  if($process){
  	$log = 'Start processing:<br>';
      foreach($Indexes as $Index){
          $input=$_POST[$Index['row'].','.$Index['column']];
          $clean='';
          foreach($Numbers as $number){
              if (strpos($input,"$number") !== false) {
              $clean.="$number";
              }
          }
          $input=$clean;
          if($input==''){$input='123456789';}
          $Array[$Index['row']][$Index['column']]=$input;
  	}

      //loop through the cell checks
      $Array=cellchecks($Array);
      $result=test($Array);
      switch ($result) {
      case 0:
          $message=$oops;
          break;
      case 1:
          $message=$done;
          break;
      case 2: //start guessing
          $Guessed=guess($Array,$maxguesses,'1');
          switch($Guessed[0]){
          case 0:
              $message=$oops;
              break;
          case 1:
              $Array=$Guessed[1];
              $message=$done;
              break;
          case 2:
              $Array=$Guessed[1];
              $message=$stuck;
              break;
          }
          break;
      }
  }

  //generate safe versions of data in array for echoing on page
  function htmlSafe($input) {
      //Only for HTML output, to prevent XSS and accidental breakage- not really needed here.
      return htmlspecialchars($input);
  }

  //generate table from array
  //this function makes the thick borders for the blocks, and makes the single figures bigger
  function setclass($row, $column, $value){
  	$class='';
  	if($row==4||$row==7){
  		$class='top';
  	}
  	if($column==4||$column==7){
  		$class.=' left';
  	}
  	if(strlen($value)<=1){
  		$class.=' single';
  	}
  	return $class;
  }
  //now the html table containing form inputs
  $table='<table id="main">';
  foreach ($Array as $row=>$Cells){
  	$table.='<tr>'."\n";
  	foreach ($Cells as $column=>$value){
          $inputsize='" size="9" maxlength="9"';
          if(strlen($value)<=1){
              $inputsize='" size="1" maxlength="1"';
      	}
  		$index="$row,$column";
  		$class=setclass($row,$column,$value);
  		$table.='<td class="'.$class.'"><input type="text" name="'.$index.$inputsize.' value="'.htmlSafe($value).'"></td>'."\n";
  	}
  	$table.='</tr>'."\n";
  }
  $table.='</table>';

  //function to make mini-table for processing log
  function minitable($Array){
      $table='<table class="mini">';
      foreach ($Array as $row=>$Cells){
      	$table.='<tr>'."\n";
      	foreach ($Cells as $column=>$value){
      		$class=setclass($row,$column,$value);
              $value=htmlSafe($value);
              if (strlen($value)>1){$value='&nbsp;';}
              if (strlen($value)==0){$value='x';}
      		$table.='<td class="'.$class.'">'.$value.'</td>'."\n";
      	}
      	$table.='</tr>'."\n";
      }
      $table.='</table>';
      return $table;
  }

  //  End TIMER
  $time=$script->time();
  $report=report($time,$runs,$guesses);
  ?>
  <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
     "http://www.w3.org/TR/html4/loose.dtd">
  <html>
  <head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>Sudoku</title>
  <style type="text/css" media="all">
  html, body, div, table, tr, th, td, ul, ol, dl, li, p, h1, h2, h3, h4, h5, h6, form, fieldset, a {margin:0;padding:0;border:0;}
  body {font-size:100%;padding:5px;}
  h1 {text-align:center;}
  table#main {border-collapse:collapse;border:solid #000 3px;margin:0 auto;}
  table#main input {border:none;margin:0;width:100%;padding:0;}
  table#main td {border:solid 1px #000;height:35px;width:40px;font-size:10px;padding:0;}
  table#main td.top {border-top:solid 3px #000;}
  table#main td.left {border-left:solid 3px #000;}
  table#main td.single input {font-size:28px;font-weight:bold;text-align:center;}
  table.mini {border-collapse:collapse;font-size:0.8em;border:solid 1px #000;}
  table.mini td {width:1em;height:1em;padding:0;line-height:1;}
  table.mini td.top {border-top:solid 1px #000;}
  table.mini td.left {border-left:solid 1px #000;}
  .note{font-size:0.8em;}
  #log {font-size:0.8em;font-family:sans-serif;margin:5px;padding:0.5em;border:1px dashed;background:#efefff;}
  div.guess{margin-left:2em;}
  p {margin:0.5em;}
  p.note{margin:0.2em;}
  hr{width:90%;height:1px;}
  </style>
  <meta name="robots" content="noindex,follow">
  </head>
  <body>
  <p class="note">version4</p>
  <h1>Sudoku</h1>
  <p><?php echo $message; ?></p>

  <form action="<?php echo htmlSafe($_SERVER['PHP_SELF'])?>" method="POST">
  <?php
  echo $table ;
  if($message!=$done){ ?>
  <br>Click the "SEND" button to send your puzzle to the sudoku solver.
  <input type="submit" name="submit" value="　SEND　">
  <?php } ?>
  <br>Click the "NEW" button to get a new blank grid.
  <input type="submit" name="submit_new" value="　NEW　">
  </form>



  <?php if ($log){ ?>
  <p class="note">
  <em>process log:</em>
  </p>
  <div id="log">

  <?php echo $log; ?>
  </div>
  <?php } ?>
  </body></html>
