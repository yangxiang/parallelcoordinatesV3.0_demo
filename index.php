<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>Parallel Coordinates Visualization</title>
<link rel="stylesheet" type="text/css" href="css/ui-lightness/jquery-ui-1.7.2.custom.css"/>
<link rel="stylesheet" type="text/css" href="parallelvisual.css" />
<script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript">
//var colors = ['aqua', 'fuchsia', 'red', 'green', 'blue', 'silver', 'teal', 'olive', 'purple', 'navy', 'maroon', 'yellow', 'lime' ];
var colors = ['aqua', 'rgb(202,170,196)', 'rgb(102,114,154)', 'rgb(22,67,22)', 'black', 'lightgreen', 'yellow', 'red', 'blue', 'purple', 'maroon' ];
//var colors = ['aqua', 'rgb(202,170,196)', 'cyan', 'rgb(22,67,22)', 'black', 'lightgreen', 'yellow', 'red', 'blue', 'purple', 'black' ];

var spc_top = 20;
var graphic_height = 440;

var alpha = 0.25;
var beta = 0.25;
var gamma = 0.50;
var data_num_clust=0;//total number of clusters in the data

var crossing_type;

//Options Begin 
var weighted_repelling = true;
var use_Bezier_Curve=false;
//Options End

function get_coordline_x(col_num)
{
  return spc_top + Math.round(((col_num - 1) / (data_num_cols - 1)) * (canvas_width - (spc_top * 2)));
}

function canvas_clear()
{
  var canvas = $('#vis_canvas')[0];
  var ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);
}

function canvas_init()
{
  canvas_clear();

	if ($('#all_crossings, #inter_crossings, #inter_crossings_2, #intra_crossings, #intra_crossings_2').filter(':checked').attr('id') === 'all_crossings')
		crossing_type=0;//all crossings
	else if ($('#all_crossings, #inter_crossings, #inter_crossings_2, #intra_crossings, #intra_crossings_2').filter(':checked').attr('id') === 'inter_crossings')
		crossing_type=1;//inter-cluster crossings
	else if ($('#all_crossings, #inter_crossings, #inter_crossings_2, #intra_crossings, #intra_crossings_2').filter(':checked').attr('id') === 'intra_crossings')
		crossing_type=2;//intra-cluster crossings
	else if ($('#all_crossings, #inter_crossings, #inter_crossings_2, #intra_crossings, #intra_crossings_2').filter(':checked').attr('id') === 'inter_crossings_2')
		crossing_type=3;//inter-cluster crossings on two focused clusters
	else if ($('#all_crossings, #inter_crossings, #inter_crossings_2, #intra_crossings, #intra_crossings_2').filter(':checked').attr('id') === 'intra_crossings_2')
		crossing_type=4;//intra-cluster crossings on two focused clusters
	else
		crossing_type=-1;//internal error    
		
  var canvas = $('#vis_canvas')[0];
  var ctx = canvas.getContext('2d');

  ctx.lineWidth = 1;
  ctx.font = "10pt sans-serif";
  ctx.textAlign = 'center';
  for (var c_num=1; c_num<=data_num_cols; ++c_num)
  {
    var cline_x = get_coordline_x(c_num);
    ctx.strokeStyle = 'black';
    ctx.beginPath();
    ctx.moveTo(cline_x, spc_top);
    ctx.lineTo(cline_x, spc_top + graphic_height);
    ctx.stroke();

    if ($('#draw_mid_lines').attr('checked'))
    {
      ctx.strokeStyle = 'green';
      ctx.fillStyle = 'green';
      if (c_num < data_num_cols)
      {
        var cline_next_x = get_coordline_x(c_num + 1);
        var mid_line_x = Math.round((cline_x + cline_next_x) / 2);
        ctx.beginPath();
        ctx.moveTo(mid_line_x, spc_top);
        ctx.lineTo(mid_line_x, spc_top + graphic_height);
        ctx.stroke();
  
        ctx.beginPath();
        ctx.moveTo(mid_line_x - 10, spc_top);
        ctx.lineTo(mid_line_x + 10, spc_top);
        ctx.stroke();
  
        ctx.textBaseline = "bottom";
        if (ctx.fillText) ctx.fillText((ds_dim[c_num].min + ds_dim[c_num+1].min).toFixed(4) / 2, mid_line_x, spc_top);
  
        ctx.beginPath();
        ctx.moveTo(mid_line_x - 10, spc_top + graphic_height);
        ctx.lineTo(mid_line_x + 10, spc_top + graphic_height);
        ctx.stroke();
  
        ctx.textBaseline = "top";
        if (ctx.fillText) ctx.fillText((ds_dim[c_num].max + ds_dim[c_num+1].max).toFixed(4) / 2, mid_line_x, spc_top + graphic_height);
      }
    }
	
	if ($('#Bezier_Curve').attr('checked'))
	{
		use_Bezier_Curve=true;
	}
	else
	{
		use_Bezier_Curve=false;
	}
	
    ctx.fillStyle = 'black';
    ctx.strokeStyle = "black";
    if (ctx.fillText) 
    {
      ctx.fillText("col" + (col_reorder ? col_reorder[c_num - 1] : c_num), cline_x, 483); 

      ctx.textBaseline = "bottom";
      ctx.fillText(ds_dim[c_num].min, cline_x, spc_top),
      ctx.beginPath();
      ctx.moveTo(cline_x - 10, spc_top);
      ctx.lineTo(cline_x + 10, spc_top);
      ctx.stroke();

      ctx.textBaseline = "top";
      ctx.fillText(ds_dim[c_num].max, cline_x, spc_top + graphic_height);
      ctx.beginPath();
      ctx.moveTo(cline_x - 10, spc_top + graphic_height);
      ctx.lineTo(cline_x + 10, spc_top + graphic_height);
      ctx.stroke();
    }
  }
  ctx.stroke();
}

var ds_dim = null;
var orig_ds_dim = null;
var u = null;
var data_num_cols = null;
var data_num_clust = null;

function render()
{  
  canvas_init();

  var ctx = $('#vis_canvas')[0].getContext('2d');
  ctx.textBaseline = "middle";
  var c_w = canvas_width;

  //ctx.globalAlpha = 0.5;
  for (var col=1; col<data_num_cols; ++col)
  {
    var mid_minval = (ds_dim[col].min + ds_dim[col+1].min) / 2;
    var mid_maxval = (ds_dim[col].max + ds_dim[col+1].max) / 2;
    var row1_x = get_coordline_x(col);
    var row2_x = get_coordline_x(col+ 1);
    var midline_x = (row1_x + row2_x) / 2.0;

    var ck = [];
    for (var cluster in u[col])
    {
      ck.push(cluster);
    }
    ck.sort();
    for (var c_num=1; c_num<=ck.length; ++c_num)
    {
      var cluster = ck[c_num - 1];
      ctx.beginPath();
      ctx.strokeStyle = colors[c_num % colors.length];
      
      var row_len = u[col][cluster].length;
      for (var row=0; row<row_len; ++row)
      {
	var z_i = z[col][cluster][row];

	var yval = spc_top + parseInt(((u[col][cluster][row] - ds_dim[col].min) / (ds_dim[col].max - ds_dim[col].min)) * graphic_height);
	ctx.moveTo(row1_x, yval);

	var midval = spc_top + parseInt(((z_i - mid_minval) / (mid_maxval - mid_minval)) * graphic_height);
	if (!use_Bezier_Curve)
		ctx.lineTo(midline_x, midval);

	var yval2 = spc_top + parseInt(((u[col+1][cluster][row] - ds_dim[col+1].min) / (ds_dim[col+1].max - ds_dim[col+1].min)) * graphic_height);
	if (!use_Bezier_Curve)
		ctx.lineTo(row2_x, yval2);
	
	if (use_Bezier_Curve)
		ctx.bezierCurveTo(midline_x, midval, midline_x, midval,row2_x, yval2);

	//console.log([row1_x, yval, row2_x, yval2]);
      }
      ctx.stroke();
    }
  }
  //ctx.globalAlpha = 1.0;
}

var data_cols = null; //[13, 1, 10, 5, 3]; //null; //[ 4, 5 ];
var orig_order_cost = null;
var orig_crossings = null;

