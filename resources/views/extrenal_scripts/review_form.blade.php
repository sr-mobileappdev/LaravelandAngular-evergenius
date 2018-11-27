<!DOCTYPE html>
<html lang="en">

<head>
    <title>Review Form</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel='stylesheet' href='{{$website_link}}/css/jquery.fileupload.css'>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="{{$website_link}}/js/jquery.validate.min.js"></script>
    <script src="../../js/star-rating.js"></script> 
    @php
        $itemarray = array();
        foreach($form_style as $key=>$value){
            $key = str_replace('amp;','',$key);
            $itemarray[$key] = $value;
        }
        $form_style = $itemarray;
    @endphp
    <style type="text/css">
        body{
            margin-top: 10px !important;
        }
        .review-form {
            width: calc(100% - 40px);
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0, 0, 0, .2);
        }
        .display-hide {
            display: none;
        }
        .review-form .row {
            margin-bottom: 20px;
        }
        .review-form .row .row {
            margin-bottom: 0;
        }
        .form-control {
            height: 40px;
            line-height: 40px;
        }
        .form-control.textarea {
            height: 150px;
            resize: none;
        }

        .buttons{padding-top: 10px;}
        .buttons .btn {
            margin: 0 5px;
        }
        .review-form label {
            @if(isset($form_style['labelColor'])) color: #{{$form_style['labelColor']}};
            @else color: #000000;
            @endif
        }
        /* Review form */
        
        .review-form {
            @if (isset($form_style['bodyText']))
            font-size: {{$form_style['bodyText']}}px;
            @else 
            font-size: 15px;
            @endif 
        }
        .review-form {
            @if(isset($form_style['bodyBackgroundColor'])) 
            background-color: #{{$form_style['bodyBackgroundColor']}};
            @else background-color: #ffffff;
            @endif
        }
        .review-form .submit-btn {
            @if(isset($form_style['submitButtonBackgroundColor'])) 
            background-color: #{{$form_style['submitButtonBackgroundColor']}};
            @else 
            background-color: #ed1d26;
            @endif 
            @if(isset($form_style['submitFontColor']));
            color: #{{$form_style['submitFontColor']}};
            @else 
            color: #ffffff;
            @endif
        }
        .review-form .cancel-btn {
            @if(isset($form_style['cancelButtonBackground'])) 
            background-color: #{{$form_style['cancelButtonBackground']}};
            @else 
            background-color: #C1C1C1;
            @endif 
            @if(isset($form_style['cancelButtonFontColor'])) 
            color:#{{$form_style['cancelButtonFontColor']}};
            @else color: #ffffff;
            @endif
        }

        .audio-upload-block{
            border-right: solid 1px #a2a9b3;
            padding-right: 16%;
        }
        .upload-av .fa {
             top: 50%;
             left: 20px;
             opacity: .1;
             font-size: 30px;
             width: 65px;
             height: 65px;
             line-height: 58px;
             text-align: center;
             border-radius: 100%;
             border: solid 2px #333;
             cursor: pointer;
         }
         .upload-av .fa.fa-check-circle {
             border-radius: 0;
             border: none;
             width: auto;
             height: auto;
             color: #4cae4c;
             opacity: 1;
             vertical-align: middle;
         }
         .upload-av .fa.uploaded-icon {
             color: #4cae4c;
             opacity: 1;
             border-color: #4cae4c;
         }
         .upload-av span {
             display: inline-block;
             vertical-align: middle;
         }
         .record_video_block,
         .capture_image_block,
         .capture_audio_block {
             display: none;
         }
         .uploded_image {
             display: none;
         }
         .review-form label.error,
         .errors {
             color: #ff1b1b;
             font-size: 13px;
         }
         #progress,
         #progressvideo,
         #progressaudio {
             display: none;
             margin-top: 10px;
         }
         .fileinput-button {
             vertical-align: middle;
         }
         .fileinput-button input {
             width: 100%;
             height: 100%;
             font-size: inherit!important;
         }
         .upload-av-text {
             display: inline-block;
             vertical-align: middle;
             padding-left: 10px;
         }
         .required_ic {
             color: #ff1b1b;
             font-size: 17px;
         }
         .btn-info,
         .btn-primary {
             border: none;
         }
         .uploded_image_col {
             position: relative;
             float: left;
             border: 1px solid #CCC;
         }
         .uploded_image_col .del-image {
             position: absolute;
             top: -8px;
             right: -7px;
             width: 20px;
             background-color: #e48e8e;
             height: 20px;
             /* border: 1px solid #CCC; */
             
             border-radius: 50%;
             line-height: 19px;
             text-align: center;
             color: #FFF;
             cursor: pointer;
         }
         .del-image {
             display: none;
         }

  .review-form h3 {
    margin: 0;
    padding: 0;
    font-size: 22px;
}
.review-form .row {
    margin-bottom: 7px;
    margin-top: 7px;
}
.form-control.textarea {
    height: 80px;}

    .form-control {
    height: 32px;
    line-height: 32px;
}
.upload-av .fa {
    top: 50%;
    left: 20px;
    opacity: .3;
    font-size: 24px;
    width: 45px;
    height: 45px;
    line-height: 38px;
    text-align: center;
    border-radius: 100%;
    border: solid 2px #333;
    cursor: pointer;
}
.border1{
    border-bottom:1px solid #ccc;padding-bottom:20px
}

