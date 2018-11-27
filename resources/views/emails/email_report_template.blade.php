<!DOCTYPE html>
<html>
   <head>
      <title>EverGenius</title>
	   <meta charset="UTF-8">
	   <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700" rel="stylesheet">
	  <style type="text/css">
		.star-ratings-css {
  unicode-bidi: bidi-override;
  color: #ccc;
  font-size: 20px;
  height: 20px;
  width: 100px;
  margin: 0 auto;
  position: relative;
  padding: 0;
  text-align:left;
  display:inline-block;
}

.star-ratings-css-top{
    color: #ffff47;
    padding: 0;
    position: absolute;
    z-index: 1;
    display: block;
    top: 0;
    left: 0;
    overflow: hidden;
}
  
.star-ratings-css-bottom {
    padding: 0;
    display: block;
    z-index: 0;
}

.highcharts-credits{
	display: none!important;
}


	  </style>

	 <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/funnel.js"></script>
  	<title></title>
  	 <script>
        $(document)
            .ready(function() {
                var donutEl = document.getElementById("donut1").getContext("2d");
                var apptdata = [
                    <?php 
            if (!empty($appointments_sources)) {
                foreach ($appointments_sources as $source) {
                    echo $source['total'].",";
                }
            } else {
                echo "10,"."10";
            }
        ?>
                ];
                var data = {
                    "datasets": [{
                        "label": "My First Dataset",
                        "data": apptdata,
                        "backgroundColor": ["#7CDB89", "#F99265"]
                    }]
                };
                var options = {
                    responsive: true,
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false,
                        text: 'Chart.js Doughnut Chart'
                    },
                    animation: {
                        animateScale: false,
                        animateRotate: false
                    },
                    cutoutPercentage: 70,
                    maintainAspectRatio: false
                }
                var myDoughnutChart = new Chart(donutEl, {
                    type: 'doughnut',
                    data: data,
                    options: options
                });

                var donutEl1 = document.getElementById("donut1").getContext("2d");
                var dataleads = [<?php
        if (!empty($leads['sources_leads'])) {
            foreach ($leads['sources_leads'] as $key=>$value) {
                echo "'".$value['count']."',";
            }
        } else {
            echo "'10',"."'10'";
        }
            
        ?>];
                var dataleadscolor = [<?php
            if (!empty($leads['sources_leads'])) {
                foreach ($leads['sources_leads'] as $key=>$value) {
                    echo "'".$value['color']."',";
                }
            } else {
                echo '"#ccccc",'.'"#ccccc"';
            }
        ?>];
                var data1 = {
                    "datasets": [{
                        "label": "My First Dataset",
                        "data": dataleads,
                        "backgroundColor": dataleadscolor
                    }]
                };
                var options1 = {
                    responsive: true,
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false,
                        text: 'Chart.js Doughnut Chart'
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    },
                    cutoutPercentage: 70,
                    maintainAspectRatio: false
                }
                var myDoughnutChartq = new Chart(donutEl1, {
                    type: 'doughnut',
                    data: data1,
                    options: options1
                });

            });
    </script>
   </head>
   <body style="background:#fff;">
      <table width="900" border="0" cellpadding="0" cellspacing="0" align="center" style="font-family: 'Montserrat', sans-serif; font-size:15px; color:#333; font-weight:400;">
      <tr>
         <td height="20"></td>
      </tr>
      <tr>
         <td align="center"><img src="/img/logo.png"/></td>
      </tr>
      <tr>
         <td height="20"></td>
      </tr>
      <tr>
         <td style="background:#d0d7dd; height:1px;"></td>
      </tr>
      <tr>
         <td height="20"></td>
      </tr>
      <tr>
         <td style="text-align:center; font-family:'Montserrat', sans-serif; font-size:26px; color:#333; font-weight:500;">Daily Work Summary</td>
      </tr>
      <tr>
         <td height="10"></td>
      </tr>
      <tr>
         <td style="text-align:center; font-family:'Montserrat', sans-serif; font-size:18px; color:#333; font-weight:400;">{{$company_details['name']}}</td>
      </tr>
      <tr>
         <td height="10"></td>
      </tr>
      <tr>
         <td style="text-align:center; font-family:'Montserrat', sans-serif; font-size:18px; color:#333; font-weight:500;">{{$date_today}}</td>
      </tr>
      <tr>
         <td height="20"></td>
      </tr>
      <tr>
         <td>
            <table width="818" border="0" cellpadding="0" cellspacing="0" align="center" style="background:#edeef2;">
               <tr>
                  <td>
                     <table width="792" border="0" cellpadding="0" cellspacing="0" align="center">
                        <tr>
                           <td height="10"></td>
                        </tr>
                        <tr>
                           <td>
                              <table width="100%" border="0" cellpadding="0" cellspacing="0" align="left">
                                 <tr>
                                    <td valign="top">
                                       <table width="412" border="0" cellpadding="0" cellspacing="0" align="left">
                                          <tr>
                                             <td style="font-family:'Montserrat', sans-serif; font-size:16px; color:#333; text-transform:uppercase; text-align:left; font-weight:500;">Sales Funnel</td>
                                          </tr>
                                          <tr>
                                             <td height="10"></td>
                                          </tr>
                                          <tr>
                                             <td style="background:#fff;">
                                             	<div style="background-color: #fff;width: 100%; height: 378px;">
                               					<div style="font-family:'Montserrat', sans-serif; font-size:16px; color:#1f3b58; padding: 20px 0; text-align:center; font-weight:500;">Avg. Lead Response Time: {{$funnel_data['avg_lead_response_time']['H']}} hrs {{$funnel_data['avg_lead_response_time']['M']}} min</div>
                               					@if($show_funnel_data)
                                <div id="funnel_mail"></div>
                                @else
                                <img src="/img/funnel-blank-email.jpg" style="width: 388px;height: 250px;">
                                @endif

                                <div style="font-family:'Montserrat', sans-serif; font-size:23px; color:#1f3b58; text-align:center; padding-top: 20px; font-weight:600; width: 80%;">${{number_format($funnel_data['close_amount'])}}</div>
                            </div>
                        </td>
                                          </tr>
                                       </table>
                                    </td>
                                    <td valign="top">
                                       <table width="358" border="0" cellpadding="0" cellspacing="0" align="right">
                                          <tr>
                                             <td style="font-family:'Montserrat', sans-serif; font-size:16px; color:#333; text-transform:uppercase; text-align:left; font-weight:500;">Opportunities</td>
                                          </tr>
                                          <tr>
                                             <td height="10"></td>
                                          </tr>
                                          <tr style="height: 159px;">
                                             <td style="background:#fff;">
                                                <table width="90%" align="center" border="0" cellpadding="7" cellspacing="0">
                                                   <tr>
                                                      <td height="3"></td>
                                                   </tr>
                                                   <tr>
                                                      <td style="font-family:'Montserrat', sans-serif; font-size:18px; color:#333; text-align:left; border-bottom:solid 1px #dae0e8;">Total Leads</td>
                                                      <td style="font-family:'Montserrat', sans-serif; font-size:30px; color:#333; text-align:right; border-bottom:solid 1px #dae0e8; font-weight:600">{{$leads['total_leads']}}</td>
                                                   </tr>
                                                   <tr>
                                                      <td height="3"></td>
                                                   </tr>
                                                   <tr>
                                                      <td style="font-family:'Montserrat', sans-serif; font-size:18px; color:#333; text-align:left; border-bottom:solid 1px #dae0e8; font-weight:300;">New Leads today</td>
                                                      <td style="font-family:'Montserrat', sans-serif; font-size:18px; color:#333; text-align:right; border-bottom:solid 1px #dae0e8;  font-weight:500;">{{$leads['today_leads']}}</td>
                                                   </tr>
                                                    <tr>
                                                      <td></td>
                                                    </tr>
                                                   <!-- <tr>
                                                      <td style="font-family:'Montserrat', sans-serif; font-size:18px; color:#333; text-align:left;  font-weight:300;">Leads close</td>
                                                      <td style="font-family:'Montserrat', sans-serif; font-size:18px; color:#333; text-align:right;  font-weight:500;">{{$leads['today_closed_leads']}}</td>
                                                   </tr> -->
                                                </table>
                                             </td>
                                          </tr>
                                          <tr>
                                             <td height="30"></td>
                                          </tr>
                                          <tr>
                                             <td style="font-family:'Montserrat', sans-serif; font-size:16px; color:#333; text-transform:uppercase; text-align:left;  font-weight:500;">Opportunity Source</td>
                                          </tr>
                                          <tr>
                                             <td height="10"></td>
                                          </tr>
                                          <tr>
                                             <td style="background:#fff;">
                                                <table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="ColumnCallSms">
                                                   <tr>
                                                      <td width="165" valign="top" class="templateColumnContainer">
                                                         <table width="100%" align="center" style="background:#fff; text-align:center;" border="0" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                               <td height="18"></td>
                                                            </tr>
                                                            <tr>
                                                               <td align="center">
                                                               	<canvas id="donut1" width="120" height="123" />
                                                               </td>
                                                            </tr>
                                                            <tr>
                                                               <td height="18"></td>
                                                            </tr>
                                                         </table>
                                                      </td>
                                                      <td width="165" valign="top" class="templateColumnContainer">
                                                         <table width="100%" align="center" style="background:#fff; text-align:center;" border="0" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                               <td height="10" colspan="2"></td>
                                                            </tr>
                                                            <tr>
                                                               <td valign="top" style="text-align:left;">
                                                                  <table width="100%" cellpadding="7" cellspacing="0">
                                                                     <tr>
                                                                        <td style="font-family:'Montserrat', sans-serif; font-size:16px; color:#333; font-weight:500;">{{$leads['today_leads']}} Leads</td>
                                                                        <td style="font-family:'Montserrat', sans-serif; font-size:16px; color:#333; font-weight:500;">Leads</td>
                                                                     </tr>
                                                                     @foreach($leads['sources_leads'] as $key=>$value)
																<tr>
																	<td style=" font-size:11px; color:{{$value['color']}};">
                                    @if ($key=='others')
                                    Others
                                    @else
                                    {{$value['source']}}
                                    @endif

                                  </td>
																	<td><span style=" font-size:11px; display:inline-block; width:25px; height:20px; line-height:20px; text-align:center; color:#fff; background:{{$value['color']}};">{{$value['count']}}</span></td>
																<tr>
																@endforeach		
                                                                  </table>
                                                               </td>
                                                            </tr>
                                                         </table>
                                                      </td>
                                                   </tr>
                                                </table>
                                             </td>
                                          </tr>
                                       </table>
                                    </td>
                                 </tr>
                              </table>
                           </td>
                        </tr>
                        <tr>
                           <td height="30"></td>
                        </tr>
                        <tr>
                           <td style="font-family:'Montserrat', sans-serif; font-size:16px; color:#333; text-transform:uppercase; text-align:left;  font-weight:500;">Appointments</td>
                        </tr>
                        <tr>
                           <td height="15"></td>
                        </tr>
                        <tr>
                           <td>
                              <table width="100%" border="0" cellpadding="0" cellspacing="0" align="left">
                                 <tr>
                                    <td valign="top">
                                       <table width="412" style="background:#fff" border="0" cellpadding="0" cellspacing="0">
                                          <tr>
                                             <td width="252" valign="top">
                                                <table width="100%" align="center" style="background:#fff; text-align:center;" border="0" cellpadding="0" cellspacing="0">
                                                   <tr>
                                                      <td height="20"></td>
                                                   </tr>
                                                   <tr>
                                                      <td align="center"><img src="/img/daily_work_summary/graph-appointments.jpg"></td>
                                                   </tr>
                                                   <tr>
                                                      <td height="5"></td>
                                                   </tr>
                                                   <tr>
                                                      <td style="font-family:'Montserrat', sans-serif; font-size:19px; color:#333; font-weight:600;">{{!empty($appointments_count)?$appointments_count:0}}</td>
                                                   </tr>
                                                   <tr>
                                                      <td style="font-family:'Montserrat', sans-serif; font-size:12px; color:#333; text-transform:uppercase;">Total Appointments</td>
                                                   </tr>
                                                   <tr>
                                                      <td height="20"></td>
                                                   </tr>
                                                </table>
                                             </td>
                                             <td width="160" valign="top" class="templateColumnContainer">
                                                <table width="100%" align="center" style="background:#fff;" border="0" cellpadding="0" cellspacing="0">
                                                   <tr>
                                                      <td height="30" class="item-gap"></td>
                                                   </tr>
                                                   <tr>
                                                      <td>
                                                         <table width="100%" cellpadding="0" cellspacing="0" class="templateColumnAppointments">
                                                            <tr>
                                                               <td width="25" valign="middle"><img src="/img/daily_work_summary/img-phone.gif"></td>
                                                               <td style="font-family:'Montserrat', sans-serif; font-size:12px; color:#333;  font-weight:300;" valign="middle">Phone</td>
                                                            </tr>
                                                            <tr>
                                                               <td height="5"></td>
                                                            </tr>
                                                            <tr>
                                                               <td width="25" valign="middle"><img src="/img/daily_work_summary/img-online.gif"></td>
                                                               <td style="font-family:'Montserrat', sans-serif; font-size:12px; color:#333;  font-weight:300;" valign="middle">Online</td>
                                                            </tr>
                                                         </table>
                                                      </td>
                                                   </tr>
                                                   <tr>
                                                      <td height="30"></td>
                                                   </tr>
                                                   <tr>
                                                      <td>
                                                         <table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" style="text-align:center;">
                                                            <tr>
                                                               <td style="font-family:'Montserrat', sans-serif; font-size:12px; color:#333;  font-weight:300;" class="last"><span style="font-family:'Montserrat', sans-serif; font-size:19px; color:#333; font-weight:600;">0</span> <br> PHONE</td>
                                                               <td style="font-family:'Montserrat', sans-serif; font-size:12px; color:#333;  font-weight:300;" class="last"><span style="font-family:'Montserrat', sans-serif; font-size:19px; color:#333; font-weight:600;">{{!empty($appointments_count)?$appointments_count:0}}</span> <br> ONLINE</td>
                                                            </tr>
                                                         </table>
                                                      </td>
                                                   </tr>
                                                </table>
                                             </td>
                                          </tr>
                                       </table>
                                    </td>
                                    <td>
                                       <table width="358" align="right" style="background:#fff" border="0" cellpadding="0" cellspacing="0">
                                          <tr>
                                             <td>
                                                <table width="290" align="center" border="0" cellpadding="0" cellspacing="0">
                                                   <tr>
                                                      <td height="51" colspan="4"></td>
                                                   </tr>
                                                   <tr>
											<td width="60"><img src="{{ url('/img/daily_work_summary/img-calls.gif')}}	"></td> <td style="text-align:center; font-size:12px; text-transform:uppercase;"><span style=" font-size:22px; color:#7eda8a; font-weight:600;">{{$call_sms['callCount']}}</span> <br> Calls</td> <td width="60"><img src="{{ url('/img/daily_work_summary/img-sms.gif')}}"></td> <td style="text-align:center; font-size:12px; text-transform:uppercase;"><span style=" font-size:19px; color:#1585b3; font-weight:600;">{{$call_sms['smsCount']}}</span> <br> SMS</td>
										</tr>	
                                                   <tr>
                                                      <td height="51" colspan="4"></td>
                                                   </tr>
                                                </table>
                                             </td>
                                          </tr>
                                       </table>
                                    </td>
                                 </tr>
                                 <tr>
                                    <td height="10"></td>
                                 </tr>
                              </table>
                           </td>
                        </tr>
                        <tr>
                           <td height="30"></td>
                        </tr>
                        @if(isset($analytics['metrics']['page_views']) && $analytics['metrics']['page_views']>0 )
                        <tr>
                           <td style="font-family:'Montserrat', sans-serif; font-size:16px; color:#333; text-transform:uppercase; text-align:left;  font-weight:500;">Website Traffic</td>
                        </tr>
                        <tr>
                           <td height="15"></td>
                        </tr>
                        <tr>
                           <td style="background:#fff;" class="website-analytics-graph">
                           	<canvas id="barChart" class="chart chart-line" chart-legend="false" width="464" height="245" style="max-height: 245px" s></canvas>

                        </td>
                    	
                        </tr>
                     	@endif
						 <tr>
                           <td height="30"></td>
                        </tr>
						
						<tr>
                           <td>
                           	@if(count($reviews)>0)
							<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" >
								<tr>
									<td style="font-family:'Montserrat', sans-serif; font-size:16px; color:#333; text-transform:uppercase; text-align:left;  font-weight:500;">New Reviews</td>
									<td align="right"><img src="/img/daily_work_summary/img-honest-doctor.gif"></td>
								</tr>
								<tr>
									<td colspan="3" style="background:#fff;">
										<table width="760" align="center" border="0" cellpadding="0" cellspacing="0" >
											@foreach ($reviews as $review)
											<tr>
											   <td colspan="2" height="15"></td>
											</tr>

											<tr>
												<td style="font-family:'Montserrat', sans-serif; font-size:16px; color:#333;font-weight:500;">{{$review['first_name']}} {{$review['last_name']}}</td>
												<td align="right">

											<div class="star-ratings-css">
											<div class="star-ratings-css-top" style="width: {{round(($review['rating']/5)*100)}}%"><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>
											<div class="star-ratings-css-bottom"><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>
										</div> <span style="color:#5ea231; font-size: 12px;">Verified</span></td>
											</tr>
											<tr>
											   <td colspan="2" height="5"></td>
											</tr>
											<tr>
											   <td colspan="2" style="font-family:'Montserrat', sans-serif; font-size:14px; color:#637383;">{{$review['user_review']}}</td>
											</tr>
											<tr>
											   <td colspan="2" height="5"></td>
											</tr>
											<tr>
											   <td colspan="2" style="font-family:'Montserrat', sans-serif; font-size:12px; color:#8a8a8a">{{date('D, M d',strtotime(date($review['created_at'])))}}</td>
											</tr>
											<tr>
											   <td colspan="2" height="15"></td>
											</tr>
											<tr>
												 <td colspan="2" style="background:#d0d7dd; height:1px;"></td>
											  </tr>
											  @endforeach
										</table>
									
									</td>
								</tr>
							</table>
							@endif
						   </td>
                        </tr>
						<tr>
                           <td height="10"></td>
                        </tr>

                     </table>
                  </td>
               </tr>
            </table>
		</td>
	</tr>
	
	<tr>
	   <td>
			 <table width="818" border="0" cellpadding="0" cellspacing="0" align="center">

			 	 <tr>
         <td>
            <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
			<tr>
		   <td height="20"></td>
		</tr>
               <tr>
                  <td style="font-family:'Montserrat', sans-serif; font-size:12px; line-height: 22px; color:#afb5bd;">*If you're not seeing any data than chances are you're not actioning your leads. Log in (or get your team to do so) to EG Now and action your leads so the stats can get updated and reflect accurately on your daily reports.</td>
				</tr>
				<tr>
		   <td height="20"></td>
		</tr>
			</table>
		</td>
	</tr>
				<tr>
                  <td style="font-family:'Montserrat', sans-serif; font-size:14px; color:#343a41; font-weight: 300;">To Your Success,</td>
				</tr>
				<tr>
				   <td height="20"></td>
				</tr>
				<tr>
					<td><img src="/img/daily_work_summary/sign.jpg"></td>
				</tr>
				<tr>
				   <td height="20"></td>
				</tr>
				<tr>
					<td style="font-family:'Montserrat', sans-serif; font-size:14px; color:#343a41; font-weight: 300;">Bob Mangat <br> CEO&amp;Founder</td>
				</tr>
				
			</table>
	   </td>
	</tr>
	
		<tr>
		   <td height="30"></td>
		</tr>
	 <tr>
	   <td align="center"><img src="/img/daily_work_summary/img-logo-footer.gif"></td>
	</tr>
	<tr>
		   <td height="10"></td>
		</tr>
	
	 