function get_data()
{
  not_intialized=true;
  var ds_name = $('#ds').val();

  var data_url = 'ds_data.php?ds_name=' + ds_name;
  if (data_cols !== null) data_url += '&cols=' + data_cols.join(',');
  $.getJSON(data_url, null, 
    function(data, textStatus)
    {
      if (textStatus != "success")
      {
        alert("error: ds_data textStatus: " + textStatus);
	return false;
      }
      if (typeof(data.error) !== 'undefined')
      {
        alert(data.error);
        return false;
      }
      u = data;

      orig_u = ({});
      for (var col in u)
      {
        orig_u[col] = u[col];
      }

      var num_rows = 0;
      var clust_ct = ({});
      data_num_cols = 0;
      for (var col in u)
      {
        ++data_num_cols;
        for (var cluster in u[col])
	{
          if (!clust_ct[cluster]) clust_ct[cluster] = 0;
	  clust_ct[cluster] = Math.max(clust_ct[cluster], u[col][cluster].length);
	}
      }
      data_num_clust = 0;
      for (var cluster in clust_ct)
      {
        ++data_num_clust;
	num_rows += clust_ct[cluster];
      }

      /*
      for(var cluster in u)
      {
	++data_num_clust;
	var num_cols = 0;
	for (var col in u[cluster])
	{
	  ++num_cols;
	}
        num_rows += u[cluster][1].length;
	clust_ct[cluster] = u[cluster][1].length;
        data_num_cols = Math.max(data_num_cols, num_cols);
      }
      */
      $('.num_rows').text(num_rows);
      $('.num_cols').text(data_num_cols);
      {
        var nc_t = '';
		var selection_list1='';
		var selection_list2='';
		var ck = [];
			for (var cluster in clust_ct)
			{
		  ck.push(cluster);
		}
		ck.sort();
		for (var i=0; i<ck.length; ++i)
		{
			if (nc_t !== '') nc_t += ', ';
			nc_t += '<span style="color: ' + colors[i + 1]  + '">' + ck[i] + '</span>: ' + clust_ct[ck[i]];
			selection_list1 += '<option value="cluster'+ i +'"> cluster'+i+' </option>';
			selection_list2 += '<option value="cluster'+ i +'"> cluster'+i+' </option>';
		}
      }
      $('.num_clusters').html('<span style="color: green">' + data_num_clust + '</span> { ' + nc_t + ' }');
	  $('.cluster_selections1').html('<select id="fc1"  onchange=" data_cols = null; not_intialized=true; reorder_then_draw(); ">'+selection_list1+'</select>');	  
	  $('.cluster_selections2').html('<select id="fc2"  onchange=" data_cols = null; not_intialized=true; reorder_then_draw(); ">'+selection_list2+'</select>');
      //console.log(col_reorder);
      //console.log(data_cols);

      // set default user specified order
      {
        var user_dim_arr = [];
        for (var i=1; i<=data_num_cols; ++i)
        {
          user_dim_arr.push(i);
        }
        var user_dim_txt = user_dim_arr.join(',');
        $('#user_specified_order').val(user_dim_txt).css('width', (user_dim_txt.length * 0.9) + 'ch');
      }

      ds_dim = calc_ds_dim();
      orig_ds_dim = ({});
      for (var col in ds_dim)
      {
        orig_ds_dim[col] = ds_dim[col];
      }

      orig_crossings = calculate_crossings(crossing_type);
      orig_order_cost = order_cost(orig_crossings);

      var ds_name = $('#ds').val();
      var ds_prefix = ds_name.split('.')[0];
      var proc_url = 'data/' + ds_name + '.arff';
      var dl_url = proc_url;
      if (ds_prefix === 'forestfires')
      {
        dl_url = 'http://archive.ics.uci.edu/ml/datasets/Forest+Fires';
      } else if (ds_prefix === 'eighthr') {
        dl_url = 'http://archive.ics.uci.edu/ml/datasets/Ozone+Level+Detection';
      } else if (ds_prefix === 'parkinsons') {
        dl_url = 'http://archive.ics.uci.edu/ml/datasets/Parkinsons';
      } else if (ds_prefix === 'pima-indians-diabetes') {
        dl_url = 'http://archive.ics.uci.edu/ml/datasets/Pima+Indians+Diabetes';
      } else if (ds_prefix === 'water-treatment') {
        dl_url = 'http://archive.ics.uci.edu/ml/datasets/Water+Treatment+Plant';
      } else if (ds_prefix === 'wdbc') {
        dl_url = 'http://archive.ics.uci.edu/ml/datasets/Breast+Cancer+Wisconsin+%28Diagnostic%29';
      } else if (ds_prefix === 'wine') {
        dl_url = 'http://archive.ics.uci.edu/ml/datasets/Wine';
      }
      $('#source_link').attr('href', dl_url).text(dl_url);
    
      $('#processed_link').attr('href', proc_url).text(proc_url);

    }
  );
}

function calc_ds_dim()
{
  var dim = ({});

  for (var col in u)
  {
    for (var cluster in u[col])
    {
      if (typeof(dim[col]) === "undefined")
      {
        dim[col] = ({ min: Number.POSITIVE_INFINITY, max: Number.NEGATIVE_INFINITY});
      }
      for (var j=0; j<u[col][cluster].length; ++j)
      {
        dim[col].min = Math.min(dim[col].min, u[col][cluster][j]);
        dim[col].max = Math.max(dim[col].max, u[col][cluster][j]);
      }
    }
  }

  /*
  for (var col=1; col<=data_num_cols; ++col)
    console.log('col ' + col + ': ' + dim[col].min + ', ' + dim[col].max);
  */

  return dim;
}

function get_centers()
{
  var ctx = $('#vis_canvas')[0].getContext('2d');
  var c = ({});
  for (var col=1; col<data_num_cols; ++col)
  {
    if (typeof(ds_dim[col]) === 'undefined')
    {
      alert("get_centers error: ds_dim[" + col + "] is undefined");
    }
    var mid_minval = (ds_dim[col].min + ds_dim[col+1].min) / 2;
    var mid_maxval = (ds_dim[col].max + ds_dim[col+1].max) / 2;
    c[col] = ({});
    for (var cluster in u[col])
    {
      var sum_mid_c = 0.0;
      var num_mid_c = 0;
      for (var row in u[col][cluster])
      {
        var left_sc = (u[col][cluster][row] - ds_dim[col].min) / (ds_dim[col].max - ds_dim[col].min);
	var right_sc = (u[col+1][cluster][row] - ds_dim[col+1].min) / (ds_dim[col+1].max - ds_dim[col+1].min);
        var mid_c = (left_sc + right_sc) / 2;
	var mid_c_scaled = mid_minval + mid_c * (mid_maxval - mid_minval);
	sum_mid_c += mid_c_scaled;
	++num_mid_c;
      }
      var avg_mid_c = sum_mid_c / num_mid_c;
      c[col][cluster] = avg_mid_c;
    }
  }
  return c;
}

var z = null;

function init_z()
{
  z = ({});
  for (var col=1; col<data_num_cols; ++col)
  {
    z[col] = ({});
    for (var cluster in u[col])
    {
      var mid_minval = (ds_dim[col].min + ds_dim[col+1].min) / 2;
      var mid_maxval = (ds_dim[col].max + ds_dim[col+1].max) / 2;
      z[col][cluster] = ({});
      for (var row in u[col][cluster])
      {
        x_norm = (u[col][cluster][row] - ds_dim[col].min) / (ds_dim[col].max - ds_dim[col].min);
		y_norm = (u[col+1][cluster][row] - ds_dim[col+1].min) / (ds_dim[col+1].max - ds_dim[col+1].min);
		var xy_midpt_norm = ((x_norm + y_norm) / 2);
		var xy_midpt = mid_minval + xy_midpt_norm * (mid_maxval - mid_minval);
		z[col][cluster][row] = xy_midpt;
      }
    }
  }
}

function clust_order_func(a,b)
{
  if (a[0] < b[0]) return -1;
  if (a[0] > b[0]) return 1;
  return 0;
}

var c_prime = null;
var not_intialized=true;
var clust_ord_map = ({});
var map_ord_clust = ({});
var uppermost_c_prime = ({}); //A uppermost pseudo cluster for balance
var lowermost_c_prime = ({}); //A lowermost pseudo cluster for balance
var uppermost_c_size = ({}); //The size of the uppermost pseudo cluster for balance
var lowermost_c_size = ({}); //The size of the lowermost pseudo cluster for balance

/*
var energy = null;
var prev_energy = null;
*/

function compute_1step_allcols()
{
	for (var col=1; col<data_num_cols; ++col)
			compute_1step(col);
	
}