.review-header {
    font-size: 30px;
    color: #162840;
    position: relative;
    padding:40px;
}

.review-header .top-rated {
    position: absolute;
    top: 10px;
    right: 40px;
}


.review-content .subtitle {
    font-size: 16px;
    font-weight: 600;
    padding:10px 0;
}

.upload-photo{
    padding-bottom: 10px;
    border-bottom: solid 1px #a2a9b3;
}

@media screen and (max-width:480px){
    .review-title{text-align:center;}
    .review-title .logo{margin:0 auto; display:block!important;}

  .review-header{
  padding:20px;
 }
 
 .review-header .top-rated{
  position:static;
  display:block;
  text-align:center;
  width:20%;
    margin:0 auto;
 }

 .review-header .top-rated img{
    max-width:100%;
}
 
 .review-content{
  padding:15px 20px;
 }
 
 .review-content .col-xs-4, .review-content .col-xs-8{
  width:100%;
 }
 
 .audio-upload-block{
  border:none;
  margin-bottom:10px;
 }
}
@media screen and (max-width:375px){
 .review-form .submit-btn{
  margin-bottom:15px;
 }
}
.progress{ margin-top: 5px !important:margin-bottom:5px !important; }
.progress-bar.progress-bar-success{
    height: 100% !important;
}
.modal-sm {
    width: 375px;
}
.modal-dialog{
 margin: 30px auto;
}

    </style>
    <script type="text/javascript" src="{{$website_link}}/js/rating.js"></script>
    <link rel="stylesheet" type="text/css" href="{{$website_link}}/css/rating.css" />
</head>

