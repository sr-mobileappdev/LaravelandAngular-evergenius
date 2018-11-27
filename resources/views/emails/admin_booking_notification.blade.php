@include('emails.mail_header', array('data' => $data))
  Patient Name: {{$data['input']['first_name']}} {{$data['input']['last_name']}}<br>
  Email: {{$data['input']['email']}}<br>
  Insurance Group: {{$data['input']['insurance_provider']}}<br>
  Appointment Duration: {{$data['duration']}}<br>
  Notes: {{$data['input']['notes']}}<br>
@include('emails.mail_footer', array('data' => $data))
