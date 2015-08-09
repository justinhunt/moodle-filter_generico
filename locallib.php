<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package filter_generico
 * @copyright  2014 Justin Hunt (http://poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/adminlib.php');

/**
 * No setting - just heading and text.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_genericopresets extends admin_setting {

	  /** @var mixed int index of template*/
    public $templateindex;
    /** @var array template data for spec index */
    public $presetdata;
    public $visiblename;
    public $information;

    /**
     * not a setting, just text
     * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in config_plugins.
     * @param string $heading heading
     * @param string $information text in box
     */
    public function __construct($name, $visiblename, $information,$templateindex) {
        $this->nosave = true;
        $this->templateindex = $templateindex;
        $this->presetdata = $this->fetch_presets();
        $this->visiblename=$visiblename;
        $this->information=$information;
        parent::__construct($name, $visiblename, $information, '',$templateindex);
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_setting() {
        return true;
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Never write settings
     * @return string Always returns an empty string
     */
    public function write_setting($data) {
    // do not write any setting
        return '';
    }

    /**
     * Returns an HTML string
     * @return string Returns an HTML string
     */
    public function output_html($data, $query='') {
        global $OUTPUT;
     
        //make our js
        $jscallback = 'filter_generico_fillfields_' . $this->templateindex ;
		$js ="<script>";
		$js .="function $jscallback(presetindex){";
		$js .="if(!presetindex){return;}";
		$js .="var presets = " . json_encode($this->presetdata) .";";
		$js .="var key = document.getElementById('id_s_filter_generico_templatekey_' + $this->templateindex);";
		$js .="var requirecss = document.getElementById('id_s_filter_generico_templaterequire_css_' + $this->templateindex);";
		$js .="var requirejs = document.getElementById('id_s_filter_generico_templaterequire_js_' + $this->templateindex);";
		$js .="var defaults = document.getElementById('id_s_filter_generico_templatedefaults_' + $this->templateindex);";
		$js .="var jquery = document.getElementById('id_s_filter_generico_templaterequire_jquery_' + $this->templateindex);";
		$js .="var amd = document.getElementById('id_s_filter_generico_template_amd_' + $this->templateindex);"; 
		$js .="var body = document.getElementById('id_s_filter_generico_template_' + $this->templateindex);";
		$js .="var bodyend = document.getElementById('id_s_filter_generico_templateend_' + $this->templateindex);";
		$js .="var script = document.getElementById('id_s_filter_generico_templatescript_' + $this->templateindex);";
		$js .="var style = document.getElementById('id_s_filter_generico_templatestyle_' + $this->templateindex);";


		$js .="key.value=presets[presetindex]['key'];";
		$js .="requirecss.value=presets[presetindex]['requirecss'];";
		$js .="requirejs.value=presets[presetindex]['requirejs'];";
		$js .="defaults.value=presets[presetindex]['defaults'];";
		$js .="jquery.value=presets[presetindex]['jquery'];";
		$js .="amd.value=presets[presetindex]['amd'];";
		$js .="jquery.checked=presets[presetindex]['jquery'] ? true : false;";
		$js .="amd.checked=presets[presetindex]['amd'] ? true : false;";
		$js .="body.value=presets[presetindex]['body'];";
		$js .="bodyend.value=presets[presetindex]['bodyend'];";
		$js .="script.value=presets[presetindex]['script'];";
		$js .="style.value=presets[presetindex]['style'];";
		$js .="}";
		$js .="</script>";
		
        //build our select form
        $keys = array_keys($this->presetdata);
        $usearray = array();
        
        foreach($keys as $key){
        	$usearray[$key]=$this->presetdata[$key]['key'];
        }
        $select = html_writer::select($usearray,'filter_generico/presets','','--custom--', array('onchange'=>$jscallback . '(this.value)'));
	
		return format_admin_setting($this, $this->visiblename,
        '<div class="form-text defaultsnext">'. $js . $select . '</div>',
        $this->information, true, '','', $query);

    }
	
	protected function fetch_presets(){

	$ret = array();
	$templates = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19);
	
	foreach($templates as $templateno){
		$presets = array();
		switch($templateno){
			case '1':
				$presets['key'] ='helloworld';
				$presets['requirecss'] ='';
				$presets['requirejs'] = '';
				$presets['amd'] = 1;
				$presets['jquery'] = 0;
				$presets['defaults'] = '';
				$presets['bodyend'] = '';
				$presets['body'] ='Welcome @@USER:FIRSTNAME@@. You are awesome.
You look like this
@@USER:PIC@@
@@USER:PICURL@@';
				$presets['script'] = '';
				$presets['style'] = '';
				break;
			case '2':
				$presets['key'] ='screenr';
				$presets['requirecss'] ='';
				$presets['requirejs'] = '';
				$presets['amd'] = 1;
				$presets['jquery'] = 0;
				$presets['defaults'] = 'width=650,height=396';
				$presets['bodyend'] = '';
				$presets['body'] ='<iframe src="http://www.screenr.com/embed/@@id@@" width="@@width@@" height="@@height@@" frameborder="0"></iframe>';
				$presets['script'] = '';
				$presets['style'] = '';
				break;

			case '3':
				$presets['key'] ='toggle';
				$presets['requirecss'] ='';
				$presets['requirejs'] = '';
				$presets['amd'] = 1;
				$presets['jquery'] = 0;
				$presets['defaults'] = 'linktext=clickme';
				$presets['bodyend'] = '</div>';
				
				$presets['body']='<a href="#" id="@@AUTOID@@">@@linktext@@</a>
						<div id="@@AUTOID@@_target" class="@@AUTOID@@_target" hidden="hidden" style="display: none;">';
				$presets['script'] = '$("#"  + @@AUTOID@@).click(function(e){
					$("#" + @@AUTOID@@ + "_target").toggle(); return false;});';
				$presets['style'] = '';
				break;
			
			case '4':
				$presets['key'] ='linechart';
				$presets['requirecss'] ='';
				$presets['requirejs'] = '//cdnjs.cloudflare.com/ajax/libs/Chart.js/0.2.0/Chart.min.js';
				$presets['amd'] = 0;
				$presets['jquery'] = 0;
				$presets['defaults'] = 'width=600,height=400,datalabel=mydata,labels="jan,feb,march",data="1,2,3"';
				$presets['bodyend'] = '';
				$presets['body'] ='<canvas id="@@AUTOID@@" width="@@width@@" height="@@height@@"></canvas>';
				$presets['style'] = '';
				$presets['script'] = 'var ctx = document.getElementById("@@AUTOID@@").getContext("2d");
var cjoptions = {


  ///Boolean - Whether grid lines are shown across the chart
    scaleShowGridLines : true,

    //String - Colour of the grid lines
    scaleGridLineColor : "rgba(0,0,0,.05)",

    //Number - Width of the grid lines
    scaleGridLineWidth : 1,

    //Boolean - Whether the line is curved between points
    bezierCurve : true,

    //Number - Tension of the bezier curve between points
    bezierCurveTension : 0.4,

    //Boolean - Whether to show a dot for each point
    pointDot : true,

    //Number - Radius of each point dot in pixels
    pointDotRadius : 4,

    //Number - Pixel width of point dot stroke
    pointDotStrokeWidth : 1,

    //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
    pointHitDetectionRadius : 20,

    //Boolean - Whether to show a stroke for datasets
    datasetStroke : true,

    //Number - Pixel width of dataset stroke
    datasetStrokeWidth : 2,

    //Boolean - Whether to fill the dataset with a colour
    datasetFill : true,

    //String - A legend template
    legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].lineColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>"

}

var cjdata = {
    labels: "@@labels@@".split(","),
    datasets: [
        {
            label: "@@datalabel@@",
            fillColor: "rgba(220,220,220,0.2)",
            strokeColor: "rgba(220,220,220,1)",
            pointColor: "rgba(220,220,220,1)",
            pointStrokeColor: "#fff",
            pointHighlightFill: "#fff",
            pointHighlightStroke: "rgba(220,220,220,1)",
            data: "@@data@@".split(",")
        }
    ]
};

var myLineChart = new Chart(ctx).Line(cjdata, cjoptions);';
				break;
				
		case '5':
				$presets['key'] ='barchart';
				$presets['requirecss'] ='';
				$presets['requirejs'] = '//cdnjs.cloudflare.com/ajax/libs/Chart.js/0.2.0/Chart.min.js';
				$presets['amd'] = 0;
				$presets['jquery'] = 0;
				$presets['defaults'] = 'width=600,height=400,datalabel=mydata,labels="jan,feb,march",data="1,2,3"';
				$presets['bodyend'] = '';
				$presets['body'] ='<canvas id="@@AUTOID@@" width="@@width@@" height="@@height@@"></canvas>';
				$presets['style'] = '';
				$presets['script'] = 'var ctx = document.getElementById("@@AUTOID@@").getContext("2d");
var cjoptions = {


  ///Boolean - Whether grid lines are shown across the chart
    scaleShowGridLines : true,

    //String - Colour of the grid lines
    scaleGridLineColor : "rgba(0,0,0,.05)",

    //Number - Width of the grid lines
    scaleGridLineWidth : 1,

    //Boolean - Whether the line is curved between points
    bezierCurve : true,

    //Number - Tension of the bezier curve between points
    bezierCurveTension : 0.4,

    //Boolean - Whether to show a dot for each point
    pointDot : true,

    //Number - Radius of each point dot in pixels
    pointDotRadius : 4,

    //Number - Pixel width of point dot stroke
    pointDotStrokeWidth : 1,

    //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
    pointHitDetectionRadius : 20,

    //Boolean - Whether to show a stroke for datasets
    datasetStroke : true,

    //Number - Pixel width of dataset stroke
    datasetStrokeWidth : 2,

    //Boolean - Whether to fill the dataset with a colour
    datasetFill : true,

    //String - A legend template
    legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].lineColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>"

}

var cjdata = {
    labels: "@@labels@@".split(","),
    datasets: [
        {
            label: "@@datalabel@@",
            fillColor: "rgba(220,220,220,0.2)",
            strokeColor: "rgba(220,220,220,1)",
            pointColor: "rgba(220,220,220,1)",
            pointStrokeColor: "#fff",
            pointHighlightFill: "#fff",
            pointHighlightStroke: "rgba(220,220,220,1)",
            data: "@@data@@".split(",")
        }
    ]
};

var myBarChart = new Chart(ctx).Bar(cjdata, cjoptions);';
				break;
			
			case '6':
				$presets['key'] ='piechart';
				$presets['requirecss'] ='';
				$presets['requirejs'] = '//cdnjs.cloudflare.com/ajax/libs/Chart.js/0.2.0/Chart.min.js';
				$presets['amd'] = 0;
				$presets['jquery'] = 0;
				$presets['defaults'] = 'width=600,height=400,datalabel=mydata,labels="jan,feb,march",data="1,2,3"';
				$presets['bodyend'] = '';
				$presets['body'] ='<canvas id="@@AUTOID@@" width="@@width@@" height="@@height@@"></canvas>';
				$presets['style'] = '';
				$presets['script'] = 'var ctx = document.getElementById("@@AUTOID@@").getContext("2d");
var cjoptions = {


   //Boolean - Whether we should show a stroke on each segment
    segmentShowStroke : true,

    //String - The colour of each segment stroke
    segmentStrokeColor : "#fff",

    //Number - The width of each segment stroke
    segmentStrokeWidth : 2,

    //Number - The percentage of the chart that we cut out of the middle
    percentageInnerCutout : 50, // This is 0 for Pie charts

    //Number - Amount of animation steps
    animationSteps : 100,

    //String - Animation easing effect
    animationEasing : "easeOutBounce",

    //Boolean - Whether we animate the rotation of the Doughnut
    animateRotate : true,

    //Boolean - Whether we animate scaling the Doughnut from the centre
    animateScale : false,

    //String - A legend template
    legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><li><span style=\"background-color:<%=segments[i].fillColor%>\"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>"

};
var colors = ["#F7464A","#46BFBD","#FDA25C","#F7404A","#464FBD","#FD445C","#FDB45C","#F7464A","#46BFBD","#FDA25C","#F7404A","#464FBD","#FD445C","#FDB45C"];
var highlights=["#FDB45C","#5AD3D1","#FF5870","#FD445C","#5A63D1","#FF5870","#FFC870","#FDB45C","#5AD3D1","#FF5870","#FD445C","#5A63D1","#FF5870","#FFC870"];
var labels= "@@labels@@".split(",");
var values= "@@data@@".split(",");
var cjdata=[];
for(var i=0;i<labels.length;i++){
	cjdata.push({label: labels[i],color: colors[i],highlight: highlights[i],value: parseInt(values[i])});

}

var myPieChart = new Chart(ctx).Pie(cjdata, cjoptions);';
				break;
		
			case '7':
				$presets['key'] ='tabs';
				$presets['requirecss'] ='//code.jquery.com/ui/1.11.2/themes/redmond/jquery-ui.css';
				$presets['requirejs'] = '//code.jquery.com/ui/1.11.2/jquery-ui.min.js';
				$presets['amd'] = 1;
				$presets['jquery'] = 1;
				$presets['defaults'] = '';
				$presets['bodyend'] = '</div>';
				$presets['body'] ='<div id="@@AUTOID@@"><ul></ul>';
				$presets['script']='var theul = $("#" + @@AUTOID@@ + " ul");
$(".filter_generico_tabitem", $("#" + @@AUTOID@@)).each(function () {
    theul.append("<li><a href=\'#" + this.id + "\'><span>"+this.title+"</span></a></li>");
});
$( "#" + @@AUTOID@@).tabs();';

				$presets['style'] = '';
				break;
				
			case '8':
				$presets['key'] ='tabitem';
				$presets['requirecss'] ='';
				$presets['requirejs'] = '';
				$presets['amd'] = 1;
				$presets['jquery'] = 0;
				//$presets['defaults'] = 'tabnumber=1';
				$presets['bodyend'] = '</div>';
				//$presets['body'] =' <div id="jqtab_@@tabnumber@@">';
				$presets['body']='<div id="@@AUTOID@@" class="filter_generico_tabitem" title="@@title@@">';
				$presets['defaults']='title="mybaby"';
				$presets['script'] = '';
				$presets['style'] = '';
				break;
				
				
			case '9':
				$presets['key'] ='accordian';
				$presets['requirecss'] ='//code.jquery.com/ui/1.11.2/themes/redmond/jquery-ui.css';
				$presets['requirejs'] = '//code.jquery.com/ui/1.11.2/jquery-ui.min.js';
				$presets['amd'] = 1;
				$presets['jquery'] = 1;
				$presets['defaults'] = '';
				$presets['bodyend'] = '</div>';
				$presets['body'] ='<div id="@@AUTOID@@">';
				$presets['script'] = ' $(function() {
    $( "#" + @@AUTOID@@).accordion({
  header: "h3",
  heightStyle: "content"
 })
});';
				$presets['style'] = '';
				break;
				
			case '10':
				$presets['key'] ='accordianitem';
				$presets['requirecss'] ='';
				$presets['requirejs'] = '';
				$presets['amd'] = 1;
				$presets['jquery'] = 0;
				$presets['defaults'] = '';
				$presets['bodyend'] = '</div>';
				$presets['body'] ='<h3>@@titletext@@</h3>