function compute_1step(col)
{
//  alpha=0.33;
//  beta=0.33;
//  gamma=0.33;


//  z = ({});
//for (var col=1; col<data_num_cols; ++col)
//{
    z[col] = ({});
    for (var cluster in u[col])
    {
      var low_bound = (ds_dim[col].min + ds_dim[col+1].min) / 2.0;
      var up_bound = (ds_dim[col].max + ds_dim[col+1].max) / 2.0;
      var mid_minval = (ds_dim[col].min + ds_dim[col+1].min) / 2;
      var mid_maxval = (ds_dim[col].max + ds_dim[col+1].max) / 2;

      var clust_cprime_order = clust_ord_map[col][cluster];
 //     console.log([col, cluster, clust_cprime_order]);

      var next_clust = map_ord_clust[col][clust_cprime_order+1];
      var next_clust_cprime;
	  if (typeof(next_clust)!="undefined")
		next_clust_cprime = c_prime[col][next_clust];
	  else
		next_clust_cprime=lowermost_c_prime[col];

      var prev_clust = map_ord_clust[col][clust_cprime_order-1];
      var prev_clust_cprime;
	  if (typeof(prev_clust)!="undefined")
		prev_clust_cprime= c_prime[col][prev_clust];
	  else
		prev_clust_cprime=uppermost_c_prime[col];
	 
	  var prev_prev_clust_idx = clust_cprime_order-2;	 
      var next_next_clust_idx = clust_cprime_order+2;
//      console.log([clust_cprime_order, prev_clust, prev_clust_cprime, uppermost_c_prime[col]]);

      // note: duplicated below
      var next_clust_size=0;
      if (typeof(u[col][next_clust]) != "undefined")
      {
        next_clust_size = u[col][next_clust].length;
      }
	  else
	  {
		next_clust_size = lowermost_c_size[col];
	  }

      var prev_clust_size=0;

      if (typeof(u[col][prev_clust]) != "undefined")
      {
        prev_clust_size = u[col][prev_clust].length;
      }
	  else
	  {
		prev_clust_size = uppermost_c_size[col];
	  }
	  
      z[col][cluster] = ({});
      for (var row in u[col][cluster])
      {
        var x_i = u[col][cluster][row];
        var x_norm = (x_i - ds_dim[col].min) / (ds_dim[col].max - ds_dim[col].min);

        var y_i = u[col+1][cluster][row];
        var y_norm = (y_i - ds_dim[col+1].min) / (ds_dim[col+1].max - ds_dim[col+1].min);

        var xy_midpt_norm = ((x_norm + y_norm) / 2);

        var xy_midpt = mid_minval + xy_midpt_norm * (mid_maxval - mid_minval);

        var z_i_unnorm = alpha * xy_midpt;
		z_i_unnorm += beta * c_prime[col][cluster]; 
		
		//console.log("alpha * xy_midpt: "+alpha * xy_midpt);
		//console.log("beta * c_prime[col][cluster]: "+beta * c_prime[col][cluster]);
	
		var weight_prev=0.0;
		var weight_next=0.0;
		var gamma_z=0;
		
		//begin handling boundary 
//		if ((typeof(u[col][prev_clust]) != "undefined") || (typeof(u[col][next_clust]) != "undefined"))
		{
			gamma_z=gamma;
		}
		//end handling boundary
		
		if (weighted_repelling)
		{
			if ((prev_clust_size!= 0) || (next_clust_size != 0))
			{
				weight_prev=(gamma_z * next_clust_size) / (prev_clust_size + next_clust_size);
				weight_next=(gamma_z * prev_clust_size) / (prev_clust_size + next_clust_size);
			}
		}
		else
		{
			weight_prev=gamma_z;
			weight_next=gamma_z;
		}
		//console.log("weight_prev: "+weight_prev);
		//console.log("weight_next: "+weight_next);
		
//		if (typeof(u[col][prev_clust]) != "undefined")
		{
			z_i_unnorm +=weight_prev*prev_clust_cprime;
		}
//		if (typeof(u[col][next_clust]) != "undefined")
		{
			z_i_unnorm += weight_next*next_clust_cprime;
		}

		var z_i_denom = alpha + beta;
		//console.log('weight_prev:'+weight_prev+',weight_next:'+weight_next);
//		if (typeof(u[col][prev_clust]) != "undefined")
			z_i_denom += weight_prev;
//		if (typeof(u[col][next_clust]) != "undefined")	
			z_i_denom += weight_next;

		var z_i = z_i_unnorm / z_i_denom;
		

		//console.log('z_i:'+z_i+', z_i_unnorm:'+z_i_unnorm+', z_i_denom:'+z_i_denom);

		if (z_i < low_bound) z_i = low_bound;
		if (z_i > up_bound) z_i = up_bound;



		z[col][cluster][row] = z_i;

      }
    }
//}





  
  
  
  
  
	var new_c_prime = ({});
//for (var col=1; col<data_num_cols; ++col)
//{
    //for (var cluster in c_prime[col])
	//{
	//	console.log('cluster:'+cluster+'; old_cp:'+c_prime[col][cluster]);
	//}
    var low_bound = (ds_dim[col].min + ds_dim[col+1].min) / 2.0;
    var up_bound = (ds_dim[col].max + ds_dim[col+1].max) / 2.0;
    for (var cluster in z[col])
    {

      // note: duplicated above
      var clust_cprime_order = clust_ord_map[col][cluster];
      var prev_clust = map_ord_clust[col][clust_cprime_order-1];
      var next_clust = map_ord_clust[col][clust_cprime_order+1];

      var prev_clust_size;
      var sum_zi_prev_cluster = 0.0;
      if (typeof(prev_clust) != "undefined")
      {
        prev_clust_size = u[col][prev_clust].length;
      }
      else
      {
        prev_clust_size = uppermost_c_size[col];
      }
      var next_clust_size;
      var sum_zi_next_cluster = 0.0;
      if (typeof(next_clust) !== "undefined")
      {
        next_clust_size = u[col][next_clust].length;
      }
      else
      {
        next_clust_size = lowermost_c_size[col];
      }

	  var gamma_c=gamma;
	  
	  //begin heuristic for handling boundary (optional)
//	  if (SA_Opt==true)
//	  {
//		  if ((typeof(u[col][prev_clust]) == "undefined")||(typeof(u[col][next_clust]) == "undefined"))
//		  {
//			gamma_c=0;
//		  }
//	  }
	  //end heuristic for handling boundary

	  
      //console.log(clust_cprime_order);
      
      prev_prev_clust_idx = clust_cprime_order-2;
	  var prev_prev_clust_size=0;
      if (prev_prev_clust_idx < 0)
      {
		prev_prev_clust_idx+=2; 
	  }
		var prev_prev_clust = map_ord_clust[col][prev_prev_clust_idx];
		prev_prev_clust_size = u[col][prev_prev_clust].length;
      
	  
//	  if (prev_prev_clust_idx == -1)
//		prev_prev_clust_size = uppermost_c_size[col];


		
      next_next_clust_idx = clust_cprime_order+2;
	  var next_next_clust_size =0;
	  //console.log('__count__'+Object.keys(map_ord_clust[col]).length);
      if (next_next_clust_idx >= Object.keys(map_ord_clust[col]).length)
      {
		next_next_clust_idx -= 2;
	  }
		var next_next_clust = map_ord_clust[col][next_next_clust_idx];
		next_next_clust_size = u[col][next_next_clust].length;
      
	  
//	  if (next_next_clust_idx == map_ord_clust[col].__count__)
//		next_next_clust_size = lowermost_c_size[col];

      var sum_zi_in_cluster = 0.0;
      var clust_size=0;
      for (var zc in z[col])
      {
        for (var row in z[col][zc])
        {
          if (zc == cluster)
          {
            sum_zi_in_cluster += z[col][zc][row];
		  	++clust_size;
          }
		  if (typeof(prev_clust) != "undefined" && zc == prev_clust)
		  {
				sum_zi_prev_cluster += z[col][zc][row];
		  }
		  if (typeof(next_clust) != "undefined" && zc == next_clust)
		  {
				sum_zi_next_cluster += z[col][zc][row];
		  }
        }
      }

	  if (typeof(prev_clust) == "undefined")
		sum_zi_prev_cluster = uppermost_c_prime[col]*uppermost_c_size[col];
	  
	  if (typeof(next_clust) == "undefined")
	    sum_zi_next_cluster = lowermost_c_prime[col]*lowermost_c_size[col];
  
      var new_cp_unscaled = beta * sum_zi_in_cluster;
	  
	  //console.log("beta * sum_zi_in_cluster: "+beta * sum_zi_in_cluster);
      
      var p_prime = prev_prev_clust_size / (prev_prev_clust_size + clust_size);
	
	  var p_double_prime = next_next_clust_size / (clust_size + next_next_clust_size);
		
      if (weighted_repelling)
      {
        new_cp_unscaled += gamma_c * p_prime * sum_zi_prev_cluster;
		new_cp_unscaled += gamma_c * p_double_prime * sum_zi_next_cluster;
      } 
	  else 
	  {
        new_cp_unscaled += gamma_c * sum_zi_prev_cluster;
        new_cp_unscaled += gamma_c * sum_zi_next_cluster;
      }
	  
	  //console.log("gamma_c * p_prime * sum_zi_prev_cluster: "+gamma_c * p_prime * sum_zi_prev_cluster);
	  //console.log("gamma_c * p_double_prime * sum_zi_next_cluster"+gamma_c * p_double_prime * sum_zi_next_cluster);
	  
      var scale_denom = beta * clust_size;
      if (weighted_repelling)
      {
        scale_denom += gamma_c * p_prime * prev_clust_size;
		scale_denom += gamma_c * p_double_prime * next_clust_size;
      } 
	  else 
	  {
        scale_denom += gamma_c * prev_clust_size 
		scale_denom += gamma_c * next_clust_size;
      }
	  
	  //console.log("beta * clust_size: "+beta * clust_size);
	  //console.log("gamma_c * p_prime * prev_clust_size: "+gamma_c * p_prime * prev_clust_size);
	  //console.log("gamma_c * p_double_prime * next_clust_size: "+gamma_c * p_double_prime * next_clust_size);
	  
      var new_cp;
      if (scale_denom != 0)
      {
        new_cp = new_cp_unscaled / scale_denom;
      } 
	  else 
	  {
        new_cp = 0;
      }

	  
	  if (typeof(prev_clust) !== "undefined")
      {
		if (new_cp < c_prime[col][prev_clust])
			new_cp=c_prime[col][prev_clust];
	  }
	  
	  if (typeof(next_clust) !== "undefined")
	  {
		if (new_cp > c_prime[col][next_clust])
			new_cp=c_prime[col][next_clust];
	  }

      if (new_cp < low_bound)
      {
        //console.log('warning: setting c_prime [' + cluster + '][' + col + '] ' + new_cp + ' to lower bound ' + low_bound);
        new_cp = low_bound;
      }

      if (new_cp > up_bound)
      {
        //console.log('warning: setting c_prime [' + cluster + '][' + col + '] ' + new_cp + ' to upper bound ' + up_bound);
        new_cp = up_bound;
      }
	  
      //console.log('new_c_prime[' + col + '][' + cluster + ']: ' + new_cp);
	  //console.log('cluster:'+cluster+'; new_cp:'+new_cp);

      if (!new_c_prime[col]) new_c_prime[col] = ({});

      new_c_prime[col][cluster] = new_cp;
    }
//}

  c_prime[col] = new_c_prime[col];
  

}