<body>
    <form id="review_web_form" method="POST" action="">

        <div class="review-form">

        <div class="review-header border1">
            <span class="top-rated"><img src="/images/img-top-rated.png" alt="Top Rated Doctor"></span>
            <h4 class="review-title">
                <span class="logo" style="display: inline-block;vertical-align: middle;width: 150px;">
                <img src="{{$company_details['logo']}}" style="max-width: 100%;"></span>
                {{$company_details['name']}}
            </h4>
        </div>

        <div class="review-content">
            <div class="row">
                <div class="col-sm-12 " >
                @if(isset($review_settings['title']))
                <div class="subtitle">Please rate your experience with {{$company_details['name']}}. Your feedback helps other patients like yourself find the right doctor for their care.</div> @endif @if(isset($review_settings['description']))
                <p>{!! $review_settings['description'] !!}</p>
                @endif
                </div>
            </div>
            <div class="row">
            <div class="col-sm-12">
                <div class="review-questinos">
                <ul id="reviewratingq">
                @php $i = 1; @endphp
                @foreach ($review_questions as $review_question)
                <li>
                <div class="ques-col">
                <i class="icon-ques{{$i}}"></i>{{$review_question['label']}}
                </div>
                <div class="add-rating-col">
                <div class='rating-stars'>
                <ul class='stars'>
                <li class='star' title='Poor' data-value='1' data-type="{{$review_question['map_to']}}">
                <i class='fa fa-star fa-fw'></i>
                </li>
                <li class='star' title='Fair' data-value='2' data-type="{{$review_question['map_to']}}">
                <i class='fa fa-star fa-fw'></i>
                </li>
                <li class='star' title='Good' data-value='3' data-type="{{$review_question['map_to']}}">
                <i class='fa fa-star fa-fw'></i>
                </li>
                <li class='star' title='Excellent' data-value='4' data-type="{{$review_question['map_to']}}">
                <i class='fa fa-star fa-fw'></i>
                </li>
                <li class='star' title='WOW!!!' data-value='5' data-type="{{$review_question['map_to']}}">
                <i class='fa fa-star fa-fw'></i>
                </li>
                </ul>
                </div>
                </div>
                </li>
                @php $i++; @endphp
                @endforeach
                </ul>
               </div>
                </div>
            </div>

             @if(isset($review_settings['text_enable'])  && $review_settings['text_enable']==1)
            <div class="row">
                <div class="col-sm-12">
                    <label>Write Your Review Here <span class="required_ic">*</span></label>
                    <textarea class="form-control textarea"  name="review" required=""></textarea>
                    

                </div>
            </div>
             @endif

            <div class="row">
                <div class="col-xs-6">
                    <label>First Name <span class="required_ic">*</span></label>
                    <input type="text" required="" name="first_name" class="form-control">
                </div>
                <div class="col-xs-6">
                    <label>Last Name <span class="required_ic">*</span></label>
                    <input type="text" required="" name="last_name" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-xs-6">
                    <label>Email <span class="required_ic">*</span></label>
                    <input type="email" required="" name="email" class="form-control">
                </div>
                <div class="col-xs-6">
                         <label>Phone Number <span class="required_ic">*</span></label>
                         <input required="" name="phone" class="form-control" type="tel">
                </div>
            </div>

            @if(isset($review_settings['testimonial_one_pic'])  && $review_settings['testimonial_one_pic']==1)
            <div class="row">
                <div class="col-sm-12">
                    <div class="upload-photo">
                    <label>Add Photo</label>
                    <div class="row">
                        <div class="col-sm-6">
                            <!-- <div class="upload-av upload-image capture_image"><i class="fa fa-picture-o"></i> <span>Upload <br>Image</span>
                            </div> -->
                             <span class="upload-av fileinput-button img-upload-block">
                                <i class="fa fa-photo"></i>
                                <input id="fileupload" type="file" name="upload_image" accept="image/*" title="Upload Image">
                                <span class="upload-av-text">Upload Photo</span>                                     
                            </span>
                            
                            <div id="progress" class="progress">
                                     <div class="progress-bar progress-bar-success" style="height:0.5em;"></div>
                            </div>
                            <div class="errors img-errors">
                            </div>

                            <div class="uploded_image_col">
                            <img src="" class="uploded_image" width="150px" >
                            <span class="del-image">x</span>
                            </div>

                            <!-- <div class="capture_image_block">
                                <photobooth id='reviewImage' data-app-id='a-a666eaf0-7e61-0135-630a-06d01c25992c'></photobooth>
                            </div> -->
                            </div> 
                        </div>
                    </div>
                </div>
            </div>
            @endif

             @if((isset($review_settings['audio_enable']) && $review_settings['audio_enable']==1) || (isset($review_settings['video_enable']) && $review_settings['video_enable']==1))
            <div class="row">
                <div class="col-sm-12">
                    <label>Upload Your Review</label>
                    <div class="row">
                        <div class="col-xs-4">
                         @if(isset($review_settings['audio_enable']) && $review_settings['audio_enable']==1)
                            <span class="upload-av fileinput-button audio-upload-block">
                                    <i class="fa fa-music"></i>
                                    <!-- The file input field used as target for the file upload widget -->
                                    <input id="fileuploadaudio" type="file" name="upload_audio"  accept="audio/*" id="capture" capture="microphone" title="Upload Audio">
									<span class="upload-av-text uploaded-audio">Upload Audio</span>
									<i class="fa fa-check-circle hide check-audio"></i>
                            </span>
							
                            <div id="progressaudio" class="progress">
                                     <div class="progress-bar progress-bar-success" style="height:0.5em;"></div>
                            </div>
                            <div class="errors aud-errors">
                            </div>
                         @endif   
                        </div>
                        <div class="col-xs-8">
                        @if(isset($review_settings['video_enable']) && $review_settings['video_enable']==1)
                          <span class="upload-av fileinput-button video-upload-block">
                                    <i class="fa fa-video-camera"></i>
                                  
                                    <!-- The file input field used as target for the file upload widget -->
                                    <input id="fileuploadvideo" type="file" name="upload_video"  accept="video/*" id="capture" capture="camcorder" title="Upload Video">
									<span class="upload-av-text uploaded-video">Upload Video (Max Size 50 MB)</span>
									<i class="fa fa-check-circle hide check-video"></i>
                            </span>
							
                            <div id="progressvideo" class="progress">
                                     <div class="progress-bar progress-bar-success" style="height:0.5em;"></div>
                            </div>
                            <div class="errors vid-errors">
                            </div>

                         @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
             
           

            <div class="row">
                <div class="col-sm-12">
                    <div class="buttons text-center">
                        <input type="hidden" name="site_key" value="{{$company_details['api_key']}}">
                        @php
                        if(isset($form_style['provider_id'])){
                            @endphp
                        <input type="hidden" name="provider_id" value="{{$form_style['provider_id']}}">
                            @php    
                        }
                        @endphp
                        <input type="hidden" name="video_url" value="">
                        <input type="hidden" name="audio_url" value="">
                        <input type="hidden" name="img_url" value="">
                        <input type="hidden" name="r_quality"  value="0">
                        <input type="hidden" name="r_value" value="0">
                        <input type="hidden" name="r_timeliness" value="0">
                        <input type="hidden" name="r_experience" value="0">
                        <input type="hidden" name="r_satisfaction" value="0">
                        <input type="submit" class="btn btn-primary submit-btn submit_review" value="Submit">
                        <button type="button" onclick="resetForm();" class="btn btn-info cancel-btn">Cancel</button>
                    </div>
                    <div class="alert alert-success display-hide success_review" style="margin-top: 10px;