<div>';
				$presets['script'] = '';
				$presets['style'] = '';
				break;
				
			case '11':
				$presets['key'] ='qrcode';
				$presets['requirecss'] ='';
				$presets['requirejs'] = '//cdnjs.cloudflare.com/ajax/libs/jquery.qrcode/1.0/jquery.qrcode.min.js';
				$presets['amd'] = 1;
				$presets['jquery'] = 1;
				$presets['defaults'] = 'data=http://mywebsite.com,size=100';
				$presets['bodyend'] = '';
				$presets['body'] ='<div id="@@AUTOID@@"></div>';
				$presets['script'] = '$("#" + @@AUTOID@@).qrcode({
    "render": "div",
    "size": @@size@@,
	"height": @@size@@,
	"width": @@size@@,
    "color": "#3a3",
    "text": "@@data@@"
});';
				$presets['style'] = '';
				break;
				
			case '12':
				$presets['key'] ='lightboxyoutube';
				$presets['requirecss'] ='//cdn.rawgit.com/noelboss/featherlight/1.0.3/release/featherlight.min.css';
				$presets['requirejs'] = '//cdn.rawgit.com/noelboss/featherlight/1.0.3/release/featherlight.min.js';
				$presets['amd'] = 0;
				$presets['jquery'] = 1;
				$presets['defaults'] = 'width=160,height=120,videowidth=640,videoheight=480';
				$presets['bodyend'] = '';
				$presets['body'] ='<a href="#" data-featherlight="#@@AUTOID@@"><div class="filter_generico_ytl"><img src="http://img.youtube.com/vi/@@videoid@@/hqdefault.jpg" width="@@width@@" height="@@height@@"/ ></div></a>
