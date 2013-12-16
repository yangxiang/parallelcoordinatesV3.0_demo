<?php
ini_set("memory_limit", "128M");
ini_set("display_errors", "On");
error_reporting(E_ALL);

if (strstr($_GET['ds_name'], '..') !== FALSE)
{
  print("Error: ds_name contains invalid characters");
  die;
}

function map_col_num($literal_col, $cols)
{
  if ($cols === null) return $literal_col;
  for ($i=0; $i<count($cols); ++$i)
  {
    if ($cols[$i] == $literal_col) return $i + 1;
  }
  /*
  $cols_arr = array_keys($cols);
  sort($cols_arr);
  for ($i=0; $i<count($cols_arr); ++$i)
  {
    if ($cols_arr[$i] == $literal_col)
    {
      return $i + 1;
    }
  }
  */
  return null;
}

$ds_name = $_GET['ds_name'];
$cols = null;
if (isset($_GET['cols']))
{
  $cols = array();
  $cols = explode(',', $_GET['cols']);
  /*
  for ($i=0; $i<count($cols_a); ++$i)
  {
    $cols[$cols_a[$i]] = 1;
  }
  */
}

require('array_to_json.php');

$ds_fname = "data/{$_GET['ds_name']}.arff";
/*
if (($stat_arr = stat($ds_fname)) === FALSE)
{
  print array_to_json(array('error' => "Error accessing dataset file $ds_fname: " . posix_get_last_error()));
  print "\n";
  die;
}
*/

unset($php_errormsg);
ini_set('track_errors', 1);
if (($ds_h = @fopen($ds_fname, "rb")) === FALSE)
{
  //print("Error opening dataset file");
  print array_to_json(array('error' => "Error opening dataset file $ds_fname: $php_errormsg"));
  print "\n";
  die;
}
ini_set('track_errors', 0);

$in_data_row = -1;
$col_d = array();
while ($line = fgets($ds_h))
{
  $line = trim($line);
  if ($in_data_row > 0)
  {
    $arr = explode(',', $line);

    if ($in_data_row === 1 && $cols !== null)
    {
      foreach ($cols as $v)
      {
        if ($v >= count($arr))
        {
          print("col value $v larger than dataset cols " . count($arr));
          die;
        }
      }
    }

    $clust = $arr[count($arr) - 1];
    for ($col_num=1;$col_num<=count($arr) - 2; ++$col_num)
    {
      //print "col $col_num " . map_col_num($col_num, $cols) . "\n";
      if (($v_col_num = map_col_num($col_num, $cols)) !== null)
      //if ($cols === null || isset($cols[$col_num]))
      {
        //$v_col_num = map_col_num($col_num, $cols);
        if (!isset($col_d[$v_col_num]))
        {
          $col_d[$v_col_num] = array();
        }
	if (!isset($col_d[$v_col_num][$clust]))
	{
          $col_d[$v_col_num][$clust] = array();
	}
	$col_d[$v_col_num][$clust][] = (double)$arr[$col_num];
      }
    }
    ++$in_data_row;
  }
  if ($line === '@data')
  {
    $in_data_row = 1;
  }
}

fclose($ds_h);

//print json_encode($col_d);

print array_to_json($col_d);

