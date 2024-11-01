=== Wp Custom Field Chart ===
Contributors: showi
Donate link: 
Tags: custom field, chart, javascript
Requires at least: 3.9.1
Tested up to: 3.9.1
Stable tag: 0.0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Make chart from custom field attached to your post/page using Chart.js
javascript library.

== Description ==
This plugin collect data attached to post/article via **custom field** and make
chart of it.
This plugin use **Chart.js** for chart drawing [ChartJs](http://www.chartjs.org/)

Data are collected by looking for specific *custom field* attached to your
post/page. You can change aggregation method, intervall...

See [usage](http://wordpress.org/plugins/wp-custom-field-chart/other_notes/)

== Installation ==
1. Upload the entire `wp-custom-field-chart` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Include [custom_field_chart] tag in your post/page (See Other Notes)

== Frequently Asked Questions ==
No FAQ :-)

== Screenshots ==
1. One field for each chart (Two tags)
2. Combined field in one chart (One Tag)
3. Bar chart with different *interval*

== Changelog ==
= 0.0.5 =
* Bump Chart.js version to 1.0.1-beta.4
* Better use of Field object, cleaning of old code
= 0.0.4 = 
* Add *round* and *dump* attribute
* Using *Field* class everywhere
= 0.0.3 =
* More attribute validation and default
* Now as to_date default, introducing post to specify post date as to_date
* Better readme, well more informations...
= 0.0.2 =
* Uploading some screenshots
* Improved readme.txt
= 0.0.1 =
* Beta Release

== Usage ==
Edit your post/page in text mode and put some Javascript and a Wordpress tag

= Minimum =
`
<script>
var mydata = { datasets: [{}]};
</script>
[custom_field_chart fields="humidity" js_data="mydata"]
`
For each field you need to put empty {} into datasets.

For two fields:
`
<script>
var mydata = {datasets: [{},{}]}
</script>
[custom_field_chart fields="humidity,temperature" js_data="mydata"]
`
But it's pretty useless to put more than one field without different colors :)

= More =
`
<script>
var mydata = {
    datasets: [
        {
            label: "Humidity",
            fillColor: "rgba(255,73,0,1)",
            strokeColor: "rgba(255,73,0,1)",
            pointColor: "rgba(255,73,0,1)",
            pointStrokeColor: "#fff",
            pointHighlightFill: "#fff",
            pointHighlightStroke: "rgba(220,220,220,1)",
        },
        {
            label: "Temperature",
            fillColor: "rgba(255,73,0,1)",
            strokeColor: "rgba(255,73,0,1)",
            pointColor: "rgba(255,73,0,1)",
            pointStrokeColor: "#fff",
            pointHighlightFill: "#fff",
            pointHighlightStroke: "rgba(220,220,220,1)",
        },
    ]
};

var myopts = {
    pointDotRadius: 1,
    bezierCurveTension: 0.2,
    barStrokeWidth : 2,
    barValueSpacing : 2,
    barDatasetSpacing : 0,
};

// Optional...
jQuery(window).load(function() {
    Chart.defaults.global.responsive = true;
    Chart.defaults.global.animationEasing = "easeOutBounce";
    Chart.defaults.global.onAnimationComplete = function(){
        alert('Hello');
    }
});
// End optional
</script>

[custom_field_chart width="1000" height="300"
  kind="line" method="track" interval="day" interval_count="31" 
  fields="humidity,temperature" js_data="mydata" js_options="myopts"]
`
= Notes =
1. js_data and js_options must reflect name given to your javascript variable.
2. Look at http://chartjs.org/ for documentation
3. You don't need to supply labels and data (like in chartjs.org example) :)


== Tag attributes == 
= Required =
1. *fields*: Custom field separate by comma
1. *js_data*: Name of javascript variable holding chart datasets

= Optional =
1. *width*: Chart width (default: 400)
1. *height*: Chart Height (default: 200)
1. *method*: Aggregate method track, delta or cumulative (defaul: track)
1. *interval*: year, month, day (default: day)
1. *interval_count*: How many year, mont or day (default: 31)
1. *js_options*: Name of javascript variable holding chart options
1. *kind*: Chart type line or bar (default: line)
1. *to_date*: Current date by default, post date if 'post' specified else value supplied 
1. *dump*: Dumping attributes for debug (default: False)
1. *round*: Rounding data with specified precision

== Note ==
Beta software... Interface may change. 