function e_prime(col)
{


  var sum_e_elastic = 0.0;
  var sum_e_attraction = 0.0;
  var sum_e_repelling = 0.0;

//for (var col=1; col<data_num_cols; ++col)
//{
    var mid_minval = (ds_dim[col].min + ds_dim[col+1].min) / 2;
    var mid_maxval = (ds_dim[col].max + ds_dim[col+1].max) / 2;
//}

 
	  
	  
    for (var cluster in u[col])
    {
	
      var clust_cprime_order = clust_ord_map[col][cluster];

      var next_clust = map_ord_clust[col][clust_cprime_order+1];
      var prev_clust = map_ord_clust[col][clust_cprime_order-1];

      var prev_clust_size=0;
      if (typeof(prev_clust) != "undefined")
      {
        prev_clust_size = u[col][prev_clust].length;
      }
	  else
	  {
	    prev_clust_size = uppermost_c_size[col];
	  }
      
	  var next_clust_size=0;
      if (typeof(next_clust) != "undefined")
      {
        next_clust_size = u[col][next_clust].length;
      }
	  else
	  {
		next_clust_size = lowermost_c_size[col];
	  }
  

	  var w_prime=1;
	  var w_double_prime =1;
	  
	  if (weighted_repelling)
	  {
		w_prime = prev_clust_size / (prev_clust_size + next_clust_size);
		w_double_prime = next_clust_size / (prev_clust_size + next_clust_size);
	  }

	
      for (var row in u[col][cluster])
      {
        var x_norm = (u[col][cluster][row] - ds_dim[col].min) / (ds_dim[col].max - ds_dim[col].min);
        var y_norm = (u[col+1][cluster][row] - ds_dim[col+1].min) / (ds_dim[col+1].max - ds_dim[col+1].min);
		var xy_midpt_norm = ((x_norm + y_norm) / 2);
		var xy_midpt = mid_minval + xy_midpt_norm * (mid_maxval - mid_minval);

		var z_i = z[col][cluster][row];

	    var e_elastic = Math.pow(z_i - xy_midpt, 2);
	    sum_e_elastic += e_elastic;

		var e_attraction = Math.pow(z_i - c_prime[col][cluster], 2);
			sum_e_attraction += e_attraction;

		var e_repelling_prev;
		var e_repelling_next; 
		
		if (typeof(prev_clust) != "undefined")
			e_repelling_prev = Math.pow(z_i - c_prime[col][prev_clust], 2);
		else
			e_repelling_prev = Math.pow(z_i -uppermost_c_prime[col], 2);
			
		if ((typeof(next_clust) != "undefined"))
			e_repelling_next =  Math.pow(z_i - c_prime[col][next_clust], 2);
		else
			e_repelling_next = Math.pow(z_i - lowermost_c_prime[col], 2);
		
		
		sum_e_repelling += w_double_prime*e_repelling_prev;	
		sum_e_repelling += w_prime*e_repelling_next;		
			
      }
    }	  
	

	return alpha*sum_e_elastic + beta*sum_e_attraction + gamma*sum_e_repelling;
  
}

function order_cost(cg, trav)
{
  //console.log('order_cost', cg, trav);
  if (!cg)
  {
    alert('error: no parameter for order_cost');
    cg = calculate_crossings();
  }
  var c_sum = 0.0;
  for (var col=1; col<data_num_cols; ++col)
  {
    var cur_idx = col;
    var next_idx = col+1;
    if (trav)
    {
      cur_idx = trav[col-1];
      next_idx = trav[col];
    }
    //console.log('crossings btwn ' + cur_idx + ' and ' + next_idx + ': ' + cg[cur_idx][next_idx]);
    c_sum += cg[cur_idx][next_idx];
  }
  return c_sum;
}

function init_c_prime_and_z()
{
  c_prime = get_centers();

  /*
  {
    var icl = '';
    for (var col in c_prime)
    {
      var cls_s = [];
      for (var cluster in c_prime[col])
      {
        cls_s.push(cluster);
      }
      cls_s.sort();
      for (var i=0; i<cls_s.length; ++i)
      {
        var cluster = cls_s[i];
        if (icl !== '') icl += ', ';
        icl += '[' + col + '][' + cluster + ']: ' + c_prime[col][cluster].toFixed(4);
      }
    }
    //console.log('c: { ' + icl + ' }');
  }
  */

  init_z();
  if(not_intialized)
  {
	for (var col=1; col<data_num_cols; ++col)
	{
		clust_ord_map[col] = ({});
		map_ord_clust[col] = ({});
		
		var clust_order = [];
		for (var c2 in z[col])
		{
		/*
		var sum_zi = 0.0;
		for (var row in z[col][c2])
		{
			  sum_zi += z[col][c2][row];
		}
		var order_val = sum_zi / parseFloat(z[col][c2].length);
		*/
			var order_val = c_prime[col][c2];
			clust_order.push([order_val, c2]);
		}
		
		clust_order.sort(clust_order_func); // otherwise, lexicographic
		  //console.log(cprime_order);
		for (var i=0; i<clust_order.length; ++i)
		{
			clust_ord_map[col][clust_order[i][1]] = i;//comment by Yang Xiang: clust_order[i][1] is cluster number.
			map_ord_clust[col][i] = clust_order[i][1];
		}
		
		//option 1
		//uppermost_c_prime[col] = 2.0*(ds_dim[col].min + ds_dim[col+1].min) / 2.0-c_prime[col][clust_order[0][1]]; 
		//lowermost_c_prime[col] = 2.0*(ds_dim[col].max + ds_dim[col+1].max) / 2.0-c_prime[col][clust_order[clust_order.length-1][1]]; 
		
		uppermost_c_size[col] = u[col][clust_order[1][1]].length;
		lowermost_c_size[col] = u[col][clust_order[clust_order.length-2][1]].length;
//		console.log([lowermost_c_prime[col],uppermost_c_prime[col]]);
//		console.log([lowermost_c_size[col], uppermost_c_size[col]]);
		//option 2
		uppermost_c_prime[col] = (ds_dim[col].min + ds_dim[col+1].min) / 2.0;
		lowermost_c_prime[col] = (ds_dim[col].max + ds_dim[col+1].max) / 2.0;
		//uppermost_c_size[col] = 0; 
		//lowermost_c_size[col] = 0;	
	}
	
	not_intialized=false;
  }

}

function reorder_then_draw()
{
  orig_crossings = calculate_crossings(crossing_type);
  //console.log('crossing_type'+crossing_type);
  orig_order_cost = order_cost(orig_crossings);  
//  var cg = calculate_crossings(crossing_type);
//  find_best_order(cg);

  find_best_order(orig_crossings);

  draw();

}

function draw()
{

  $('.convergence_time').text('');

  //calc_ds_dim();
  //var cg = calculate_crossings();
  //find_best_order(cg);

  init_c_prime_and_z();

  render();
}

function set_slider(symbol, value)
{
  if (symbol === "alpha")
  {
    alpha = value;
  } else if (symbol === "beta") {
    beta = value;
  } else if (symbol === "gamma") {
    gamma = value;
  }
  $('#slider_' + symbol).slider('value',value.toFixed(2));
  $('.val_' + symbol).text(value.toFixed(2));
}

