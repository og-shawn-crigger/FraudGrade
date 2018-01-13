<?php
/**
 * Checks user IP address against fraudgarde.com's IP review API and blocks visitors can block visitors
 * who come from Proxy's, TOR, Datacenters, or VPN networks.
 *
 * @since      1.0.0
 *
 * @package    FraudGrade
 * @subpackage FraudGrade/admin
 * @author     FraudGrade
 * @version  1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) :
   die;
endif;

/**
 * The HTML view file for the Dashboard chart widget
 *
 * @package    FraudGrade
 * @subpackage FraudGrade/admin/view
 * @author     FraudGrade
 * @version  1.0.0
 */
?>
      <div id="chart" style="width: 100%; min-height: 510px; padding: 0px; position: relative;" ></div>
      <div id="flot-memo" style="text-align:center;height:30px;width:250px;height:20px;text-align:center;margin:0"></div>
      <script language="javascript">


var options = {
    series: {
        pie: {
            show: true,
            label: {
                show: true,
                formatter: function (label, series) {
                                                       console.log(series);
                    return '<div style="border:1px solid grey;font-size:8pt;text-align:center;padding:5px;color:white;">' +
                    label + ' : ' +
                    series.data[0][1] +
                    '</div>';
                    return '<div style="border:1px solid grey;font-size:8pt;text-align:center;padding:5px;color:white;">' +
                    label + ' : ' +
                    Math.round(series.percent) +
                    '%</div>';
                },
                background: {
                    opacity: 0.8,
                    color: '#000'
                }
            }
        }
    },
    legend: {
        show: false
    },
    grid: {
        hoverable: true
    }
};

var options1 = {
    series: {
        pie: {
            show: true,
            tilt: 0.5
        }
    }
};

var options2 = {
    series: {
        pie: {
            show: true,
            innerRadius: 0.5,
            label: {
                show: true
            }
        }
    }
};

jQuery.fn.showMemo = function () {
    jQuery(this).bind("plothover", function (event, pos, item) {
        if (!item) { return; }
        console.log(item.series.data)
        var html = [];
        var percent = parseFloat(item.series.percent).toFixed(2);
console.log(item.series);
        html.push("<div style=\"border:1px solid grey;background-color:",
             item.series.color,
             "\">",
             "<span style=\"color:white\">",
             item.series.label,
             " : ",
             jQuery.formatNumber(item.series.data[0][1], { format: "#,###", locale: "us" }),
             " (", percent, "%)",
             "</span>",
             "</div>");
        jQuery("#flot-memo").html(html.join(''));
    });
}

jQuery(document).ready(function ($) {
    $.plot($("#chart"), ipc_chart_data, options);
    $("#flot-placeholder").showMemo();
});
      </script>