<div style="display: none;">
<div  id="@@AUTOID@@"><iframe width="@@videowidth@@" height="@@videoheight@@" src="//www.youtube.com/embed/@@videoid@@?rel=0" frameborder="0" allowfullscreen></iframe></div>
</div>';
				$presets['script'] = '';
				$presets['style'] = '.filter_generico_ytl img{display: block;}
.filter_generico_ytl { 
position: relative; 
display: inline-block;
}
.filter_generico_ytl:after {
content: ">";
  font-size: 20px;
  line-height: 30px;
  color: #FFFFFF;
  text-align: center;
  position: absolute;
  top: 40%;
  left: 40%;
  width: 20%;
  height: 32px;
  z-index: 2;
  background: #FF0000;
  border-radius: 8px;
  pointer-events: none;
}';
				break;

				
			case '13':
				$presets['key'] ='tts';
				$presets['requirecss'] ='';
				$presets['requirejs'] = '';
				$presets['amd'] = 1;
				$presets['jquery'] = 0;
				$presets['defaults'] = 'text="say something",lang="en"';
				$presets['bodyend'] = '';
				$presets['body'] ='<a href="//translate.google.com/translate_tts?ie=UTF-8&q=@@text@@&tl=@@lang@@">@@text@@</a>';
				$presets['script'] = '';
				$presets['style'] = '';
				break;
		/*
		'<a onclick="this.firstChild.play()"><audio>
  <source src="//translate.google.com/translate_tts?ie=UTF-8&q=@@text@@&tl=@@lang@@" type="audio/mpeg">
</audio>@@text@@</a>';

'@@text@@<br /><audio controls>
  <source src="//translate.google.com/translate_tts?ie=UTF-8&q=@@text@@&tl=@@lang@@" type="audio/mpeg">
</audio>';
*/

		case '14':
				$presets['key'] ='imagegallery';
				$presets['requirecss'] ='//cdnjs.cloudflare.com/ajax/libs/galleria/1.4.2/themes/classic/galleria.classic.css';
				$presets['requirejs'] = '//cdnjs.cloudflare.com/ajax/libs/galleria/1.4.2/galleria.min.js';
				$presets['amd'] = 0;
				$presets['jquery'] = 0;
				$presets['defaults'] = '';
				$presets['bodyend'] = '</div>';
				$presets['body'] ='<div class="galleria">';
				$presets['script'] = 'Galleria.loadTheme("https://cdnjs.cloudflare.com/ajax/libs/galleria/1.4.2/themes/classic/galleria.classic.js");