function uniq(arr)
{
  var new_arr = [];
  if (arr.length > 0)
  {
    new_arr.push(arr[0]);
  }
  for (var i=1; i<arr.length; ++i)
  {
    if (arr[i] instanceof Array && arr[i-1] instanceof Array)
    {
      var arr_i_len = arr[i].length;
      if (arr_i_len === arr[i-1].length)
      {
	var all_eq = true;
        for (var j=0; j<arr_i_len; ++j)
	{
          if (arr[i][j] !== arr[i-1][j])
	  {
	    all_eq = false;
            break;
	  }
	}
        if (!all_eq) new_arr.push(arr[i]);
      }
    }
    else if (arr[i] !== arr[i-1])
    {
      new_arr.push(arr[i]);
    }
  }
  return new_arr;
}

function pairs_order_func(a, b)// Newly adjusted for calculate inter-intra cluster crossings
{
  if (a[0][0] < b[0][0]) return -1;
  if (a[0][0] > b[0][0]) return 1;
  if (a[1][0] < b[1][0]) return -1;
  if (a[1][0] > b[1][0]) return 1;
  return 0;
}

function col_crossings(col1, col2, _crossing_type)
{
  var pairs = [];
  for (var i=0; i<col1.length; ++i)
  {
    pairs.push([col1[i], col2[i]]);
  }
  pairs.sort(pairs_order_func); // non-lexicographic
  pairs = uniq(pairs);
  //console.log(pairs);
  
  var num_crossings = 0;
  var s = [];
  var focus_cluster1 = $('#fc1').val();
  var focus_cluster2 = $('#fc2').val();
  for (var i=0; i<pairs.length; ++i)
  {
    //console.log('considering item ' + pairs[i][1]);
    //console.log(s);
    for (var j=0; j<s.length; ++j)
    {
      if (s[j][0] > pairs[i][1][0]) 
      {
		if (_crossing_type==0)
		{
			++num_crossings;
			//console.log('all crossing');
			continue;
		}
		if ((_crossing_type==1)&& (s[j][1]!=pairs[i][1][1]))
		{
			
			//if (((s[j][1]=='cluster1')&&(pairs[i][1][1]=='cluster4'))||((s[j][1]=='cluster4')&&(pairs[i][1][1]=='cluster1')))
				++num_crossings;
			//console.log('inter crossing');
			continue;			
		}
		if ((_crossing_type==2)&& (s[j][1]==pairs[i][1][1]))
		{
			//if ((s[j][1]=='cluster1')||(s[j][1]=='cluster4'))
			++num_crossings;
			//console.log('intra crossing');
			continue;			
		}
		if (_crossing_type==3)
		{
			if (((s[j][1]==focus_cluster1)&&(pairs[i][1][1]==focus_cluster2))||((s[j][1]==focus_cluster2)&&(pairs[i][1][1]==focus_cluster1)))
				++num_crossings;		
		}
		if (_crossing_type==4)
		{
			if ((s[j][1]==focus_cluster1 && (pairs[i][1][1]==focus_cluster1))||(s[j][1]==focus_cluster2) && (pairs[i][1][1]==focus_cluster2))
			++num_crossings;		
		}
			
      }
    }
    s.push(pairs[i][1]);
  }

  return num_crossings;
}

function calculate_crossings(_crossing_type)
{
  // accumulate edges
  var rows = ({});
  for (var col in orig_u)
  {
    rows[col] = [];
    for (var cluster in orig_u[col])
    {
      for (var row=0; row<orig_u[col][cluster].length; ++row)
      {
        var x_norm = (orig_u[col][cluster][row] - ds_dim[col].min) / (ds_dim[col].max - ds_dim[col].min);
        rows[col].push([x_norm, cluster]);// updated for calculate inter-intra cluster crossings
      }
    }
  }

  // get num pairwise crossings between columns
  var cc = ({}); 
  for (var col1=1; col1<=data_num_cols; ++col1)
  {
    for (var col2=col1+1; col2<=data_num_cols; ++col2)
    {
      var num_crossings = col_crossings(rows[col1], rows[col2], _crossing_type);
      if (typeof(cc[col1]) === "undefined") cc[col1] = ({});
      cc[col1][col2] = num_crossings;
      //console.log('num crossings between ' + col1 + ' and ' + col2 + ': ' + num_crossings);
    }
  }
  //console.log(cc);

  // build fully connected graph from pairwise crossings
  var cg = ({});
  for (var col1=1; col1<=data_num_cols; ++col1)
  {
    cg[col1] = ({});
    for (var col2=1; col2<=data_num_cols; ++col2)
    {
      if (col1 !== col2)
      {
        cg[col1][col2] = cc[Math.min(col1, col2)][Math.max(col1,col2)];
      }
    }
  }
  return cg;
}

function mst_prim(cc, root)
{ 
  v_new = ({ });
  v_new[root] = 1;

  //console.log([v_new.__count__, cc.__count__]);

  mst = ({});

  //while (v_new.__count__ < cc.__count__)
  while(Object.keys(v_new).length<Object.keys(cc).length)
  {
    var min_newe_wt = Number.POSITIVE_INFINITY;
    var min_newe = null;

    for (var st_v in v_new)
    {
      //console.log('st_v ' + st_v);
      for (end_v in cc[st_v])
      {
        if (typeof(v_new[end_v]) === "undefined")
	{
          //console.log('considering [' + st_v + ', ' + end_v + '] of weight ' + cc[st_v][end_v]);
          if (cc[st_v][end_v] < min_newe_wt)
	  {
	    min_newe_wt = cc[st_v][end_v]; 
            min_newe = [st_v, end_v];
	  }
	}
      }
    }
    v_new[min_newe[1]] = 1;

    //console.log('adding min wt edge [' + min_newe[0] + ', ' + min_newe[1] + '] of weight ' + min_newe_wt);

    if (typeof(mst[min_newe[0]]) === "undefined") mst[min_newe[0]] = ({});
    if (typeof(mst[min_newe[1]]) === "undefined") mst[min_newe[1]] = ({});
    mst[min_newe[0]][min_newe[1]] = 1;
    mst[min_newe[1]][min_newe[0]] = 1;
  }

  return mst;
}