"></div>
                    <div class="alert alert-danger display-hide success_error" style="margin-top: 10px;
"></div>
                </div>
            </div>
        </div>


        <div class="review-bottom">
        <img src="/images/img-powered-by.png" alt="Powered By Honest Doctor">
      </div>

      <div class="review-footer">
        <p>By submitting this form, you agree to the terms and conditions of the Healthgrades User Agreement, Editorial Policy, and Privacy Policy, and certify you or a family member has had contact with this practice. You also confirm that you do not work for, are not in competition with and are not related to the provider in this review.</p>

        <p>Email Address &amp; Phone Number</p>

        <p>Your email address or phone number will NOT appear publicly with your review. When you submit your survey, we will 
either email or text you a verification link.</p>
      </div>

         </div>  
    </form>
    <div class="modal fade success-popup" id="reviewmessagebx" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
      <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            <h4 class="modal-title" id="myModalLabel">Thank You!</h4>
          </div>
          <div class="modal-body text-center">
              <p class="lead">Review has been submitted successfully.</p>
          </div>
          
        </div>
      </div>
    </div>


<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="{{$website_link}}/js/jquery.ui.widget.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="{{$website_link}}/js/jquery.iframe-transport.js"></script>
<!-- The basic File Upload plugin -->
<script src="{{$website_link}}/js/jquery.fileupload.js"></script>



    <script type="text/javascript">
        $.validator.addMethod('email', function (value) { 
    return /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,5}$/.test(value); 
}, 'Please enter a valid email address.');

        $(function() {
            $('.rating').rating();
            $('.ratingEvent').rating({
                rateEnd: function(v) {
                    $('#result').text(v);
                }
            });
            $("#review_web_form").validate({
                rules: {
                    first_name:{
                      required: true,
                      maxlength: 50
                    },
                    last_name:{
                      required: true,
                      maxlength: 50
                    },
                    email: {
                    required: true,
                      email: true
                    },
                    review:{
                        maxlength: 1000
                    },
                    phone:{
                        maxlength: 12,
                        minlength: 10,
                        number: true

                    }

                },
                submitHandler: function(form) {
                    $(".submit_review").prop('disabled', true);
                    var send_url = "{{$website_link}}/scripts/widgets/form/review-store";
                    $.ajax({
                        url: send_url,
                        type: 'POST',
                        data: $("#review_web_form").serialize(),
                        timeout: 3000,
                        success: function(res) {
                            $('#reviewmessagebx').modal('show');
                            $(".submit_review").prop('disabled', false);
                            //$(".success_review").show();
                            //$(".success_review").html(res.data);
                            setTimeout(function() {$('#reviewmessagebx').modal('hide');}, 8000);
                            $("#review_web_form")[0].reset();
                            $(".record_video_block, .capture_image_block, .capture_audio_block").hide();
                            $(".record_video, .capture_image, .capture_audio").show();
                            resetForm();
                        },
                        error: function(err) {
                            
                            $(".submit_review").prop('disabled', false);
                            var error_s = JSON.parse(err.responseText);
							if(error_s.errors!='undefined' && error_s.errors.message[0]!='undefined' && error_s.errors.message[0].length>0){
								$(".success_error").show();
								$(".success_error").html(error_s.errors.message[0]);
							}
							

                        }
                    });
                    return false;
                }
            });
        });
    </script>


