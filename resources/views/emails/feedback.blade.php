@include('emails.mail_header', array('data' => $data))
	<tr>
		<td style="font-family:Arial; font-size:14px; border-bottom:solid 1px #ebebeb; padding-bottom:0;" align="center">
			<table width="310">
				<tr>
					<td style="background:#286090; width:125px; padding:7px 0; text-align:center;"><a href="{{$data['company_information']['site_url']}}/submit-review" style="color:#fff; font-size:16px; text-decoration:none; display:block;">Yes</a></td>
					<td width="10"></td>
					<td style="background:#286090; width:125px; padding:7px 0;  text-align:center;"><a href="{{$data['company_information']['site_url']}}/thank-you" style="color:#fff;  font-size:16px; text-decoration:none; display:block;">No</a></td>
				</tr>
			</table>
			
		</td>
	</tr>
	
	<tr>
		<td style="font-family:Arial; font-size:14px;">Thanks for visiting us at our office today. We hope you're grinning from ear to ear! <br> <br>

Your satisfaction is important to us, so any feedback help us continue striving for exceptional service.<br> <br>

See you next time.</td>
	</tr>
	<tr>
		<td style="font-family:Arial; font-size:14px;">Thanks, <br> {{$data['company_information']['name']}}</td>
	</tr>
	<tr>
		<td style="font-family:Arial; padding:20px; font-size:13px; text-align:center; border-top:solid 1px #ebebeb;">{{$data['company_information']['name']}} | {{$data['company_information']['address']}} </td>
	</tr>


@include('emails.mail_footer', array('data' => $data))