function find_best_order(cc)
{
  //console.log(mst);

  var exhaustive = $('#exh_order_search').attr('checked');
  var is_min = ($('#minimize_crossings,#maximize_crossings').filter(':checked').attr('id') === 'minimize_crossings');
  if (!exhaustive && !is_min) 
  {
    alert('under development.');
  }  

  var auto_reorder = ($('#reorder_manual,#reorder_automatic').filter(':checked').attr('id') === 'reorder_automatic');  

  var new_order = null;
  var best_order_cost = null;
  if (auto_reorder)
  {
    var best_order_trav = null;

    if (exhaustive && !is_min)    
    {

	best_order_cost = Number.POSITIVE_INFINITY;

		  
	var VERTICES=new Array(data_num_cols);//This array should be initiallized to be all 0.
	var MAX_VERTICES=null;//This array should be initiallized to be all 0.
	var used=new Array(data_num_cols);//This array should be initiallized to be all 0.
	//weight is replaced by cc//var weight=new Array(data_num_cols)(data_num_cols);//This 2-d array (matrix) should be intialized to the actual weight of the graph.
	var weight_rank=new Array(data_num_cols*(data_num_cols-1)/2);//weight_rank records all the weight in weight matrices from large to small
	var k=0;
	for (var i=0; i<=data_num_cols-2; i++)
	{
		for (var j=i+1; j<=data_num_cols-1; j++)
		{
			weight_rank[k]=cc[i+1][j+1];
			k++;
		}
	}
	weight_rank.sort(function numerical_sort(a,b){return b-a});
//	console.log('weight_rank:'+weight_rank);
	
	var remain_opt=new Array(data_num_cols);//remain_opt[x] is the maximum possible total weights if only x+1 vertices are selected.
	remain_opt[0]=0;
	for(var i=1; i<=data_num_cols-1; i++)//initialize remain_opt array
	{
		remain_opt[i]=remain_opt[i-1]+weight_rank[i-1];
	}
//	console.log('remain_opt:'+remain_opt);
	

	var STEP= 0; //STEP starts from 0
	var MAX=Number.NEGATIVE_INFINITY; //MIN is initialized to a minimum value
	for (var i=0; i<=data_num_cols-1; i++)
	{
		VERTICES[i]=0;
	//	MIN_VERTICES[i]=0;
		used[i]=false;
	}

	var current_weight=0;
	
	while (true)
	{
//		console.log('STEP:'+STEP);
		while (true)//if the vertex has been selected, increase the vertex number unless reach an unselected vertex or out of range.
		{
			if(VERTICES[STEP] > data_num_cols-1)
				break;
			if(!used[VERTICES[STEP]])
				break;
			VERTICES[STEP]++;
		}
		if(VERTICES[STEP] > data_num_cols-1)//if all the vertices have been selected, return to previous step.
		{
			VERTICES[STEP] = 0;
			STEP--;
			if (STEP<0)//when STEP<0, we are done with the search space. Exit the big while loop.
			{
				break;
			}
			if (STEP>=1)
			{
				current_weight=current_weight-cc[VERTICES[STEP]+1][VERTICES[STEP-1]+1];	
			}
			used[VERTICES[STEP]]=false;//make the old vertex in the previous order reusable
			VERTICES[STEP]++;//try the next vertex
			continue;
				
		}
		
		if (STEP>=1)
		{
			current_weight=current_weight+cc[VERTICES[STEP]+1][VERTICES[STEP-1]+1];
//			console.log('current weight: '+current_weight+' ,Min: '+MIN);
			if (current_weight+remain_opt[data_num_cols-STEP-1]<MAX)//Cut this branch
			{
				current_weight=current_weight-cc[VERTICES[STEP]+1][VERTICES[STEP-1]+1];
				VERTICES[STEP]++;//try the next vertex. No vertex is marked as 'used' in this iteration.
				continue;//To the beginning of the largest while loop
			}
		}		
			
		if(STEP == data_num_cols-1)//When STEP equals dimension-1, it means the array VERTICES contains a complete order of vertices.
		{	
			
			if (current_weight>MAX)//if the current_weight is larger than MAX, replace MAX with current_weight and record the current order of vertices;
			{
				MAX=current_weight;
				MAX_VERTICES=VERTICES.slice(0);
//				console.log(MIN_VERTICES);
			}
			current_weight=current_weight-cc[VERTICES[STEP]+1][VERTICES[STEP-1]+1];
			VERTICES[STEP]++;//try the next vertex. No vertex is marked as 'used' in this iteration.
			continue;
		}
			
		used[VERTICES[STEP]]=true; //mark the vertex that is selected at order 'STEP'
		STEP++;//Give the above code, here STEP will not exceed dimension-1;
	}

	for (var i=0; i<=data_num_cols-1; i++)
		MAX_VERTICES[i]++;

	best_order_trav=MAX_VERTICES.slice(0);
	//console.log(best_order_trav);
	best_order_cost=MAX;
	
    }
    else if (exhaustive && is_min)
    {

	best_order_cost = Number.POSITIVE_INFINITY;

		  
	var VERTICES=new Array(data_num_cols);//This array should be initiallized to be all 0.
	var MIN_VERTICES=null;//This array should be initiallized to be all 0.
	var used=new Array(data_num_cols);//This array should be initiallized to be all 0.
	//weight is replaced by cc//var weight=new Array(data_num_cols)(data_num_cols);//This 2-d array (matrix) should be intialized to the actual weight of the graph.
	var weight_rank=new Array(data_num_cols*(data_num_cols-1)/2);//weight_rank records all the weight in weight matrices from large to small
	var k=0;
	for (var i=0; i<=data_num_cols-2; i++)
	{
		for (var j=i+1; j<=data_num_cols-1; j++)
		{
			weight_rank[k]=cc[i+1][j+1];
			k++;
		}
	}
	weight_rank.sort(function numerical_sort(a,b){return a-b});
//	console.log('weight_rank:'+weight_rank);
	
	var remain_opt=new Array(data_num_cols);//remain_opt[x] is the maximum possible total weights if only x+1 vertices are selected.
	remain_opt[0]=0;
	for(var i=1; i<=data_num_cols-1; i++)//initialize remain_opt array
	{
		remain_opt[i]=remain_opt[i-1]+weight_rank[i-1];
	}
//	console.log('remain_opt:'+remain_opt);
	

	var STEP= 0; //STEP starts from 0
	var MIN=Number.POSITIVE_INFINITY; //MIN is initialized to a minimum value
	for (var i=0; i<=data_num_cols-1; i++)
	{
		VERTICES[i]=0;
	//	MIN_VERTICES[i]=0;
		used[i]=false;
	}

	var current_weight=0;
	
	while (true)
	{
//		console.log('STEP:'+STEP);

		while (true)//if the vertex has been selected, increase the vertex number unless reach an unselected vertex or out of range.
		{
			if(VERTICES[STEP] > data_num_cols-1)
				break;
			if(!used[VERTICES[STEP]])
				break;
			VERTICES[STEP]++;
		}
		if(VERTICES[STEP] > data_num_cols-1)//if all the vertices have been selected, return to previous step.
		{
			VERTICES[STEP] = 0;
			STEP--;
			if (STEP<0)//when STEP<0, we are done with the search space. Exit the big while loop.
			{
				break;
			}
			if (STEP>=1)
			{
				current_weight=current_weight-cc[VERTICES[STEP]+1][VERTICES[STEP-1]+1];	
			}
			used[VERTICES[STEP]]=false;//make the old vertex in the previous order reusable
			VERTICES[STEP]++;//try the next vertex
			continue;
				
		}
		
		if (STEP>=1)
		{
			current_weight=current_weight+cc[VERTICES[STEP]+1][VERTICES[STEP-1]+1];
//			console.log('current weight: '+current_weight+' ,Min: '+MIN);
			if (current_weight+remain_opt[data_num_cols-STEP-1]>MIN)//Cut this branch
			{
				current_weight=current_weight-cc[VERTICES[STEP]+1][VERTICES[STEP-1]+1];
				VERTICES[STEP]++;//try the next vertex. No vertex is marked as 'used' in this iteration.
				continue;//To the beginning of the largest while loop
			}
		}		
			
		if(STEP == data_num_cols-1)//When STEP equals dimension-1, it means the array VERTICES contains a complete order of vertices.
		{	
			
			if (current_weight<MIN)//if the current_weight is larger than MAX, replace MAX with current_weight and record the current order of vertices;
			{
				MIN=current_weight;
				MIN_VERTICES=VERTICES.slice(0);
//				console.log(MIN_VERTICES);
			}
			current_weight=current_weight-cc[VERTICES[STEP]+1][VERTICES[STEP-1]+1];
			VERTICES[STEP]++;//try the next vertex. No vertex is marked as 'used' in this iteration.
			continue;
		}
			
		used[VERTICES[STEP]]=true; //mark the vertex that is selected at order 'STEP'
		STEP++;//Give the above code, here STEP will not exceed dimension-1;
	}

	for (var i=0; i<=data_num_cols-1; i++)
		MIN_VERTICES[i]++;

	best_order_trav=MIN_VERTICES.slice(0);
	//console.log(best_order_trav);
	best_order_cost=MIN;

    }
    else if (!exhaustive && is_min)
    {
    
      best_order_cost = Number.POSITIVE_INFINITY;

      //console.log('doing reorder');
      var mst_root = null;
      for (var root in cc)
      {
        mst_root = root;
      }
      var mst = mst_prim(cc, mst_root);
      var reord_ct = 0;

/*
      for (var root in cc) {
        if (!exhaustive && ++reord_ct > 6)
        {
          break;
        }
        var dfs_roots = [ root ];
        if (exhaustive)
        {
*/
          dfs_roots = [];
          for(var v in cc)
          {
            dfs_roots.push(v);
          }
/*
        }
*/
        for (var j=0; j<dfs_roots.length; ++j)
        {
          var dfs_root = dfs_roots[j];
          var v_seen = ({});
          v_seen[dfs_root] = 1;
          var dfs_trav = [dfs_root];
          //console.log('dfs root: ' + dfs_root);
          dfs(mst, dfs_root, v_seen, dfs_trav);
          //console.log(dfs_trav);
          //reorder(dfs_trav);
          var new_order_cost = order_cost(cc, dfs_trav);
          //console.log('computed order cost: ' + new_order_cost);
          if (new_order_cost < best_order_cost)
          {
            best_order_cost = new_order_cost;
            best_order_trav = dfs_trav;
          }
        }
/*
      }
*/
    
	//console.log(best_order_trav);
    }
    else
    {
      best_order_trav=new Array(data_num_cols);
      for(var i=0; i<=data_num_cols-1; i++)
      {
        best_order_trav[i]=i+1;
      }
    }
    new_order = best_order_trav;
  }
  else
  {
    new_order = get_user_order();
  }
  if (!(new_order instanceof Array))
  {
    alert("Error: could not determine order with auto_reorder " + auto_reorder);
  }
  reorder(new_order);

  if (!auto_reorder)
  {
    var cc = calculate_crossings();
    best_order_cost = order_cost(cc);
  }

  var oc_txt = '';
  if (best_order_cost !== orig_order_cost)
  {
    oc_txt += 'orig: ';
  }
  oc_txt += orig_order_cost;
  if (best_order_cost !== orig_order_cost)
  {
    oc_txt += ', new: ' + best_order_cost + ' (';
    var oc_chg = (best_order_cost - orig_order_cost) / orig_order_cost;
    if (oc_chg > 0) oc_txt += '+';
    oc_txt += (oc_chg * 100).toFixed(1) + '%)';
  }

  $('.order_cost').text(oc_txt);
}