<script>
    function resetForm(){
        $('#review_web_form')[0].reset();
        $(".uploded_image").slideUp();
        $(".del-image").hide();
        $(".img-upload-block").show();
        $(".video-upload-block .fa.fa-video-camera").removeClass("uploaded-icon");
        $(".check-video").addClass("hide");
        $(".check-audio").addClass("hide");
        $(".audio-upload-block .fa.fa-music").removeClass("uploaded-icon");
    }
/*jslint unparam: true */
/*global window, $ */
$(function () {
    'use strict';
    // Change this to the location of your server-side upload handler:
    var url = '{{$website_link}}/scripts/upload-image';
    var video_upload_url = '{{$website_link}}/scripts/upload-video';
    var audio_upload_url = '{{$website_link}}/scripts/upload-audio';
    
    $(".del-image").click(function(){
        $(".uploded_image").hide();
        $(".del-image").hide();
        $(".uploded_image").attr('src','');
        $("input[name=img_url]").val('');
        $(".img-upload-block").show();
    });

    $('#fileupload').fileupload({
        url: url,
        dataType: 'json',
        done: function (e, data) {
            var resp = data.result;
            if(resp.data.path!=undefined) {
                $("#progress").slideUp();
                $(".uploded_image").slideDown();
                $(".del-image").show();
                $(".uploded_image").attr('src',resp.data.path);
                $("input[name=img_url]").val(resp.data.path);
                $(".submit_review").prop('disabled', false);
            }
            else{
                $(".submit_review").prop('disabled', true);
                $("#progress").slideUp();
                $(".img-upload-block").show();
                var errors = resp.data.error.upload_image;
                var out = '<ul>';
                for (var i = errors.length - 1; i >= 0; i--) {
                    out += '<li>'+errors[i]+'</li>';
                }
                out += '</ul>';
                $(".img-errors").html(out);
            }
        },
        progressall: function (e, data) {
            $(".submit_review").prop('disabled', true);
            $("#progress").show();
            $(".img-errors").html('');
            $(".img-upload-block").hide();
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress .progress-bar').css(
                'width',
                progress + '%'
            );
        },fail: function (e, data) {
           $(".submit_review").prop('disabled', false);
                $("#progress").slideUp();
                $(".img-upload-block").show();
                $(".img-errors").html("<ul><li>The uploaded Image may not be greater than 5 MB.</li></ul>");
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');

    $('#fileuploadvideo').fileupload({
        url: '{{$website_link}}/scripts/upload-video',
        dataType: 'json',
        done: function (e, data) {
            var resp = data.result;
            if(resp.data.path!=undefined) {
                $("#progressvideo").slideUp();
                $("input[name=video_url]").val(resp.data.path);
                $(".check-video").removeClass("hide");
                $(".submit_review").prop('disabled', false);
				$(".video-upload-block .fa.fa-video-camera").addClass("uploaded-icon");
            }
            else{
                $(".submit_review").prop('disabled', false);
                $("#progressvideo").slideUp();
                $(".video-upload-block").show();
                var errors = resp.data.error.upload_video;
                var out = '<ul>';
                for (var i = errors.length - 1; i >= 0; i--) {
                    out += '<li>'+errors[i]+'</li>';
                }
                out += '</ul>';
                $(".vid-errors").html(out);
            }
        },
        progressall: function (e, data) {
            $(".submit_review").prop('disabled', true);
            $("#progressvideo").show();
            $(".vid-errors").html('');
            //$(".video-upload-block").hide();
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progressvideo .progress-bar').css(
                'width',
                progress + '%'
            );
        },fail: function (e, data) {
           $(".submit_review").prop('disabled', false);
                $("#progressvideo").slideUp();
                $(".video-upload-block").show();
                $(".vid-errors").html("<ul><li>The uploaded video may not be greater than 50 MB.</li></ul>");
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');

    $('#fileuploadaudio').fileupload({
        url: audio_upload_url,
        dataType: 'json',
        done: function (e, data) {
           var resp = data.result;
            if(resp.data.path!=undefined) {
                $("#progressaudio").slideUp();
                $("input[name=audio_url]").val(resp.data.path);
				$(".check-audio").removeClass("hide");
                $(".submit_review").prop('disabled', false);
				$(".audio-upload-block .fa.fa-music").addClass("uploaded-icon");
            }
            else{
                 $(".submit_review").prop('disabled', false);
                $("#progressaudio").slideUp();
                $(".audio-upload-block").show();
                var errors = resp.data.error.upload_audio;
                var out = '<ul>';
                for (var i = errors.length - 1; i >= 0; i--) {
                    out += '<li>'+errors[i]+'</li>';
                }
                out += '</ul>';
                $(".aud-errors").html(out);
				
            }
        },
        progressall: function (e, data) {
            $(".submit_review").prop('disabled', true);
            $("#progressaudio").show();
            $(".aud-errors").html('');
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progressaudio .progress-bar').css(
                'width',
                progress + '%'
            );
        },fail: function (e, data) {
           $(".submit_review").prop('disabled', false);
                $("#progressvideo").slideUp();
                $(".audio-upload-block").show();
                $(".aud-errors").html("<ul><li>The uploaded audio may not be greater than 50 MB.</li></ul>");
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');
        $('#reviewratingq li.star').on('click',function(){
        var value = $(this).attr('data-value');
        var type = $(this).attr('data-type');
        $("input[name=r_"+type+"]").val(value);
    });

});

</script>


</body>

</html>