Galleria.run(".galleria");';
				$presets['style'] = '.galleria{ width: 450px; height: 400px; background: #000 }';
				break;
				
		case '15':
				$presets['key'] ='videogallery';
				$presets['requirecss'] ='';
				$presets['requirejs'] = 'https://jwpsrv.com/library/YOURJWPLAYERID.js';
				$presets['amd'] = 1;
				$presets['jquery'] = 1;
				$presets['defaults'] = '';
				$presets['bodyend'] = '</div>';
				$presets['body'] ='<div id="@@AUTOID@@">';
				$presets['script'] = 'var playlist=[];
$("a", $("#" + @@AUTOID@@)).each(function () {
    playlist.push({file: this.href, title: this.text});
});
$("#" + @@AUTOID@@).empty();
jwplayer("@@AUTOID@@").setup({
playlist: playlist,
width: 720,
height: 270,
listbar: {
        position: "right",
        layout: "basic",
        size: 240
 }
});';
				$presets['style'] = '';
				break;
				
		case '16':
				$presets['key'] ='fontawesome';
				$presets['requirecss'] ='//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css';
				$presets['requirejs'] = '';
				$presets['amd'] = 1;
				$presets['jquery'] = 0;
				$presets['defaults'] = 'icon="fa-cog",orientation="fa-spin|fa-rotate-90|fa-rotate-180|fa-rotate-270",size="fa-lg|fa-2x",layout="pull-left|fa-border"';
				$presets['bodyend'] = '';
				$presets['body'] ='<span class="fa @@icon@@ @@orientation@@ @@size@@ @@layout@@"></span>';
				$presets['script'] = '';
				$presets['style'] = '';
				break;
		
		case '17':
				$presets['key'] ='infobox';
				$presets['requirecss'] ='//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css';
				$presets['requirejs'] = '';
				$presets['amd'] = 1;
				$presets['jquery'] = 0;
				$presets['defaults'] = 'text="Your message goes here."';
				$presets['bodyend'] = '';
				$presets['body'] ='<div class="filter_generico_info">
    <i class="fa fa-info-circle"></i>
    @@text@@