function dfs(t, root, v_seen, dfs_trav)
{
  for (var v in t[root])
  {
    if (typeof(v_seen[v]) === "undefined")
    {
      v_seen[v] = 1;
      dfs_trav.push(v);
      //console.log('dfs: ' + v);
      dfs(t, v, v_seen, dfs_trav);
    }
  }
}

var col_reorder = null;


function reorder(new_order)
{
  for (var i=1; i<=data_num_cols; ++i)
  {
    u[i] = orig_u[new_order[i-1]];
    ds_dim[i] = orig_ds_dim[new_order[i-1]];
    //console.log('u2[' + i + '] = u[' + unorder[i] + ']');
  }
  col_reorder = new_order;

}

function fit_slider(symbol)
{
  if (symbol === "alpha")
  {
    set_slider("alpha", 1 - beta - gamma);
  } else if (symbol === "beta") {
    set_slider("beta", 1 - alpha - gamma);
  } else if (symbol === "gamma") {
    set_slider("gamma", 1 - alpha - beta);
  }
}

function update_greek()
{
  alpha = parseFloat($('#v_alpha').val());
  beta = parseFloat($('#v_beta').val());
  gamma = parseFloat($('#v_gamma').val());
  draw();
  $('#render_1_step,#render_until_converge').attr('disabled',false);
}

function set_greek()
{
  $('#v_alpha').val(alpha);
  $('#v_beta').val(beta);
  $('#v_gamma').val(gamma);
}

var canvas_width = null;

function resize_canvas()
{
  if ($('#whform input[name=imgwidth]:radio').val() == 'autoadjust')
  {
    canvas_width = parseInt($(document.body).width());
    graphic_height = 440;
  }
  else
  {
    canvas_width = parseInt($('#fixedwidth').val());
    graphic_height = parseInt($('#fixedheight').val()) - 60;
  }
  var c_elem = $('#vis_canvas');
  if (c_elem.width != canvas_width || parseInt(c_elem.height) - 60 != graphic_height)
  {
    c_elem.attr('width', canvas_width);
    c_elem.attr('height', graphic_height + 60);
    draw();
  }
}

function compute_until_converge()
{
  var upper=200;
  var energy = null;
  var prev_energy = null;
  
  for (var col=1; col<data_num_cols; ++col)
  {
	  var iter = 0;
	  while (prev_energy == null ||  Math.abs(prev_energy - energy) > 0.001*prev_energy)
	  {
		compute_1step(col);
		prev_energy = energy;
		energy = e_prime(col);
		++iter;
		if (iter>upper)
			break;
	  }
	  prev_energy=null;
	  energy=null;
   }
}

function validate_user_order()
{
  return (get_user_order() !== false);
}

function get_user_order()
{
  var uo = $('#user_specified_order').val();
  var uo_arr = uo.split(/\s*,\s*/);
  for (var i=0; i<uo_arr.length; ++i)
  {
    uo_arr[i] = parseInt(uo_arr[i]);
  }

  var dims = {};
  var dims_ct = 0;
  for (var col in u)
  {
    ++dims_ct;
    dims[dims_ct] = true;
  }

  if (uo_arr.length != dims_ct)
  {
    alert("Error: number of user specified dimensions " + uo_arr.length + " does not match number of dataset dimensions " + dims_ct);
    return false;
  }

  for (var j=0; j<uo_arr.length; ++j)
  {
    delete dims[uo_arr[j]];
  }

  for (var dim in dims)
  {
    alert("Error: dimension " + dim + " unspecified in manual order");
    return false;
  }

  return uo_arr;
}

$(function() {

  /*
  $('.slider').slider({ min: 0, max: 1, step: 0.01, 
    slide: function (event, ui)
    {
      var tid = event.target.id;
      var symbol = tid.split('_')[1];
      var val_id = 'val_' + symbol;
      //console.log(val_id + ', ' + ui.value);
      $('.' + val_id).text(ui.value);
      if (symbol === "alpha")
      {
	if (beta * 100 % 2 == 0 || gamma == 0)
	{
          set_slider("beta", (ui.value > alpha) ? beta - 0.01 : beta + 0.01);
	  fit_slider("gamma");
	} else {
	  set_slider("gamma", (ui.value > alpha) ? gamma - 0.01 : gamma + 0.01);
	  fit_slider("beta");
	}
        alpha = parseFloat(ui.value);
      } else if (symbol === "beta") {
	if (alpha * 100 % 2 == 0 || gamma == 0)
	{
          set_slider("alpha", (ui.value > beta) ? alpha - 0.01 : alpha + 0.01);
	  fit_slider("gamma");
	} else {
	  set_slider("gamma", (ui.value > beta) ? gamma - 0.01: gamma + 0.01);
	  fit_slider("alpha");
	}
        beta = parseFloat(ui.value);
      } else if (symbol === "gamma") {
	if (alpha * 100 % 2 == 0 || beta == 0)
	{
	  set_slider("alpha", (ui.value > gamma) ? alpha - 0.01 : alpha + 0.01)
	  fit_slider("beta");
	} else {
          set_slider("beta", (ui.value > gamma) ? beta - 0.01 : beta + 0.01);
	  fit_slider("alpha");
	}
        gamma = parseFloat(ui.value);
      }
    }
  });
  set_slider("alpha", alpha);
  set_slider("beta", beta);
  set_slider("gamma", gamma);
  */
  $('#v_alpha').val(alpha);
  $('#v_beta').val(beta);
  $('#v_gamma').val(gamma);
  $('#v_alpha,#v_beta,#v_gamma').keyup(function() {
    if (!$('#render_1_step,#render_until_converge').attr('disabled'))
    {
      canvas_clear();
      $('#render_1_step,#render_until_converge').attr('disabled',true);
    }
  });
  canvas_width = parseInt($(document.body).width());
  $('#vis_canvas').attr('width', canvas_width);
  $.ajaxSetup({async: false});
  get_data();

  reorder_then_draw();

  $(window).resize(resize_canvas);
  $('#exh_order_search').change(function(e) {
	not_intialized=true;
    reorder_then_draw();
  });
  $('#draw_mid_lines').change(function(e) {
	not_intialized=true;
    reorder_then_draw();
  });
  $('#Bezier_Curve').change(function(e) {
	not_intialized=true;
    reorder_then_draw();
  });
  $('#minimize_crossings,#maximize_crossings').change(function(e) {
    var is_min = ($('#minimize_crossings,#maximize_crossings').filter(':checked').attr('id') === 'minimize_crossings');
    if (!is_min)
    {
      $('#exh_order_search').attr('checked',true);
    }
    $('#exh_order_search').attr('disabled',!is_min)
	
    data_cols = null; // clear column ordering
	not_intialized=true;
    reorder_then_draw();
  });
  
  $('#all_crossings, #inter_crossings, #inter_crossings_2, #intra_crossings, #intra_crossings_2').change(function(e) {
	if ($('#all_crossings, #inter_crossings, #inter_crossings_2, #intra_crossings, #intra_crossings_2').filter(':checked').attr('id') === 'all_crossings')
		crossing_type=0;//all crossings
	else if ($('#all_crossings, #inter_crossings, #inter_crossings_2, #intra_crossings, #intra_crossings_2').filter(':checked').attr('id') === 'inter_crossings')
		crossing_type=1;//inter-cluster crossings
	else if ($('#all_crossings, #inter_crossings, #inter_crossings_2, #intra_crossings, #intra_crossings_2').filter(':checked').attr('id') === 'intra_crossings')
		crossing_type=2;//intra-cluster crossings
	else if ($('#all_crossings, #inter_crossings, #inter_crossings_2, #intra_crossings, #intra_crossings_2').filter(':checked').attr('id') === 'inter_crossings_2')
		crossing_type=3;//inter-cluster crossings on two focused clusters
	else if ($('#all_crossings, #inter_crossings, #inter_crossings_2, #intra_crossings, #intra_crossings_2').filter(':checked').attr('id') === 'intra_crossings_2')
		crossing_type=4;//intra-cluster crossings on two focused clusters
	else
		crossing_type=-1;//internal error
		
    data_cols = null; // clear column ordering
	not_intialized=true;
    reorder_then_draw();
  });
  
  $('#fc1').change(function(e) {
    data_cols = null;
	not_intialized=true;
    reorder_then_draw();	
  });
  
  $('#fc2').change(function(e) {
    data_cols = null;
	not_intialized=true;
    reorder_then_draw();	
  });  

  $('#reorder_automatic').change(function(e) {
    $('#user_specified_order').attr('disabled', true);
    $('#exh_order_search').attr('disabled', false);
    $('#minimize_crossings,#maximize_crossings,#all_crossings, #inter_crossings, #inter_crossings_2, #intra_crossings, #intra_crossings_2').attr('disabled', false);
    $('#set_user_order').attr('disabled', true);
    data_cols = null;
	not_intialized=true;
    reorder_then_draw();
  });

  $('#reorder_manual').change(function(e) {
    $('#user_specified_order').attr('disabled', false);
    $('#exh_order_search').attr('disabled', true);
    $('#minimize_crossings,#maximize_crossings,#all_crossings, #inter_crossings, #inter_crossings_2, #intra_crossings, #intra_crossings_2').attr('disabled', true);
    $('#set_user_order').attr('disabled', false);
	not_intialized=true;
    reorder_then_draw();
  });

});
</script>
</head>
<body>
<canvas id="vis_canvas" width="500" height="500" style="border: 1px solid black;">
  Your browser does not support canvas.  Use a recent Firefox / Safari / Opera /IE browser.


