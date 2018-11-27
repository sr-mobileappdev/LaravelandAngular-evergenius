<!DOCTYPE html>
<html>
<head>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.min.js"></script>
  	<title></title>
  	<script>

  	$(document).ready(function(){
		var donutEl = document.getElementById("donut").getContext("2d");
		var apptdata = [
		<?php 
			if(!empty($appointments_sources)){  
				foreach($appointments_sources as $source){
					echo $source['total'].",";
				}
			}
			else{
				echo '20,'.'30';
			}
		?>];
		var data = {
					"datasets":[
								{
									"label":"My First Dataset",
									"data":apptdata,
									"backgroundColor":["#7CDB89","#F99265"]
								}
						]
					};
		var options= {
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
			cutoutPercentage : 70,
			maintainAspectRatio : false
        }
        var myDoughnutChart = new Chart(donutEl, { type: 'doughnut', data: data, options: options });

        var donutEl1 = document.getElementById("donut1").getContext("2d");
		var dataleads = [ <?php
			foreach($leads['leads'] as $key=>$value){
				echo "'".$value['count']."',";				
			}
		?> ];
		var dataleadscolor = [ <?php
			foreach($leads['leads'] as $key=>$value){
				echo "'".$value['color']."',";				
			}
		?> ];
		var data1 = {
					"datasets":[
								{
									"label":"My First Dataset",
									"data":dataleads,
									"backgroundColor":dataleadscolor
								}
						]
					};
		var options1= {
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
			cutoutPercentage : 70,
			maintainAspectRatio : false
        }
        var myDoughnutChartq = new Chart(donutEl1, { type: 'doughnut', data: data1, options: options1 });

  	});
  	</script>
  	<style type="text/css">
	 @media only screen and (max-width: 480px){
        table{
            width:100% !important;
        }
		
		.templateColumnContainer{
			display:block !important;
            width:100% !important;
		}
		
		.templateColumnAppointments{
			width:25%!important;
			margin:0 auto;
		}
		
		.ColumnCallSms{
			padding:0 10px;
			}
		
		.item-gap{
			display:none;
		}
			
		td{
			font-size:13px!important;
		}
		
		.footer{
			font-size:11px!important;
		}
		
		.templateColumnContainerWrapper{
			margin-bottom:20px;
		}
	
		.last{
			padding-bottom:20px;
			}
		}

  	</style>
</head>
<body style=" background:#eceef2;">

