<!DOCTYPE html>
<html lang="en">
<head>
  <title>EverGenius</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
  <link rel="stylesheet" href="../../css/style-review.css">
  <link rel="stylesheet" href="../../css/owl.carousel.min.css">
  <link rel="stylesheet" href="../../css/owl.theme.default.min.css">
  <script
  src="https://code.jquery.com/jquery-3.2.1.min.js"
  integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
  crossorigin="anonymous"></script>

  <script src="../../js/owl.carousel.min.js"></script>
  <style type="text/css">
  .owl-carousel .owl-item img {
    display: inline;
    width: auto;
  }
  @if(isset($form_style['headerBg']))
  .review-header{ background: #{{$form_style['headerBg']}} }  
  @endif
  @if(isset($form_style['bodyBackground']))
  .body-bg{ background: #{{$form_style['bodyBackground']}} }  
  @endif
  @if(isset($form_style['bodyTextSize']))
  .review-content-box p{ font-size: {{$form_style['bodyTextSize']}}px !important; }
  @endif
  @if(isset($form_style['verifiedTextColor']))
  .verify-btn-success { color: #{{$form_style['verifiedTextColor']}}; }
  @endif
  @if(isset($form_style['verifiedTextBackground']))
  .verify-btn-success { background-color: #{{$form_style['verifiedTextBackground']}} !important;  }  
  @endif 
</style>

</head>
<body>
  <div class="sidebar-widget-container">
    <div class="display-review sidebar-widget body-bg">
     <div class="display-review-header review-header">
      <div class="review-date"><span><i class="fa fa-calendar"></i> {{date('l M d Y',time())}}</span> <span><i class="fa fa-commenting-o"></i> {{$total_reviews}} Reviews</span></div>
      
      <div class="honest-logo"> 
        @if(isset($form_style['honestDoctorLogo']) && $form_style['honestDoctorLogo']=='true')
        <img src="../../img/img-honest-doctor-sidebar.png">
        @endif
      </div>
      <div class="review-header-rating">
        <span>Average rating</span>
        <div class="star-counting star-{{round($avg_rating,0)}}"></div>
      </div>	
    </div>
    <div class="owl-carousel owl-theme">
      @foreach($review_listing as $rate_item)
      <div class="item">
       <div class="sidebar-review-box body-bg">
        <div class="sidebar-review-box-author">
         <div class="author-name clearfix">
           @if($rate_item['img_url']!=null)
           <img src="{{$rate_item['img_url']}}"> 
           @else
           <img src="../../img/user-100x100.jpg"> 
           @endif
           
           <div>{{ucwords($rate_item['first_name'])}} {{ucwords($rate_item['last_name'])}} <br> 
             
            @if(isset($form_style['showDate']) && $form_style['showDate']=='true')
            <small> {{date('M d,Y',strtotime($rate_item['published_time']))}}</small>
            @endif

          </div>
          <div class="author-rating">
            <div class="star-counting star-{{$rate_item['rating']}}"></div>
          </div>	
        </div>
      </div>
      
      <div class="review-content-box">
        <!-- 	<h3>Still reading - can’t wait to start</h3> -->
        <p>{{ucwords($rate_item['user_review'])}}</p>
        <span class="btn btn-success verify-btn-success"  style="cursor: default;"><i class="fa fa-check-circle"></i> {{$form_style['verifiedText']}}</span>
      </div>
      
    </div>
  </div>

  @endforeach
</div>
</div>
</div>
<script>
	$('.owl-carousel').owlCarousel({
    loop:true,
    margin:10,
    autoplay:true,
    autoplayTimeout:5000,
    smartSpeed:1000,
    nav:false,
    dots:false,
    responsive:{
      0:{
        items:1
      },
      600:{
        items:1
      },
      1000:{
        items:1
      }
    }
  });
</script>

</body>
</html>