</canvas>
<table class="dt">
  <tr><th colspan="2">
Dataset 
<select id="ds" onchange="$('#reorder_manual').attr('checked', true); $('#exh_order_search').attr('disabled', true); $('#minimize_crossings').attr('disabled', true); $('#maximize_crossings').attr('disabled', true); $('#all_crossings').attr('disabled', true); $('#inter_crossings').attr('disabled', true); $('#inter_crossings_2').attr('disabled', true); $('#intra_crossings').attr('disabled', true); $('#intra_crossings_2').attr('disabled', true); $('#Bezier_Curve').attr('checked', true); data_cols = null; not_intialized=true; get_data(); reorder_then_draw(); ">
<?php
$ds_ct = 0;
foreach (glob("data/*.arff") as $k => $arff)
{
  $arff_fname = substr($arff, strrpos($arff, '/') + 1);
  $arff_fname = substr($arff_fname, 0, strrpos($arff_fname, '.'));
  print "  <option value='$arff_fname'";
  if ($arff_fname === "wdbc.kmeans.4")
  {
    print " selected='selected'";
  }
  print ">$arff_fname</option>\n";
  ++$ds_ct;
}
?>
</select>
    </th>
  </tr>
  <tr>
    <td>Source:</td><td><a id="source_link" style="font-size: 10pt;" href="#"></a></td>
  </tr>
  <tr>
    <td>Processed:</td><td><a id="processed_link" style="font-size: 10pt;" href="#"></a>
<?php
/*
<a href="how_processed.html" onclick="window.open(this.href, 'how_processed', 'width=700,height=600'); return false;"><img style="border-width: 0px;" src="info.png" width="16" height="16" alt="How were the original datasets processed?" title="How were the original datasets processed?" /></a>
*/
?>
</td>
  </tr>
  <tr>
    <td>Rows:</td><td><span class="num_rows" style="color: green"></span></td>
  </tr>
  <tr>
    <td>Cols:</td><td><span class="num_cols" style="color: green"></span></td>
  </tr>
  <tr>
    <td>Clusters:</td><td><span class="num_clusters"></span></td>
  </tr>
  <tr>
    <td>Order Cost:</td><td><span class="order_cost" style="color: green"></span></td>
  </tr>
</table>

<table class="dt">
  <tr>
    <th>Actions</th>

  </tr>
  <tr>

    <td style="text-align: center"><input id="render_1_step" type="button" value="compute 1 step" onclick="compute_1step_allcols(); render();" /></td>
  </tr>
  <tr>
    <td style="text-align: center"><input id="render_until_converge" type="button" value="compute until converge" onclick="compute_until_converge(); render();" /></td>
  </tr>
  <tr>

    <td><label><input id="draw_mid_lines" type="checkbox" /> draw middle lines</label></td>
  </tr>
  <tr>

    <td><label><input id="Bezier_Curve" type="checkbox" checked="true"/> Bezier Curve</label></td>
  </tr>
</table>

<table class="dt">
  <tr>
    <th>Dimension Reordering</th>
  </tr>
  <tr>
    <td>
      <label title="automatically reorder the dimensions"><input id="reorder_automatic" type="radio"  checked="false" name="reorder" /> automatic</label>
      <br/>
      <label title="checked = find best order among all permutations. unchecked = use faster but not optimal minimum spanning tree method"><input style="margin-left: 26px;" id="exh_order_search" disabled="disabled" type="checkbox" /> exhaustive reorder search</label>
      <br/>
      <label><input style="margin-left: 26px;" id="minimize_crossings" disabled="disabled" type="radio" checked="checked" name="crossings" /> minimize crossings</label>
      <br/>
      <label><input style="margin-left: 26px;" id="maximize_crossings" disabled="disabled" type="radio" name="crossings" /> maximize crossings</label>
    </td>
  </tr>

  <tr>
    <td>
      <label><input style="margin-left: 26px;" id="all_crossings" disabled="disabled" type="radio" checked="checked" name="crossingtype" /> all crossings</label>
      <br/>	
      <label><input style="margin-left: 26px;" id="inter_crossings" disabled="disabled" type="radio" name="crossingtype" /> inter-cluster crossings</label>	  
      <br/>
      <label><input style="margin-left: 26px;" id="inter_crossings_2" disabled="disabled" type="radio" name="crossingtype" /> inter-cluster crossings on</label>	  
      <br/>
	  <label style="margin-left: 45px;">2 focused clusters</label>
	  <br/>	  
      <label><input style="margin-left: 26px;" id="intra_crossings" disabled="disabled" type="radio" name="crossingtype" /> intra-cluster crossings</label>
	  <br/>
      <label><input style="margin-left: 26px;" id="intra_crossings_2" disabled="disabled" type="radio" name="crossingtype" /> intra-cluster crossings on</label>
	  <br/>
	  <label style="margin-left: 45px;">2 focused clusters</label>
    </td>
  </tr> 

  <tr>
    <td>
	  <label style="margin-left: 26px;" >Cluster to focus:</label>
	  <span class="cluster_selections1"></span>
	  <br/>
	  <label style="margin-left: 26px;">Cluster to focus:</label>
	  <span class="cluster_selections2"></span>
    </td>
  </tr>
  
  <tr>
    <td>
      <label title="specify the order of dimensions (left-to-right) manually"><input id="reorder_manual" type="radio" checked="checked" name="reorder" /> manual</label>
      <input type="text" id="user_specified_order" disabled="disabled" />
      <input id="set_user_order" type="button" value="Set" onclick="if (validate_user_order()) not_intialized=true;; reorder_then_draw();" disabled="disabled" />
    </td>
  </tr>
</table>


<table class="dt">
  <tr>
    <th colspan="2">Visualization</th>
  </tr>
  <tr>
    <td class="greek">&alpha; = </td>
    <td><input type="text" id="v_alpha" /><!-- <div class="slider" id="slider_alpha"/> --></td>
  </tr>
  <tr>
    <td class="greek">&beta; = </td>
    <td><input type="text" id="v_beta" /><!-- <div class="slider" id="slider_beta"/> --></td>
  </tr>
  <tr>
    <td class="greek">&gamma; = </td>
    <td><input type="text" id="v_gamma" /><!-- <div class="slider" id="slider_gamma"/> --></td>
  </tr>
  <tr>
    <td colspan="3" style="text-align: center"><input type="button" value="Update" onclick="update_greek()" /></td>
  </tr>
</table>

<table class="dt">
  <tr>
    <th>Shortcuts</th>
  </tr>
<?php
//$abg1 = array(array(0.8,0.1,0.1), array(0.1,0.8,0.1), array(0.1,0.1,0.8));
$abg1 = array(array(0.33,0.33,0.33), array(0.5,0.25,0.25), array(0.25,0.5,0.25), array(0.25, 0.25, 0.5));
foreach($abg1 as $abg)
{
  print "    <tr>\n";
  printf("    <td><input type='button' value='&alpha; = %0.2f, &beta; = %0.2f, &gamma; = %0.2f' onclick='alpha = %0.2f; beta = %0.2f; gamma = %0.2f; set_greek(); init_c_prime_and_z(); compute_until_converge(); render();' /></td>\n", $abg[0], $abg[1], $abg[2], $abg[0], $abg[1], $abg[2]);
  print "    </tr>\n";
}
?>
</table>

<table class="dt">
  <tr>
    <th>Visualization Size (px)</th>
  <tr>
  <tr>
    <td><input type="radio" name="imgwidth" checked="true" value="autoadjust" onclick="$('#fixedwidth,#fixedheight,#fwsubmit').attr('disabled',true); resize_canvas()" /> [page width] x 440</td>
  <tr>
    <td><input type="radio" name="imgwidth" value="fixed" onclick="$('#fixedwidth,#fixedheight,#fwsubmit').attr('disabled',false)"  /> <form id="whform" onsubmit="resize_canvas(); return false;" style='display: inline'><input type="text" id="fixedwidth" value="1000" disabled="disabled" /> <input type="text" id="fixedheight" value="600" disabled="disabled"/> <input id='fwsubmit' type="Submit" value="Set" disabled="disabled"></form><td>
  </tr>
</table>

</body>
</html>