<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" style="font-family:Arial; font-size:15px; color:#333;">
	<tr>
		<td height="10"></td>
	</tr>
	<tr>
		<td align="center"><img src="{{ url('/img/daily_work_summary/logo.png') }}"></td>
	</tr>
	<tr>
		<td height="10"></td>
	</tr>	
	<tr>
		<td style="border-bottom:solid 1px #c2cad3; overflow:hidden;" height="1"></td>
	</tr>	
	
	<tr>
		<td height="20"></td>
	</tr>
	
	<tr>
		<td style="text-align:center; font-family:Arial; font-size:26px; color:#333; font-weight:600;">Daily Work Summary</td>
	</tr>
	<tr>
		<td height="10"></td>
	</tr>
	<tr>
		<td style="text-align:center; font-family:Arial; font-size:18px; color:#333;"></td>
	</tr>
	<tr>
		<td height="10"></td>
	</tr>
	<tr>
		<td style="text-align:center; font-family:Arial; font-size:18px; color:#333;">{{ date("F j, Y")}}</td>
	</tr>
	<tr>
		<td height="40"></td>
	</tr>
	
		<tr>
		<td>
			<table border="0" align="center" width="600" cellpadding="0" cellspacing="0">
				<tr>
					<td style="font-family:Arial; font-size:16px; color:#333; text-transform:uppercase; text-align:left;">Appointments</td>
					<td></td>
				</tr>	
				<tr>
					<td height="5" colspan="2"></td>
				</tr>
				<tr>
					<td valign="top" width="50%" class="templateColumnContainer templateColumnContainerWrapper">
						<table width="290" style="background:#fff" border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td width="165" valign="top" class="templateColumnContainer">
									<table width="100%" align="center" style="background:#fff; text-align:center;" border="0" cellpadding="0" cellspacing="0">
										<tr>
											<td height="20"></td>
										</tr>
										<tr>
											<td align="center"><canvas id="donut" height="92" width="94" /></td>
										</tr>
										<tr>
											<td height="5"></td>
										</tr>
										<tr>
											<td style="font-family:Arial; font-size:18px; color:#333; font-weight:bold;">{{!empty($appointments_count)?$appointments_count:0}}</td>
										</tr>
										<tr>
											<td style="font-family:Arial; font-size:12px; color:#333; text-transform:uppercase;">Total Appointments</td>
										</tr>
										<tr>
											<td height="20"></td>
										</tr>
									</table>
								</td>
								<td width="125" valign="top" class="templateColumnContainer">
									<table width="100%" align="center" style="background:#fff;" border="0" cellpadding="0" cellspacing="0">
										<tr>
											<td height="30" class="item-gap"></td>
										</tr>
										<tr>
											<td>
												<table width="100%" cellpadding="0" cellspacing="0" class="templateColumnAppointments">
													<tr>
														<td width="25" valign="middle"><img src="{{ url('/img/daily_work_summary/img-phone.gif')}}"></td>
														<td style="font-family:Arial; font-size:14px; color:#333;" valign="middle">Phone</td>
													</tr>
													<tr>
														<td height="5"></td>
													</tr>
													<tr>
														<td width="25" valign="middle"><img src="{{ url('/img/daily_work_summary/img-online.gif')}}"></td>
														<td style="font-family:Arial; font-size:14px; color:#333;" valign="middle">Online</td>
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
														@foreach($appointments_sources as $source)
														<td style="font-family:Arial; font-size:14px; color:#333;" class="last"><span style="font-family:Arial; font-size:18px; color:#333; font-weight:bold;">{{$source['total']}}</span> <br> {{$source['scheduling_method']}}</td>
														@endforeach
													</tr>
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
					<td valign="top"  width="50%" class="templateColumnContainer templateColumnContainerWrapper">
						<table width="290" align="right" style="background:#fff" border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td>
									<table width="250" align="center" border="0" cellpadding="0" cellspacing="0" class="ColumnCallSms">
										<tr>
											<td height="51" colspan="4"></td>
										</tr>
										<tr>
											<td width="60"><img src="{{ url('/img/daily_work_summary/img-calls.gif')}}	"></td> <td style="text-align:center; font-size:12px; text-transform:uppercase;"><span style="font-family:Arial; font-size:18px; color:#7eda8a; font-weight:bold;">{{$call_sms['callCount']}}</span> <br> Calls</td> <td width="60"><img src="{{ url('/img/daily_work_summary/img-sms.gif')}}"></td> <td style="text-align:center; font-size:12px; text-transform:uppercase;"><span style="font-family:Arial; font-size:18px; color:#1585b3; font-weight:bold;">{{$call_sms['smsCount']}}</span> <br> SMS</td>
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
			</table>
		</td>
	</tr>
	<tr>
		<td height="30"></td>
	</tr>
	
	<tr>
		<td>
			<table border="0" align="center" width="600" cellpadding="0" cellspacing="0">
				<tr>
					<td valign="top" width="40%" class="templateColumnContainer templateColumnContainerWrapper">
						<table width="228" border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td style="font-family:Arial; font-size:16px; color:#333; text-transform:uppercase; text-align:left;">Leads</td>
							</tr>
							<tr>
								<td height="10"></td>
							</tr>
							<tr>
								<td valign="top" class="templateColumnContainer" style="background:#fff;height:225px">
									<table width="90%" align="center" border="0" cellpadding="7" cellspacing="0">
										<tr>
											<td height="10"></td>
										</tr>
										<tr>
											<td style="font-family:Arial; font-size:16px; color:#333; text-align:left; border-bottom:solid 1px #dae0e8;">Total Leads</td>
											<td style="font-family:Arial; font-size:24px; color:#333; text-align:right; border-bottom:solid 1px #dae0e8; font-weight:bold;">{{$leads['total_leads']}}</td>
										</tr>
										<tr>
											<td height="10"></td>
										</tr>
										<tr>
											<td style="font-family:Arial; font-size:14px; color:#333; text-align:left; border-bottom:solid 1px #dae0e8;">New Leads today</td>
											<td style="font-family:Arial; font-size:16px; color:#333; text-align:right; border-bottom:solid 1px #dae0e8;">{{$leads['today_leads']}}</td>
										</tr>
										<tr>
											<td style="font-family:Arial; font-size:14px; color:#333; text-align:left;">Leads close</td>
											<td style="font-family:Arial; font-size:16px; color:#333; text-align:right;">{{$leads['today_closed_leads']}}</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
					<td valign="top"  width="60%" class="templateColumnContainer templateColumnContainerWrapper">
						<table width="350" align="right" border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td style="font-family:Arial; font-size:16px; color:#333; text-transform:uppercase; text-align:left;">Lead Source</td>
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
														<td height="20" colspan="2"></td>
													</tr>
													<tr>
														<td valign="top" style="text-align:left;">
															<table width="100%" cellpadding="7" cellspacing="0">
																
																<tr>
																	<td style="font-family:Arial; font-size:15px; color:#333; font-weight:bold;">58 Leads</td>
																	<td style="font-family:Arial; font-size:15px; color:#333; font-weight:bold;">Leads</td>
																</tr>
																@foreach($leads['leads'] as $key=>$value)
																<tr>
																	<td style="font-family:Arial; font-size:13px; color:{{$value['color']}};">{{$key}}</td>
																	<td><span style="font-family:Arial; font-size:12px; display:inline-block; width:25px; height:20px; line-height:20px; text-align:center; color:#fff; background:{{$value['color']}};">{{$value['count']}}</span></td>
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
		<td>
			<table width="600" cellspacing="0" cellpadding="0" border="0" align="center">
				<tr>
					<td class="templateColumnContainer templateColumnContainerWrapper" width="40%" valign="top">
						<table width="228" cellspacing="0" cellpadding="0" border="0">
							<tr>
								<td style="font-family:Arial; font-size:16px; color:#333; text-transform:uppercase; text-align:left;">avg. lead handling time</td>
							</tr>
							<tr>
								<td height="10"></td>
							</tr>
							<tr>
								<td class="templateColumnContainer" style="background:#fff;height:218px" valign="top">
									<table width="90%" cellspacing="0" cellpadding="7" border="0" align="center">
										<tr>
											<td height="10"></td>
										</tr>
										<tr>
											<td align="center"><img src="{{ url('/img/daily_work_summary/img-time.jpg')}}"></td>
										</tr>
										
										<tr>
											<td>
												<table width="100%" cellspacing="0" cellpadding="0" border="0">
													<tr>
														<td style="font-family:Arial; font-size:16px; color:#333; text-align:left; padding-top:10px; border-top:solid 1px #dae0e8;">Avg. Time</td>
														<td style="font-family:Arial; font-size:24px; color:#333; text-align:center; padding-top:10px; border-top:solid 1px #dae0e8; font-weight:bold; line-height:14px;">{{$avtd_value!=null?$avtd_value:0}}</span></td>
													</tr>
												</table>
											</td>
										</tr>
										
									</table>
								</td>
							</tr>
						</table>
					</td>
					<td class="templateColumnContainer templateColumnContainerWrapper" width="60%" valign="top">
						<table width="350" cellspacing="0" cellpadding="0" border="0" align="right">
							<tr>
								<td style="font-family:Arial; font-size:16px; color:#333; text-transform:uppercase; text-align:left;">lead stages</td>
							</tr>
							
							<tr>
								<td height="10"></td>
							</tr>
							<tr>
								<td style="background:#fff;height:218px" valign="top">
									<table class="ColumnCallSms" width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
										<tr>
											<td height="10"></td>
										</tr>
										<tr>
											<td>
												<table width="90%" align="center" cellspacing="0" cellpadding="0" border="0">
													<tr>
														<td width="90%"  style="font-family:Arial; font-size:14px; color:#333; padding:10px 0;">Stage Name</td>
														<td width="10%"  style="font-family:Arial; font-size:14px; color:#333; padding:10px 0;">Leads</td>
													</tr>
													@foreach($stages as $stage)
													<tr>
														<td style="font-family:Arial; font-size:16px; color:#333; padding:10px 0; border-bottom:solid 1px #dae0e8;">{{$stage['title']}}</td>
														<td style="font-family:Arial; font-size:16px; color:#333; padding:10px 0; border-bottom:solid 1px #dae0e8;">{{$stage['total_count']}}</td>
													</tr>
													@endforeach
												</table>
											</td>
										</tr>
										<tr>
											<td height="7"></td>
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
		<td style="font-family:Arial; font-size:15px; color:#333;">To Your Success,</td>
	</tr>
	<tr>
		<td height="20"></td>
	</tr>
	<tr>
		<td><img src="{{ url('/img/daily_work_summary/sign.jpg')}}"></td>
	</tr>
	<tr>
		<td height="20"></td>
	</tr>
	<tr>
		<td style="font-family:Arial; font-size:15px; color:#333;">Bob Mangat <br> CEO&amp;Founder</td>
	</tr>
	<tr>
		<td height="30"></td>
	</tr>
	<tr>
		<td style="border-top:solid 1px #c2cad3; overflow:hidden;" height="1"></td>
	</tr>
	<tr>
		<td height="5"></td>
	</tr>
	<tr>
		<td align="center"><img src="{{ url('/img/daily_work_summary/footer-logo.png')}}"></td>
	</tr>
</table>
</body>
</html>