</table>
<script>
                $('#funnel_mail')
                    .highcharts({
                        chart: {
                            type: 'funnel',
                            marginRight: 0,
                             marginLeft: -70,
                            height: 250,
                            width: 400
                        },
                        title: {
                            text: '',
                            x: -40
                        },
                        colors: ['#297fb8', '#fb9265', '#9a59b7', '#258e4c'],
                        plotOptions: {
                            series: {
                                dataLabels: {
                                    enabled: true,
                                    format: '<b>{point.name}</b> ({point.y:,.0f})',
                                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black',
                                    softConnector: false
                                },
                                neckWidth: '22%',
                                neckHeight: '30%',
                                width: '60%'

                                //-- Other available options
                                // height: pixels or percent
                                // width: pixels or percent
                            }
                        },
                        legend: {
                            enabled: true
                        },
                        series: [{
                            name: 'Unique users',
                            data : [
                            	@foreach($funnel_data['leads_statics'] as $lead_s)
                                ['{{$lead_s['title']}}',{{$lead_s['count_lead']}}],
                                @endforeach
                            ]
                        }]
                    });
            </script>
            @if(isset($analytics['metrics']['page_views']) && $analytics['metrics']['page_views']>0 )
            <script type="text/javascript">
                var canvas = document.getElementById("barChart");
                var ctx = canvas.getContext('2d');

                // Global Options:
                Chart.defaults.global.defaultFontColor = 'black';
                Chart.defaults.global.defaultFontSize = 16;

                var data = {
                    labels: [@foreach ($analytics['visitor_report'] as $visits)
                                "{{$visits['date']}}",
                                @endforeach],
                    datasets: [{
                            label: "Visits",
                            fill: true,
                            //lineTension: 0.1,
                            backgroundColor: "#fcc5ae",
                            borderColor: "#fcc5ae",
                            borderCapStyle: 'butt',
                            borderDash: [],
                            borderDashOffset: 0.0,
                            borderJoinStyle: 'miter',
                            pointBorderColor: "#fcc5ae",
                            pointBackgroundColor: "#000",
                            pointBorderWidth: 1,
                            pointHoverRadius: 8,
                            pointHoverBackgroundColor: "#fcc5ae",
                            pointHoverBorderColor: "yellow",
                            pointHoverBorderWidth: 2,
                            pointRadius: 4,
                            pointHitRadius: 10,
                            // notice the gap in the data and the spanGaps: false
                            data: [@foreach ($analytics['visitor_report'] as $visits)
                                {{$visits['visits']}},
                                @endforeach],
                            spanGaps: false,
                        }

                    ]
                };

                // Notice the scaleLabel at the same level as Ticks
                var options = {

                    //scaleShowVerticalLines: false,
                    scaleShowHorizontallLines: false,
                    //responsive:true,

                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            stacked: true,
                            gridLines: {
                                display: true,
                                color: "rgba(255,99,132,0.2)"
                            }
                        }],
                        xAxes: [{
                            gridLines: {
                                display: false
                            }
                        }]
                    },
                    legend: {
                        display: false
                    },
                    tooltipTemplate: "<%= value %>",
                    gridLines: {
                        show: false
                    },
                    animation: {
				        duration: 0
				    }
                };
                var chart_colors = [{
                    fillColor: '#fcc5ae',
                    strokeColor: '#D2D6DE',
                    pointColor: '#000000',
                    pointStrokeColor: '#fff',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(148,159,177,0.8)'
                }];

                // Chart declaration:
                var myBarChart = new Chart(ctx, {
                    type: 'line',
                    data: data,
                    options: options,
                    colors: chart_colors,
                    legend: false
                });
            </script>
            @endif
   </body>
</html>

