<!DOCTYPE html>
<html>
<head>
  <title></title>
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,500" rel="stylesheet">
  <style type="text/css">
        @media only screen and (max-width: 480px) {
        table {
            width: 100% !important;
        }
        td {
            vertical-align: top;
            font-size: 13px!important;
        }
        .footer {
            font-size: 11px!important;
        }
    </style>
</head>
<body style="background-color: #fff;font-family: 'Montserrat', sans-serif">
<center >
  <table width="600" border="0" align="center" style="font-family:Arial; font-size:15px; color:#333;">
    <tr>
      <th style="background-color: #fff;text-align: center;padding: 8px 6px;">
         @if ($data['company_information']['logo'])
         <img src="{{url('/')}}/{!! $data['company_information']['logo'] !!}" width="auto" style="max-height: 100px;" >
      @else
       <img width="250" src="{{url('/')}}/img/logo.png" width="auto" style="max-height: 100px;">
     @endif
      </th>
    </tr>
    <tr>
      <td style="padding: 15px;font-size: 15px;color: #000;margin-top: 0px;line-height: 23px;">