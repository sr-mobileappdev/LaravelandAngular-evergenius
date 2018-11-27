<!DOCTYPE html>
<html lang="en">
<head>
  <title>EverGenius</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
  <link rel="stylesheet" href="../../widget_3/css/style.css">
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
</head>
<body>
<style>


.display-hide { display: none; }

@if(isset($form_style['headerBg']))
  .rating-bussiness-head{
  background:#54b4ee;
  }
  .rating-tabs a{
  border-bottom:solid 4px #fff;
  color:#54b4ee !important ;
  
}
.rating-tabs a:hover, .rating-tabs a.active{
  border-color:#{{$form_style['headerBg']}} !important ;
}
.rating-tabs a:hover, .rating-tabs a.active{
  color:#{{$form_style['headerBg']}} !important;
}
.rating-tabs a:hover, .rating-tabs li.active a{
  border-color:#{{$form_style['headerBg']}} !important ;
}
.rating-tabs a.active{
  color:#{{$form_style['headerBg']}} !important;
}
@endif

@if(isset($form_style['bodyBackground']))
      .body-bg{ background: #{{$form_style['bodyBackground']}} }  
@endif

@if(isset($form_style['bodyTextSize']))
      .review-content-box p{ font-size: {{$form_style['bodyTextSize']}}px !important; }
@endif

@if(isset($form_style['verifiedTextBackground']))
   .verify-btn-success { background-color: #{{$form_style['verifiedTextBackground']}} !important;  }  
@endif 
@if(isset($form_style['stripeOne']))
   .rating-list li{
  background:#{{$form_style['stripeOne']}} !important;
}
@endif 
@if(isset($form_style['stripeTwo']))
.rating-list li:nth-child(2n){
  background:#{{$form_style['stripeTwo']}} !Important;
}
@endif 
@if(isset($form_style['ovrallStripe']))
.rating-list li.overall-rating{
  background:#{{$form_style['ovrallStripe']}} !Important;
}
@endif 
@if(isset($form_style['headerTextColor']))
.rating-bussiness-head .verified-rating{
  color:#{{$form_style['headerTextColor']}} !important;
  border:solid 2px #{{$form_style['headerTextColor']}} !important;
}
@endif
@if(isset($form_style['verifiedTextColor']))
.rating-bussiness-head .rating-number{
  background:#{{$form_style['headerTextColor']}} !important;
}
@endif 
@if(isset($form_style['rateThisBackgroundColor']))
.rate-this a{
  background-color:#{{$form_style['rateThisBackgroundColor']}} !important;
}
@endif 

@if(isset($form_style['ratingTitleColor']))
.rating-col.rating-title{
  color:#{{$form_style['ratingTitleColor']}} !important;
}
@endif 

</style>
<div class="rating-bussiness">
  <div class="rating-bussiness-head">
    <span class="top-rated"><img src="../../widget_3/images/img-top-rated.png" alt="Top Rated Doctor"></span>
    <div class="verified-rating">
      <span class="rating-number">{{$total_reviews}}</span> <p>{{$form_style['verifiedText']}}</p>
    </div>
  </div>
  
  <div class="rating-bussinus-middle">
    <div class="rating-tabs">
     <ul><li class="active" ><a data-toggle="tab" href="#ratings">Ratings</a></li> <li><a data-toggle="tab" href="#reviews">Reviews</a></li></ul>
    </div>
  </div>
  <div class="tab-content">
  <div id="ratings" class="tab-pane fade in active">
  <div class="rating-list">
    <ul>
      <li>
        <div class="rating-col rating-title">Quality</div>
        <div class="rating-col">
          <div class="rating-star">
            <div class="rating-strip">
            <span class="rating-per" style="width: {{$widget_3['css_rating_quality']}}%;" ></span>
            </div>
          </div>
        </div>
        <div class="rating-col">
          <div class="rating-number">{{$widget_3['avg_rating_quality']}}</div>
        </div>
      </li>
      <li>
        <div class="rating-col rating-title">Value</div>
        <div class="rating-col">
          <div class="rating-star">
            <div class="rating-strip">
            <span class="rating-per" style="width: {{$widget_3['css_rating_value']}}%;" ></span>
            </div>
          </div>
        </div>
        <div class="rating-col">
          <div class="rating-number">{{$widget_3['avg_rating_value']}}</div>
        </div>
      </li>
      <li>
        <div class="rating-col rating-title">Timeliness</div>
        <div class="rating-col">
          <div class="rating-star">
            <div class="rating-strip">
            <span class="rating-per" style="width: {{$widget_3['css_rating_timeliness']}}%;" ></span>
            </div>
          </div>
        </div>
        <div class="rating-col">
          <div class="rating-number">{{$widget_3['avg_rating_timeliness']}}</div>
        </div>
      </li>
      <li>
        <div class="rating-col rating-title">Experience</div>
        <div class="rating-col">
         <div class="rating-star">
            <div class="rating-strip">
            <span class="rating-per" style="width: {{$widget_3['css_rating_experience']}}%;" ></span>
            </div>
          </div>
        </div>
        <div class="rating-col">
          <div class="rating-number">{{$widget_3['avg_rating_experience']}}</div>
        </div>
      </li>
      
      <li>
        <div class="rating-col rating-title">Satisfaction</div>
        <div class="rating-col">
          <div class="rating-star">
            <div class="rating-strip">
            <span class="rating-per" style="width: {{$widget_3['css_rating_satisfaction']}}%;" ></span>
            </div>
          </div>
        </div>
        <div class="rating-col">
          <div class="rating-number">{{$widget_3['avg_rating_satisfaction']}}</div>
        </div>
      </li>
      
      <li class="overall-rating">
        <div class="rating-col rating-title">Overall Rating</div>
        <div class="rating-col">
          <div class="rating-star">
            <div class="rating-strip">
            @php $overall_r_width = $avg_rating>0?(($avg_rating*100)/5):0; @endphp 
            <span class="rating-per" style="width: {{$overall_r_width}}%;"  ></span>
            </div>
          </div>
        </div>
        <div class="rating-col">
          <div class="rating-number">{{$avg_rating}}</div>
        </div>
      </li>
    </ul>
  </div>
  </div>
  
      <div id="reviews" class="tab-pane fade">
      <div class="reviews-list">
        <ul>
          @foreach($review_listing as $rate_item)
          <li>
            <div class="review-status">Verified </div>
            <h2>{{ucwords($rate_item['first_name'])}} {{ucwords($rate_item['last_name'])}} </h2>
            <div class="star-rating-view">
              <div class="star-rating-strip">
              @php $ratingwidth = (($rate_item['rating']*100)/5); @endphp 
                <span style="width:{{$ratingwidth}}%;"></span>
              </div>
            </div>
            <p>{{ucwords($rate_item['user_review'])}} </p>
            <div class="review-date">
            @if(isset($form_style['showDate']) && $form_style['showDate']=='true')
            {{date('M d,Y',strtotime($rate_item['published_time']))}}
            @endif</div>
          </li>
           @endforeach
        </ul>
      </div>
    </div>
    </div>
  <div class="rate-this">
    <!--<a href="#" data-toggle="modal" data-target="#leave-review">Rate This Business</a>-->
   <a href="javascript:void(0)" rel="nofollow" class="social_share_link">Rate This Business</a>
  </div>

  <div class="rating-bussiness-bottom">
    <img src="../../widget_3/images/img-logo-honest-doctor.gif" alt="Honest Doctor">
  </div>
</div>  

<!-- Add review popup -->


<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script src="../../widget_3/js/star-rating.js"></script> 

<!--Scripts-->



<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="{{$website_link}}/js/jquery.ui.widget.js"></script>

<script>
$("a.social_share_link").on("click", function() {
var share_link = "{{$website_link}}/scripts/widgets/form-preview/?api_key={{$company_details['api_key']}}&bodyBackgroundColor=ffffff&labelColor=000000&bodyText=14&submitButtonBackgroundColor=ed1d26&submitFontColor=ffffff&cancelButtonBackground=c1c1c1&cancelButtonFontColor=ffffff";
window.open(share_link, "_blank","width=850, height=650, top=0, left=200");
});
</script>

<!--Scripts-->

</body>
</html>