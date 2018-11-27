<!DOCTYPE html>
<html>
<head>
  <title></title>
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,500" rel="stylesheet"> 
</head>
<body style="background-color: ##f1f1f1;font-family: 'Montserrat', sans-serif">
<center style="padding-top: 40px;padding-bottom: 40px;">
  <table cellspacing="0" cellpadding="0" style="box-shadow: 0 0 6px rgba(0, 0, 0, 0.17);max-width: 100%; width: 600px;background-color: #FFF; margin: 0 auto;text-align: left;border: 1px solid #edeaea;">
    <tr>
      <th style="background-color: #eceef2;text-align: center;padding: 40px 6px;">
      <img width="250" src="{{url('/')}}/img/logo.png">
      </th>
    </tr>
    <tr>
      <td style="padding: 15px;">
        <table cellpadding="0" cellspacing="0" width="100%" align="center" style="background:#ffffff; padding:20px;">
        <tr>
          <td colspan="2" style="font-family:Arial; padding:5px 0; font-size:24px; text-align:center; border-bottom:solid 1px #ddd;">Forgot your Password?</td>
        </tr>
        <tr>
        <td style="text-align: center;font-size: 18px;padding: 38px;">
We received a request to reset your EverGenius password for {{$email}}. To reset your password, please click the button below:
        </td>
        </tr>
       
          <tr>
            <td class="button">
              <div style="text-align: center;">             

              <!--[if mso]>
                <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ URL::to('/#/reset-password/'. $email . '/' . $token) }}" style="height:45px;v-text-anchor:middle;width:155px;" arcsize="15%" strokecolor="#ffffff" fillcolor="#ff6f6f">
                  <w:anchorlock/>
                  <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:14px;font-weight:regular;">Reset Password</center>
                </v:roundrect>
              <![endif]-->

              <a class="button-mobile" href="{{ URL::to('/#/reset-password/'. $email . '/' . $token) }}"
              style="background-color:#ff6f6f;border-radius:5px;color:#ffffff;display:inline-block;font-family:'Cabin', Helvetica, Arial, sans-serif;font-size:14px;font-weight:regular;line-height:45px;text-align:center;text-decoration:none;width:155px;-webkit-text-size-adjust:none;mso-hide:all;">Reset Password</a></div>
            </td>
          </tr>
      </table>

      </td>
    </tr>
    <tr>
      <td style="background-color:#03101e;text-align: center;font-size: 13px;color: #FFF;padding: 15px;">&copy; EverGenius.com {{date('Y',time())}}</td>
    </tr>
  </table>
</center>
</body>
</html>