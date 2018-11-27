@include('emails.mail_header', array('data' => $data))
  {!! $data['content_data'] !!}
@include('emails.mail_footer', array('data' => $data))