</div>';
				$presets['script'] = '';
				$presets['style'] = '.filter_generico_info {
	margin: 10px 0px;
    padding:12px;
    color: #00529B;
    background-color: #BDE5F8;
    border: 1px solid;
    border-radius:.5em;
}
.isa_info i {
    margin:10px 22px;
    font-size:2em;
    vertical-align:middle;
}';
				break;
				
		case '18':
				$presets['key'] ='warningbox';
				$presets['requirecss'] ='//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css';
				$presets['requirejs'] = '';
				$presets['amd'] = 1;
				$presets['jquery'] = 0;
				$presets['defaults'] = 'text="Your message goes here."';
				$presets['bodyend'] = '';
				$presets['body'] ='<div class="filter_generico_warning">
    <i class="fa fa-warning"></i>
    @@text@@
</div>';
				$presets['script'] = '';
				$presets['style'] = '.filter_generico_warning {
	margin: 10px 0px;
    padding:12px;
    color: #9F6000;
    background-color: #FEEFB3;
    border: 1px solid;
    border-radius:.5em;
}
.isa_warning i {
    margin:10px 22px;
    font-size:2em;
    vertical-align:middle;
}';
				break;

		case '19':
				$presets['key'] ='errorbox';
				$presets['requirecss'] ='//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css';
				$presets['requirejs'] = '';
				$presets['amd'] = 1;
				$presets['jquery'] = 0;
				$presets['defaults'] = 'text="Your message goes here."';
				$presets['bodyend'] = '';
				$presets['body'] ='<div class="filter_generico_error">
    <i class="fa fa-times-circle"></i>
    @@text@@
</div>';
				$presets['script'] = '';
				$presets['style'] = '.filter_generico_error {
	margin: 10px 0px;
    padding:12px;
    color: #D8000C;
    background-color: #FFBABA;
    border: 1px solid;
    border-radius:.5em;
}
.isa_error i {
    margin:10px 22px;
    font-size:2em;
    vertical-align:middle;
}';
				break;
		
		}	
		
		
	  //update our return value
	    $ret[$templateno] = $presets;
	}
	return $ret;
	
}
}//end of class